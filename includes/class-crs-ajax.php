<?php
if (!defined('ABSPATH')) exit;

class CRS_Ajax {
    
    public static function init() {
        $ajax_handlers = [
            'crs_load_step' => ['logged_in' => true, 'nopriv' => true],
            'crs_get_status_nonce' => ['logged_in' => true],
            'crs_save_without_payment' => ['logged_in' => true],
            'crs_load_stripe_payment_form' => ['logged_in' => true],
            'crs_create_stripe_payment_intent' => ['logged_in' => true],
            'crs_confirm_stripe_payment' => ['logged_in' => true],
            'crs_load_documents_page' => ['logged_in' => true],
            'crs_send_invoice' => ['logged_in' => true, 'capability' => 'manage_options'],
            'crs_send_test_email' => ['logged_in' => true, 'capability' => 'manage_options'],
            'crs_stripe_webhook' => ['logged_in' => false, 'nopriv' => true],
            'crs_validate_coupon' => ['logged_in' => true, 'nopriv' => true],
            'crs_create_third_person_user' => ['logged_in' => true, 'nopriv' => true],

        ];
        
        foreach ($ajax_handlers as $handler => $args) {
            if ($args['logged_in']) {
                add_action('wp_ajax_' . $handler, [self::class, $handler]);
            }
            if (!empty($args['nopriv'])) {
                add_action('wp_ajax_nopriv_' . $handler, [self::class, $handler]);
            }
        }
    }
    
    public static function crs_load_step() {
        check_ajax_referer('crs_nonce', 'nonce');
        $step = intval($_POST['step']);
        $congress_id = intval($_POST['congress_id'] ?? 0);
        $data = isset($_POST['data']) ? json_decode(stripslashes($_POST['data']), true) : [];
        
        $step_methods = [
            1 => [CRS_Steps::class, 'step1'],
            2 => [CRS_Steps::class, 'step2'],
            3 => [CRS_Steps::class, 'step3'],
            4 => [CRS_Steps::class, 'step4'],
            5 => [CRS_Steps::class, 'step5'],
            6 => [CRS_Steps::class, 'step6'],
            7 => [CRS_Steps::class, 'step7'],
            8 => [self::class, 'getStep8Html']
        ];

        if (isset($step_methods[$step])) {
            if (is_array($step_methods[$step])) {
                $html = call_user_func($step_methods[$step], $congress_id, $data);
            } else {
                $html = $step_methods[$step]($congress_id, $data);
            }
            wp_send_json_success(['html' => $html]);
        }
    }

    // Add this method
    public static function crs_create_third_person_user() {
        check_ajax_referer('crs_nonce', 'nonce');
        
        $data = json_decode(stripslashes($_POST['data']), true);
        
        if (!isset($data['registration_type']) || $data['registration_type'] !== 'third_person') {
            wp_send_json_error(['message' => 'Invalid registration type']);
        }
        
        $name = sanitize_text_field($data['third_person_name'] ?? '');
        $email = sanitize_email($data['third_person_email'] ?? '');
        $password = $data['third_person_password'] ?? '';
        
        if (empty($name) || empty($email) || empty($password)) {
            wp_send_json_error(['message' => 'Missing required fields']);
        }
        
        // Check if user already exists
        $existing_user = email_exists($email);
        if ($existing_user) {
            wp_send_json_success([
                'user_id' => $existing_user,
                'message' => 'User already exists'
            ]);
            return;
        }
        
        // Create username from email
        $username = sanitize_user(current(explode('@', $email)), true);
        $original_username = $username;
        $counter = 1;
        while (username_exists($username)) {
            $username = $original_username . $counter;
            $counter++;
        }
        
        $name_parts = explode(' ', $name, 2);
        $first_name = $name_parts[0];
        $last_name = $name_parts[1] ?? '';
        
        // Create user (without logging in)
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error(['message' => $user_id->get_error_message()]);
            return;
        }
        
        // Update user meta
        wp_update_user([
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $name
        ]);
        
        // Set role
        $user = new WP_User($user_id);
        $user->set_role('subscriber');
        
        // IMPORTANT: DO NOT log the user in
        // Keep current user session unchanged
        
        error_log('CRS: Third person user created (not logged in): ' . $user_id . ' - ' . $email);
        
        wp_send_json_success([
            'user_id' => $user_id,
            'message' => 'User created successfully'
        ]);
    }
    
    public static function crs_get_status_nonce() {
        check_ajax_referer('crs_ajax_nonce', 'nonce');
        $status = sanitize_text_field($_POST['status'] ?? '');
        $booking_id = intval($_POST['booking_id'] ?? 0);
        
        if ($status && $booking_id) {
            wp_send_json_success(['nonce' => wp_create_nonce('mark_' . $status . '_' . $booking_id)]);
        }
        wp_send_json_error('Invalid parameters');
    }

    /**
     * Validate coupon AJAX handler
     */
    public static function crs_validate_coupon() {
        check_ajax_referer('crs_nonce', 'nonce');
        
        $code = sanitize_text_field($_POST['code'] ?? '');
        $total = floatval($_POST['total'] ?? 0);
        $user_id = get_current_user_id();
        
        if (empty($code)) {
            wp_send_json_error(['message' => 'Please enter a coupon code']);
        }
        
        // Check if coupon class exists
        if (!class_exists('CRS_Coupon')) {
            require_once CRS_PLUGIN_DIR . 'includes/class-crs-coupon.php';
        }
        
        $coupon_obj = new CRS_Coupon();
        $result = $coupon_obj->validate_coupon($code, $total, $user_id);
        
        if ($result['valid']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }
    
    public static function crs_save_without_payment() {
        check_ajax_referer('crs_nonce', 'nonce');
        $data = json_decode(stripslashes($_POST['data']), true);
        
        // Use current user (already logged in, might be third person from step 1)
        $user_id = get_current_user_id();

         // If third person user ID exists in formData, use that
        if (isset($data['third_person_user_id']) && !empty($data['third_person_user_id'])) {
            $user_id = intval($data['third_person_user_id']);
        }
    
        
        // Only create third person if not already created (fallback)
        if (isset($data['registration_type']) && $data['registration_type'] === 'third_person' && !$user_id) {
            $user_id = CRS_Payment::createThirdPersonUser($data);
        }
        
        $booking_id = CRS_Payment::saveBooking($data, 'pending', '', $user_id);
        
        if ($booking_id) {
            global $wpdb;
            $booking_data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}cr_bookings WHERE id = %d",
                $booking_id
            ));
            do_action('crs_after_pending_registration', $booking_id, $booking_data);
            
            wp_send_json_success([
                'message' => 'Registration saved as pending',
                'booking_id' => $booking_id,
                'redirect' => home_url('/my-registrations/?booking=' . $booking_id)
            ]);
        } else {
            wp_send_json_error('Failed to save booking');
        }
    }
    
    public static function crs_load_stripe_payment_form() {
        check_ajax_referer('crs_nonce', 'nonce');
        $temp_booking_id = sanitize_text_field($_POST['temp_booking_id']);
        $intent_id = sanitize_text_field($_POST['intent_id']);
        $client_secret = sanitize_text_field($_POST['client_secret']);
        $publishable_key = sanitize_text_field($_POST['publishable_key']);
        
        if (!get_transient('crs_temp_booking_' . $temp_booking_id)) {
            wp_send_json_error('Temporary booking data not found');
        }
        
        ob_start();
        include CRS_PLUGIN_DIR . 'templates/stripe-payment-form.php';
        $html = ob_get_clean();
        wp_send_json_success(['html' => $html]);
    }
    
    public static function crs_create_stripe_payment_intent() {
        check_ajax_referer('crs_nonce', 'nonce');
        
        // User is already logged in (created in step 1)
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        $data = json_decode(stripslashes($_POST['data']), true);
        
        // IMPORTANT: Make sure coupon data is in the data array
        // Get coupon code from formData if present
        $coupon_code = '';
        if (isset($data['applied_coupon_code']) && !empty($data['applied_coupon_code'])) {
            $coupon_code = $data['applied_coupon_code'];
        }
        
        error_log('CRS: Creating payment intent - User ID: ' . $user_id . ', Coupon: ' . $coupon_code);
        
        // Calculate total with coupon
        $total = CRS_Payment::calculateTotal($data);
        
        error_log('CRS: Calculated total after coupon: ' . $total);
        
        // Handle proof file upload
        $proof_file_data = $_POST['proof_file_data'] ?? '';
        $proof_file_name = $_POST['proof_file_name'] ?? '';
        $proof_file_id = 0;
        $proof_file_url = '';
        
        if (!empty($proof_file_data) && !empty($proof_file_name)) {
            $proof_file_id = CRS_Documents::uploadFromBase64($proof_file_data, $proof_file_name, $user_id);
            $proof_file_url = $proof_file_id ? wp_get_attachment_url($proof_file_id) : '';
        }
        
        // Create Stripe Payment Intent with calculated total
        $result = CRS_Payment::createPaymentIntent($total, $user_id, $data, $proof_file_id, $proof_file_url, $proof_file_name);
        
        if ($result['success']) {
            wp_send_json_success($result['data']);
        } else {
            error_log('CRS: Payment intent error - ' . ($result['error'] ?? 'Unknown error'));
            wp_send_json_error($result['error']);
        }
    }
    
public static function crs_confirm_stripe_payment() {
    check_ajax_referer('crs_nonce', 'nonce');
    
    $intent_id = sanitize_text_field($_POST['intent_id']);
    $temp_booking_id = sanitize_text_field($_POST['temp_booking_id']);
    
    error_log('CRS: Confirm payment - Intent ID: ' . $intent_id . ', Temp ID: ' . $temp_booking_id);
    
    $result = CRS_Payment::confirmPayment($intent_id, $temp_booking_id);
    
    if ($result['success']) {
        error_log('CRS: Payment confirmed, booking ID: ' . $result['data']['booking_id']);
        wp_send_json_success($result['data']);
    } else {
        error_log('CRS: Payment confirmation failed - ' . ($result['error'] ?? 'Unknown error'));
        wp_send_json_error($result['error']);
    }
}
    
    public static function crs_load_documents_page() {
        check_ajax_referer('crs_nonce', 'nonce');
        
        $page = intval($_POST['page']);
        $per_page = intval($_POST['per_page']);
        $user_id = get_current_user_id();
        
        if (!$user_id) wp_send_json_error('User not logged in');
        
        ob_start();
        CRS_Documents::renderDocumentsList($user_id, $page, $per_page);
        $html = ob_get_clean();
        
        wp_send_json_success(['html' => $html]);
    }
    
    public static function crs_send_invoice() {
        check_ajax_referer('crs_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        
        $booking_id = intval($_POST['booking_id']);
        global $wpdb;
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cr_bookings WHERE id = %d",
            $booking_id
        ));
        
        if ($booking) {
            do_action('crs_manual_invoice', $booking_id, $booking);
            wp_send_json_success('Invoice email triggered');
        } else {
            wp_send_json_error('Booking not found');
        }
    }
    
    public static function crs_send_test_email() {
        check_ajax_referer('crs_test_email_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        
        $to_email = sanitize_email($_POST['to_email']);
        $template = sanitize_text_field($_POST['template']);
        
        if (!is_email($to_email)) wp_send_json_error('Invalid email address');
        
        $result = CRS_Email_Core::sendTestEmail($to_email, $template);
        
        if ($result) {
            wp_send_json_success('Test email sent');
        } else {
            wp_send_json_error('Failed to send email');
        }
    }
    
private static function getStep8Html($congress_id, $data) {
    
    global $wpdb;
    $type_table = $wpdb->prefix . 'registration_types';
    
    $type = null;
    if (isset($data['registration_type_id'])) {
        $type = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $type_table WHERE id = %d",
            $data['registration_type_id']
        ));
    }
    
    // Fix hotel data retrieval with proper checks
    $hotel = null;
    $hotel_name = '';
    $price_per_night = 0;
    $hotel_total = 0;
    $nights = 0;
    $check_in_display = '';
    $check_out_display = '';
    
    // Check if hotel_id exists and is valid
    if (isset($data['hotel_id']) && !empty($data['hotel_id']) && $data['hotel_id'] != 0) {
        $hotel = get_post($data['hotel_id']);
        if ($hotel) {
            $hotel_name = get_the_title($hotel);
            $price_per_night = get_post_meta($hotel->ID, 'price_per_night', true);
            if (empty($price_per_night)) $price_per_night = 0;
            
            // IMPORTANT: Check if dates exist in the data array
            if (isset($data['check_in_date']) && !empty($data['check_in_date'])) {
                $check_in_display = $data['check_in_date'];
            }
            
            if (isset($data['check_out_date']) && !empty($data['check_out_date'])) {
                $check_out_display = $data['check_out_date'];
            }
            
            // Calculate nights and total if we have valid dates
            if (!empty($check_in_display) && !empty($check_out_display)) {
                $check_in_ts = strtotime($check_in_display);
                $check_out_ts = strtotime($check_out_display);
                
                if ($check_in_ts && $check_out_ts && $check_out_ts > $check_in_ts) {
                    $nights = ceil(($check_out_ts - $check_in_ts) / (60 * 60 * 24));
                    $hotel_total = floatval($price_per_night) * $nights;
                }
            }
        }
    }
    
    // Calculate totals
    $registration_price = $type ? floatval($type->price) : 0;
    $sidi_price = !empty($data['add_sidi']) ? 150 : 0;
    $meals_total = 0;
    $meals_list = array();
    
    if (!empty($data['meals'])) {
        $meals = get_post_meta($congress_id, 'congress_meals', true);
        if (is_array($meals)) {
            foreach ($data['meals'] as $meal_index) {
                // Handle both string and array indices
                $meal_key = is_array($meal_index) ? key($meal_index) : $meal_index;
                if (isset($meals[$meal_key])) {
                    $meals_total += floatval($meals[$meal_key]['meal_price']);
                    $meals_list[] = $meals[$meal_key];
                }
            }
        }
    }
    
    // Get workshops
    $workshops_list = array();
    if (!empty($data['workshops'])) {
        $workshops = get_post_meta($congress_id, 'congress_workshop', true);
        if (is_array($workshops)) {
            foreach ($data['workshops'] as $workshop_index) {
                $workshop_key = is_array($workshop_index) ? key($workshop_index) : $workshop_index;
                if (isset($workshops[$workshop_key])) {
                    $workshops_list[] = $workshops[$workshop_key];
                }
            }
        }
    }
    
    $subtotal = $registration_price + $sidi_price + $hotel_total + $meals_total;
    $total = $subtotal;
    $discount_amount = 0;
    $applied_coupon = null;
    
    // Check if coupon is applied
    if (isset($data['applied_coupon_code']) && !empty($data['applied_coupon_code'])) {
        if (!class_exists('CRS_Coupon')) {
            require_once CRS_PLUGIN_DIR . 'includes/class-crs-coupon.php';
        }
        $coupon_obj = new CRS_Coupon();
        $result = $coupon_obj->validate_coupon($data['applied_coupon_code'], $subtotal, get_current_user_id());
        var_dump($result);
        error_log($result);
        if ($result['valid']) {
            $applied_coupon = $result['coupon'];
            $discount_amount = $result['discount'];
            $total = $result['final_amount'];
        }
    }

    
    ob_start();
    ?>
    <div class="crs-step-content">
        <h2 class="crs-step-title"><?php _e('Summary and Confirmation', 'crscngres'); ?></h2>
        <p class="crs-step-description"><?php _e('Please review your registration details before confirming.', 'crscngres'); ?></p>
        
        <div class="crs-summary-grid">
            <!-- Left Column - Details -->
            <div class="crs-summary-left">
                <!-- Personal Data -->
                <div class="crs-summary-card">
                    <h3 class="crs-card-title"><?php _e('Personal Data', 'crscngres'); ?></h3>
                    <div class="crs-data-list">
                        <div class="crs-data-row">
                            <span class="crs-data-label"><?php _e('Name:', 'crscngres'); ?></span>
                            <span class="crs-data-value"><?php echo isset($data['first_name']) ? esc_html($data['first_name'] . ' ' . ($data['last_name'] ?? '')) : ''; ?></span>
                        </div>
                        <div class="crs-data-row">
                            <span class="crs-data-label"><?php _e('DNI:', 'crscngres'); ?></span>
                            <span class="crs-data-value"><?php echo isset($data['id_number']) ? esc_html($data['id_number']) : ''; ?></span>
                        </div>
                        <div class="crs-data-row">
                            <span class="crs-data-label"><?php _e('Email:', 'crscngres'); ?></span>
                            <span class="crs-data-value"><?php echo isset($data['email']) ? esc_html($data['email']) : ''; ?></span>
                        </div>
                        <div class="crs-data-row">
                            <span class="crs-data-label"><?php _e('Telephone:', 'crscngres'); ?></span>
                            <span class="crs-data-value"><?php echo isset($data['phone']) ? esc_html($data['phone']) : ''; ?></span>
                        </div>
                        <div class="crs-data-row">
                            <span class="crs-data-label"><?php _e('Direction:', 'crscngres'); ?></span>
                            <span class="crs-data-value"><?php 
                                $address_parts = [];
                                if (!empty($data['address'])) $address_parts[] = $data['address'];
                                if (!empty($data['location'])) $address_parts[] = $data['location'];
                                if (!empty($data['postal_code'])) $address_parts[] = $data['postal_code'];
                                if (!empty($data['country'])) $address_parts[] = $data['country'];
                                echo esc_html(implode(', ', $address_parts));
                            ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Registration Type -->
                <?php if ($type): ?>
                <div class="crs-summary-card">
                    <h3 class="crs-card-title"><?php _e('Registration Type', 'crscngres'); ?></h3>
                    <div class="crs-data-row">
                        <span class="crs-data-label"><?php echo esc_html($type->name); ?></span>
                        <span class="crs-data-value"><?php printf(__('€%s', 'crscngres'), number_format($type->price, 0)); ?></span>
                    </div>
                    <?php if (!empty($data['add_sidi'])): ?>
                    <div class="crs-data-row">
                        <span class="crs-data-label"><?php _e('+ SIDI Congress 2026', 'crscngres'); ?></span>
                        <span class="crs-data-value"><?php _e('€150', 'crscngres'); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Accommodation - Fixed with proper data -->
                <?php if ($hotel && !empty($check_in_display) && !empty($check_out_display) && $nights > 0): ?>
                <div class="crs-summary-card">
                    <h3 class="crs-card-title"><?php _e('Accommodation', 'crscngres'); ?></h3>
                    <div class="crs-data-row">
                        <span class="crs-data-label"><?php echo esc_html($hotel_name); ?></span>
                        <span class="crs-data-value"><?php printf(__('€%s', 'crscngres'), number_format($hotel_total, 0)); ?></span>
                    </div>
                    <div class="crs-hotel-dates">
                        <?php echo sprintf(_n('%s night', '%s nights', $nights, 'crscngres'), $nights); ?> (<?php echo $check_in_display; ?> - <?php echo $check_out_display; ?>)
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Lunches and Dinners -->
                <?php if (!empty($meals_list)): ?>
                <div class="crs-summary-card">
                    <h3 class="crs-card-title"><?php _e('Lunches and Dinners', 'crscngres'); ?></h3>
                    <?php foreach ($meals_list as $meal): ?>
                    <div class="crs-data-row">
                        <span class="crs-data-label"><?php echo esc_html($meal['meal_title'] ?? $meal['title'] ?? __('Meal', 'crscngres')); ?></span>
                        <span class="crs-data-value"><?php printf(__('€%s', 'crscngres'), $meal['meal_price'] ?? $meal['price'] ?? 0); ?></span>
                    </div>
                    <?php endforeach; ?>
                    <div class="crs-data-row crs-total-row">
                        <span class="crs-data-label"><?php _e('Total Meals', 'crscngres'); ?></span>
                        <span class="crs-data-value"><?php printf(__('€%s', 'crscngres'), $meals_total); ?></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Workshops -->
                <?php if (!empty($workshops_list)): ?>
                <div class="crs-summary-card">
                    <h3 class="crs-card-title"><?php _e('Workshops', 'crscngres'); ?></h3>
                    <?php foreach ($workshops_list as $workshop): ?>
                    <div class="crs-data-row">
                        <span class="crs-data-label crs-checkmark">✓ <?php echo esc_html($workshop['workshop_title'] ?? $workshop['title'] ?? __('Workshop', 'crscngres')); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Right Column - Economic Summary -->
            <div class="crs-summary-right">

            <!-- Coupon Section - Professional Design -->
            <div class="crs-coupon-container" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                
                <!-- Header -->
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); width: 36px; height: 36px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <span style="font-size: 18px;">🏷️</span>
                    </div>
                    <div>
                        <h4 style="font-size: 16px; font-weight: 600; color: #1e293b; margin: 0;"><?php _e('Have a coupon?', 'crscngres'); ?></h4>
                        <p style="font-size: 12px; color: #64748b; margin: 2px 0 0 0;">Enter your code for instant discount</p>
                    </div>
                </div>
                
                <!-- Input Group -->
                <div class="crs-coupon-input-group" style="display: flex; gap: 12px; margin-bottom: 15px;">
                    <div style="flex: 1; position: relative;">
                        <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px;">🔖</span>
                        <input type="text" class="crs-input" id="coupon_code" 
                            placeholder="<?php _e('Enter coupon code', 'crscngres'); ?>" 
                            value="<?php echo isset($data['applied_coupon_code']) ? esc_attr($data['applied_coupon_code']) : ''; ?>"
                            style="width: 100%; padding: 12px 12px 12px 38px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; font-weight: 500; letter-spacing: 0.5px; background: #fafcff; transition: all 0.2s;">
                    </div>
                    <button type="button" id="apply-coupon" 
                            style="padding: 12px 28px; background: #4f46e5; border: none; border-radius: 10px; font-weight: 600; font-size: 14px; color: white; cursor: pointer; transition: all 0.2s; white-space: nowrap; box-shadow: 0 2px 6px rgba(79, 70, 229, 0.2);">
                        <?php _e('Apply', 'crscngres'); ?>
                    </button>
                </div>
                
                <!-- Message Area -->
                <div id="coupon-message" class="crs-coupon-message" style="display: none; margin-top: 12px; padding: 10px 14px; border-radius: 10px; font-size: 13px; font-weight: 500;"></div>
                
                <!-- Applied Coupon Details -->
                <div id="coupon-details" class="crs-coupon-details" 
                    style="<?php echo $applied_coupon ? 'display: flex;' : 'display: none;'; ?> margin-top: 16px; padding: 14px 18px; background: #f0f9ff; border: 1px solid #b9e0f7; border-radius: 12px; align-items: center; gap: 12px; flex-wrap: wrap;">
                    
                    <div style="display: flex; align-items: center; gap: 8px; background: white; padding: 6px 14px; border-radius: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <span style="color: #10b981; font-size: 14px;">✓</span>
                        <span style="color: #0369a1; font-size: 13px; font-weight: 500;"><?php _e('Applied', 'crscngres'); ?></span>
                    </div>
                    
                        <div style="display: flex; justify-content: space-between; margin: 12px 0; ">
                            <span class="crs-coupon-code" style="color: black; font-size: 14px; font-weight: 700; letter-spacing: 0.5px;">
                                Saved
                            </span>
                            
                            <span id="coupon-type-badge" style=" color: black;font-size: 14px; font-weight: 600;">
                                <?php 
                                if ($applied_coupon) {
                                    if ($applied_coupon->discount_type === 'percentage') {
                                        echo $applied_coupon->discount_value . '% OFF';
                                    } else {
                                        echo '€' . $applied_coupon->discount_value . ' OFF';
                                    }
                                }
                                ?>
                            </span>
                        </div>
                    

                    
                    <button type="button" id="remove-coupon" 
                            style="margin-left: auto; background: white; border: none; width: 32px; height: 32px; border-radius: 50%; font-size: 20px; cursor: pointer; color: #64748b; display: flex; align-items: center; justify-content: center; transition: all 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.1);"
                            onmouseover="this.style.background='#fee2e2'; this.style.color='#dc2626'; this.style.transform='scale(1.05)'" 
                            onmouseout="this.style.background='white'; this.style.color='#64748b'; this.style.transform='scale(1)'">
                        ×
                    </button>
                </div>










                
            </div>
                





















               <!-- Economic Summary Card -->
                <div class="crs-economic-card" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
                    <h3 class="crs-card-title" style="font-size: 18px; font-weight: 600; color: #0f172a; margin: 0 0 15px 0; padding-bottom: 10px; border-bottom: 2px solid #2271b1; display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 20px;">💰</span>
                        <?php _e('Economic Summary', 'crscngres'); ?>
                    </h3>
                    
                    <div style="margin-bottom: 10px;">
                        <!-- Registration Row -->
                        <div class="crs-economic-row" style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px dashed #e2e8f0;">
                            <span class="crs-economic-label" style="color: #475569; font-weight: 500;"><?php _e('Registration', 'crscngres'); ?></span>
                            <span class="crs-economic-value" id="reg-price" style="color: #0f172a; font-weight: 600;"><?php printf(__('€%s', 'crscngres'), number_format($registration_price, 0)); ?></span>
                        </div>
                        
                        <?php if ($meals_total > 0): ?>
                        <!-- Meals Row -->
                        <div class="crs-economic-row" style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px dashed #e2e8f0;">
                            <span class="crs-economic-label" style="color: #475569; font-weight: 500;"><?php _e('Food', 'crscngres'); ?></span>
                            <span class="crs-economic-value" id="meals-price" style="color: #0f172a; font-weight: 600;"><?php printf(__('€%s', 'crscngres'), number_format($meals_total, 0)); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($hotel_total > 0): ?>
                        <!-- Hotel Row -->
                        <div class="crs-economic-row" style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px dashed #e2e8f0;">
                            <span class="crs-economic-label" style="color: #475569; font-weight: 500;"><?php _e('Accommodation', 'crscngres'); ?></span>
                            <span class="crs-economic-value" id="hotel-price" style="color: #0f172a; font-weight: 600;"><?php printf(__('€%s', 'crscngres'), number_format($hotel_total, 0)); ?></span>
                        </div>
                        <?php endif; ?>

                    <!-- Discount Row (shown only when coupon applied) -->
                    <div class="crs-economic-row crs-economic-discount" id="discount-row" 
                        style="display: <?php echo $discount_amount > 0 ? 'flex' : 'none'; ?>; justify-content: space-between; align-items: center; padding: 12px ; background: linear-gradient(135deg, #fef9e8 0%, #fff5e6 100%); border-radius: 10px; margin: 8px 0; border: 1px solid #ffe4b5;">
                        
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="background: #f59e0b; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <span style="font-size: 14px; color: white;">🏷️</span>
                            </div>
                            <div>
                                <span style="color: #1e293b; font-weight: 600; font-size: 14px;"><?php _e('Discount', 'crscngres'); ?></span>
                                <?php if ($applied_coupon): ?>
                                <span style="margin-left: 10px; background: #f59e0b; color: white; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 500;">
                                    <?php 
                                    if ($applied_coupon->discount_type === 'percentage') {
                                        echo $applied_coupon->discount_value . '% OFF';
                                    } else {
                                        echo '€' . $applied_coupon->discount_value . ' OFF';
                                    }
                                    ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <span style="color: #059669; font-weight: 500; font-size: 12px;">SAVED</span>
                            <span id="discount-amount" style="color: #059669; font-weight: 800; font-size: 18px;">
                                -€<?php echo number_format($discount_amount, 0); ?>
                            </span>
                        </div>
                    </div>
                        





                        <!-- Total Row -->
                        <div class="crs-economic-row crs-economic-total" style="display: flex; justify-content: space-between; padding: 15px 0 5px 0; margin-top: 5px; border-top: 2px solid #2271b1;">
                            <span class="crs-economic-label" style="color: #0f172a; font-weight: 700; font-size: 16px;"><?php _e('Total', 'crscngres'); ?></span>
                            <span class="crs-economic-value" id="total-amount" style="color: #2271b1; font-weight: 700; font-size: 20px;"><?php printf(__('€%s', 'crscngres'), number_format($total, 0)); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Hidden fields for coupon data -->
                <input type="hidden" name="applied_coupon_id" id="applied_coupon_id" value="<?php echo $applied_coupon ? esc_attr($applied_coupon->id) : ''; ?>">
                <input type="hidden" name="applied_coupon_code" id="applied_coupon_code" value="<?php echo $applied_coupon ? esc_attr($applied_coupon->code) : ''; ?>">
                <input type="hidden" name="discount_amount" id="discount_amount" value="<?php echo $discount_amount; ?>">
                <input type="hidden" name="subtotal_amount" id="subtotal_amount" value="<?php echo $subtotal; ?>">
                <input type="hidden" name="final_amount" id="final_amount" value="<?php echo $total; ?>">

                <!-- Request Invoice -->
                <div class="crs-option-card invoice_rqquest_section">
                    <label class="crs-checkbox-label">
                        <input type="checkbox" name="request_invoice" id="request_invoice">
                        <span class="crs-checkbox-custom"></span>
                        <span class="crs-checkbox-text"><?php _e('Request an invoice', 'crscngres'); ?></span>
                    </label>
                    
                    <div class="crs-invoice-fields" style="display: none;">
                        <input type="text" class="crs-input" name="company_name" placeholder="<?php _e('Company name', 'crscngres'); ?>">
                        <input type="text" class="crs-input" name="tax_address" placeholder="<?php _e('Tax address', 'crscngres'); ?>">
                        <input type="text" class="crs-input" name="cif" placeholder="<?php _e('CIF', 'crscngres'); ?>">
                        <input type="tel" class="crs-input" name="invoice_phone" placeholder="<?php _e('Phone', 'crscngres'); ?>">
                        <input type="email" class="crs-input" name="invoice_email" placeholder="<?php _e('Email for invoice', 'crscngres'); ?>">
                    </div>
                </div>
                
                <!-- Terms and Conditions -->
                <div class="crs-option-card term_and_conditions">
                    <label class="crs-checkbox-label">
                        <input type="checkbox" name="accept_terms" id="accept_terms">
                        <span class="crs-checkbox-custom"></span>
                        <span class="crs-checkbox-text"><?php printf(__('I have read and agree to the <a href="%s" target="_blank">Terms of Use</a>', 'crscngres'), '/terms-of-use'); ?></span>
                    </label>
                </div>

                <!-- Payment Container (hidden initially) -->
                <div id="crs-payment-container" class="crs-payment-container"></div>

                <!-- Payment Button -->
                <button type="button" class="crs-pay-button" data-total="<?php echo $total; ?>" data-subtotal="<?php echo $subtotal; ?>">
                    <?php printf(__('Pay €%s', 'crscngres'), number_format($total, 0)); ?>
                </button>
                
                <!-- Save without paying -->
                <button type="button" class="crs-save-button">
                    <?php _e('Save without paying (pending)', 'crscngres'); ?>
                </button>
            </div>
        </div>
    </div>

    
<style>
/* Coupon Section Styles */
.crs-coupon-container {
    transition: all 0.2s ease;
}

.crs-coupon-container:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.crs-input:focus {
    outline: none;
    border-color: #4f46e5 !important;
    background: white !important;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

#apply-coupon:hover {
    background: #4338ca !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3) !important;
}

#apply-coupon:active {
    transform: translateY(0);
}

/* Message styles */
.crs-coupon-message.success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #b8e6b9;
}

.crs-coupon-message.error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #f5b9b9;
}

/* Coupon details animation */
@keyframes couponSlideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

#coupon-details {
    animation: couponSlideIn 0.3s ease-out;
}

/* Discount row animation */
@keyframes discountPulse {
    0% { background: #fef9e8; }
    50% { background: #fff0d6; }
    100% { background: #fef9e8; }
}

.crs-economic-discount {
    animation: discountPulse 2s ease-in-out infinite;
}

/* Responsive Styles */
@media (max-width: 640px) {
    .crs-coupon-input-group {
        flex-direction: column;
        gap: 10px !important;
    }
    
    .crs-coupon-input-group button {
        width: 100%;
    }
    
    .crs-coupon-details {
        flex-direction: column;
        align-items: flex-start !important;
    }
    
    #remove-coupon {
        margin-left: 0 !important;
        width: 100%;
        border-radius: 30px !important;
        height: 38px !important;
    }
    
    .crs-economic-discount {
        flex-direction: column;
        text-align: center;
        gap: 8px;
    }
}

@media (max-width: 768px) {
    .crs-economic-card {
        padding: 15px !important;
    }
    
    .crs-economic-row {
        flex-direction: column;
        gap: 5px;
        align-items: flex-start !important;
    }
    
    .crs-economic-value {
        align-self: flex-end;
    }
    
    .crs-economic-total {
        flex-direction: row !important;
    }
}

/* Hover effect on rows */
.crs-economic-row:hover {
    background: #f8fafc;
    transition: background 0.2s;
}

/* Input focus effect */
.crs-input:focus {
    outline: none;
    border-color: #4f46e5 !important;
    background: white !important;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}
</style>

<!-- Coupon JavaScript -->
<script>
    jQuery(document).ready(function($) {
        let originalSubtotal = <?php echo $subtotal; ?>;
        let currentTotal = <?php echo $total; ?>;
        let currentDiscount = <?php echo $discount_amount; ?>;
        
        // Apply coupon
        $('#apply-coupon').on('click', function() {
            const code = $('#coupon_code').val().trim().toUpperCase();
            if (!code) {
                showCouponMessage('Please enter a coupon code', 'error');
                return;
            }
            
            // Check if same coupon is already applied
            if (window.currentAppliedCoupon && window.currentAppliedCoupon.code === code) {
                showCouponMessage('This coupon is already applied', 'error');
                return;
            }
            
            $(this).prop('disabled', true).text('Applying...');
            
            console.log('Applying coupon:', code);
            console.log('Original subtotal:', originalSubtotal);
            
            $.ajax({
                url: crs_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'crs_validate_coupon',
                    code: code,
                    total: originalSubtotal,
                    nonce: crs_ajax.nonce
                },
                success: function(response) {
                    console.log('Coupon response:', response);
                    
                    if (response.success) {
                        // First, reset any existing coupon
                        if (window.currentAppliedCoupon) {
                            console.log('Removing existing coupon before applying new one');
                            resetCoupon();
                        }
                        
                        // Then apply the new coupon
                        applyCoupon(response.data);
                        showCouponMessage('Coupon applied successfully!', 'success');
                    } else {
                        console.log('Coupon error:', response.data);
                        showCouponMessage(response.data.message || 'Invalid coupon', 'error');
                        // Don't reset if coupon is invalid - keep existing if any
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    showCouponMessage('Failed to validate coupon', 'error');
                },
                complete: function() {
                    $('#apply-coupon').prop('disabled', false).text('Apply');
                }
            });
        });
        
        // Remove coupon
        $('#remove-coupon').on('click', function() {
            if (window.currentAppliedCoupon) {
                console.log('Removing coupon:', window.currentAppliedCoupon.code);
                resetCoupon();
                showCouponMessage('Coupon removed', 'success');
            } else {
                // Force reset even if no global tracking
                resetCoupon();
                showCouponMessage('Coupon removed', 'success');
            }
        });
        
        // Apply coupon data to UI (FIXED - with correct property names)
   // Apply coupon data to UI
function applyCoupon(data) {
    currentDiscount = data.discount;
    currentTotal = data.final_amount;
    
    // Create type badge text
    let typeBadgeText = '';
    if (data.coupon.discount_type === 'percentage') {
        typeBadgeText = data.coupon.discount_value + '% OFF';
    } else {
        typeBadgeText = '€' + data.coupon.discount_value + ' OFF';
    }
    
    // Update discount row
    $('#discount-row').show();
    $('#discount-amount').text('-€' + data.discount.toFixed(0));
    $('#total-amount').text('€' + data.final_amount.toFixed(0));
    
    // Update discount row badge
    $('#discount-row .crs-coupon-type-badge').text(typeBadgeText);
    
    // Update hidden fields
    $('#applied_coupon_id').val(data.coupon.id);
    $('#applied_coupon_code').val(data.coupon.code);
    $('#discount_amount').val(data.discount);
    $('#final_amount').val(data.final_amount);
    
    // Update coupon details
    $('#coupon-details').show();
    $('.crs-coupon-code').text(data.coupon.code);
    $('#coupon-type-badge').text(typeBadgeText);
    $('.crs-coupon-discount').text('-€' + data.discount.toFixed(0));
    
    // Update pay button
    $('.crs-pay-button').text('Pay €' + data.final_amount.toFixed(0));
    $('.crs-pay-button').data('total', data.final_amount);
    
    // Update coupon code input
    $('#coupon_code').val(data.coupon.code);
    
    // ========== IMPORTANT: Clear old coupon data from formData ==========
    // Remove any existing coupon data first
    if (typeof formData !== 'undefined') {
        // Clear old coupon data
        delete formData.applied_coupon_code;
        delete formData.applied_coupon_id;
        delete formData.discount_amount;
        delete formData.discount_type;
        delete formData.discount_value;
        
        // Set new coupon data
        formData.applied_coupon_code = data.coupon.code;
        formData.applied_coupon_id = data.coupon.id;
        formData.discount_amount = data.discount;
        formData.final_amount = data.final_amount;
        formData.subtotal_amount = originalSubtotal;
        formData.discount_type = data.coupon.discount_type;
        formData.discount_value = data.coupon.discount_value;
        
        // Save to localStorage
        if (typeof saveToLocalStorage === 'function') {
            saveToLocalStorage();
        }
        
        console.log('FormData updated with new coupon:', formData);
    }
    
    // Store current coupon in a global variable for tracking
    window.currentAppliedCoupon = {
        code: data.coupon.code,
        id: data.coupon.id,
        discount: data.discount,
        final_amount: data.final_amount,
        type: data.coupon.discount_type,
        value: data.coupon.discount_value
    };
}

    // Reset coupon - COMPLETELY REMOVE ALL COUPON DATA
    function resetCoupon() {
        currentDiscount = 0;
        currentTotal = originalSubtotal;
        
        // Hide discount row and remove type badge
        $('#discount-row').hide();
        $('#discount-row .crs-coupon-type-badge').remove();
        $('#total-amount').text('€' + originalSubtotal.toFixed(0));
        
        // ========== CRITICAL: Clear ALL hidden fields ==========
        $('#applied_coupon_id').val('');
        $('#applied_coupon_code').val('');
        $('#discount_amount').val('0');
        $('#final_amount').val(originalSubtotal);
        $('#subtotal_amount').val(originalSubtotal);
        
        // Hide coupon details
        $('#coupon-details').hide();
        
        // Reset pay button
        $('.crs-pay-button').text('Pay €' + originalSubtotal.toFixed(0));
        $('.crs-pay-button').data('total', originalSubtotal);
        
        // Clear coupon code input
        $('#coupon_code').val('');
        
        // ========== IMPORTANT: COMPLETELY REMOVE coupon data from formData ==========
        if (typeof formData !== 'undefined') {
            // Delete all coupon related data
            delete formData.applied_coupon_code;
            delete formData.applied_coupon_id;
            delete formData.discount_amount;
            delete formData.discount_type;
            delete formData.discount_value;
            
            // Reset amounts
            formData.final_amount = originalSubtotal;
            formData.subtotal_amount = originalSubtotal;
            
            // Save to localStorage
            if (typeof saveToLocalStorage === 'function') {
                saveToLocalStorage();
            }
            
            console.log('FormData after reset - coupon data removed:', formData);
        }
        
        // Clear global coupon tracking
        window.currentAppliedCoupon = null;
        
        console.log('Coupon removed, total reset to:', originalSubtotal);
    }






        
        // Show message
        function showCouponMessage(text, type) {
            const $msg = $('#coupon-message');
            $msg.text(text)
                .removeClass('success error')
                .addClass(type)
                .css({
                    'background': type === 'success' ? '#d1fae5' : '#fee2e2',
                    'color': type === 'success' ? '#065f46' : '#991b1b',
                    'border': '1px solid ' + (type === 'success' ? '#b8e6b9' : '#f5b9b9')
                })
                .show();
            
            setTimeout(function() {
                $msg.fadeOut();
            }, 3000);
        }
        
        // Toggle invoice fields
        $('#request_invoice').on('change', function() {
            if ($(this).is(':checked')) {
                $('.crs-invoice-fields').slideDown();
            } else {
                $('.crs-invoice-fields').slideUp();
            }
        });
    });
</script>


    <?php
    return ob_get_clean();
}




}