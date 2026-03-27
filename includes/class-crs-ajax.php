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
        
        error_log('CRS: Creating payment intent - User ID: ' . $user_id);
        
        $total = CRS_Payment::calculateTotal($data);
        
        // Handle proof file upload
        $proof_file_data = $_POST['proof_file_data'] ?? '';
        $proof_file_name = $_POST['proof_file_name'] ?? '';
        $proof_file_id = 0;
        $proof_file_url = '';
        
        if (!empty($proof_file_data) && !empty($proof_file_name)) {
            $proof_file_id = CRS_Documents::uploadFromBase64($proof_file_data, $proof_file_name, $user_id);
            $proof_file_url = $proof_file_id ? wp_get_attachment_url($proof_file_id) : '';
        }
        
        // Create Stripe Payment Intent
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
        
        $result = CRS_Payment::confirmPayment($intent_id, $temp_booking_id);
        
        if ($result['success']) {
            wp_send_json_success($result['data']);
        } else {
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
                <div class=" coupon-section" style="background: #ffffff; border: 1px solid #e0e7ff; border-radius: 16px; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.05); transition: all 0.2s ease;">
                    
                    <!-- Header with modern icon -->
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px;">
                        <div style="background: #e0e7ff; border-radius: 8px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                            <span style="font-size: 18px; color: #4f46e5;">🏷️</span>
                        </div>
                        <h4 style="font-size: 16px; font-weight: 600; color: #1e293b; margin: 0; letter-spacing: -0.01em;"><?php _e('Have a coupon?', 'crscngres'); ?></h4>
                    </div>
                    
                    <!-- Input group - side by side on desktop, stacked on mobile -->
                    <div class="crs-coupon-input-group" style="display: flex; gap: 12px; align-items: center;">
                        <div style="flex: 1; position: relative;">
                            <input type="text" class="crs-input" id="coupon_code" 
                                placeholder="<?php _e('Enter coupon code', 'crscngres'); ?>" 
                                value="<?php echo isset($data['applied_coupon_code']) ? esc_attr($data['applied_coupon_code']) : ''; ?>"
                                style="width: 100%; padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; font-weight: 500; letter-spacing: 0.3px; transition: all 0.2s; background: #f8fafc;">
                        </div>
                        
                        <button type="button" class="crs-btn crs-btn-secondary" id="apply-coupon" 
                                style="padding: 12px 24px; background: #4f46e5; border: none; border-radius: 10px; font-weight: 600; font-size: 14px; color: white; cursor: pointer; transition: all 0.2s; white-space: nowrap; box-shadow: 0 2px 6px rgba(79, 70, 229, 0.2);">
                            <?php _e('Apply', 'crscngres'); ?>
                        </button>
                    </div>
                    
                    <!-- Message area -->
                    <div id="coupon-message" class="crs-coupon-message" style="display: none; margin-top: 12px; padding: 10px 14px; border-radius: 8px; font-size: 13px; font-weight: 500;"></div>
                    
                    <!-- Applied coupon details - clean design -->
                    <div id="coupon-details" class="crs-coupon-details" 
                        style="<?php echo $applied_coupon ? 'display: flex;' : 'display: none;'; ?> margin-top: 16px; padding: 14px 16px; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 10px; align-items: center; gap: 12px; flex-wrap: wrap;">
                        
                        <span style="color: #0369a1; font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 4px;">
                            <span style="font-size: 14px;">✓</span> 
                            <?php _e('Applied', 'crscngres'); ?>
                        </span>
                        
                        <span class="crs-coupon-code" style="background: #0369a1; color: white; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; letter-spacing: 0.5px;">
                            <?php echo $applied_coupon ? esc_html($applied_coupon->code) : ''; ?>
                        </span>
                        
                        <span class="crs-coupon-discount" style="background: #059669; color: white; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 600;">
                            -€<?php echo number_format($discount_amount, 0); ?>
                        </span>
                        
                        <button type="button" class="crs-coupon-remove" id="remove-coupon" 
                                style="margin-left: auto; background: transparent; border: none; font-size: 20px; cursor: pointer; color: #64748b; padding: 0 4px; line-height: 1; transition: color 0.2s;"
                                onmouseover="this.style.color='#dc2626'" 
                                onmouseout="this.style.color='#64748b'">
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
                            style="display: <?php echo $discount_amount > 0 ? 'flex' : 'none'; ?>; justify-content: space-between; padding: 10px 0; border-bottom: 1px dashed #e2e8f0; background: #f0f9ff; margin-top: 5px; border-radius: 6px;">
                            <span class="crs-economic-label" style="color: #059669; font-weight: 600; display: flex; align-items: center; gap: 5px;">
                                <span style="font-size: 16px;">🏷️</span>
                                <?php _e('Discount', 'crscngres'); ?>
                            </span>
                            <span class="crs-economic-value" id="discount-amount" style="color: #059669; font-weight: 700;">-€<?php echo number_format($discount_amount, 0); ?></span>
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

/* Responsive Styles */
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

/* Animation for discount row */
@keyframes discountPulse {
    0% { background: #f0f9ff; }
    50% { background: #dbeafe; }
    100% { background: #f0f9ff; }
}

.crs-economic-discount {
    animation: discountPulse 2s infinite;
}
/* Input focus effect */
.crs-input:focus {
    outline: none;
    border-color: #4f46e5 !important;
    background: white !important;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

/* Button hover effect */
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
    background: #f0fdf4;
    color: #166534;
    border: 1px solid #86efac;
}

.crs-coupon-message.error {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

/* Responsive design */
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
    
    .crs-coupon-remove {
        margin-left: 0 !important;
        align-self: flex-end;
    }
}

@media (min-width: 641px) and (max-width: 1024px) {
    .crs-coupon-input-group {
        flex-wrap: wrap;
    }
    
    .crs-coupon-input-group input {
        min-width: 250px;
    }
}
.crs-option-card.coupon-section.selected {
    display: block !important;
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
            
            $(this).prop('disabled', true).text('Applying...');
            
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
                    if (response.success) {
                        applyCoupon(response.data);
                        showCouponMessage('Coupon applied successfully!', 'success');
                    } else {
                        showCouponMessage(response.data.message || 'Invalid coupon', 'error');
                        resetCoupon();
                    }
                },
                error: function() {
                    showCouponMessage('Failed to validate coupon', 'error');
                },
                complete: function() {
                    $('#apply-coupon').prop('disabled', false).text('Apply');
                }
            });
        });
        
        // Remove coupon
        $('#remove-coupon').on('click', function() {
            resetCoupon();
            $('#coupon_code').val('').focus();
            showCouponMessage('Coupon removed', 'success');
        });
        
        // Apply coupon data to UI
        function applyCoupon(data) {
            currentDiscount = data.discount;
            currentTotal = data.final_amount;
            
            // Update discount row
            $('#discount-row').show();
            $('#discount-amount').text('-€' + data.discount.toFixed(0));
            $('#total-amount').text('€' + data.final_amount.toFixed(0));
            
            // Update hidden fields
            $('#applied_coupon_id').val(data.coupon.id);
            $('#applied_coupon_code').val(data.coupon.code);
            $('#discount_amount').val(data.discount);
            $('#final_amount').val(data.final_amount);
            
            // Update coupon details display
            $('#coupon-details').show();
            $('.crs-coupon-code').text(data.coupon.code);
            $('.crs-coupon-discount').text('-€' + data.discount.toFixed(0));
            
            // Update pay button
            $('.crs-pay-button').text('Pay €' + data.final_amount.toFixed(0));
            $('.crs-pay-button').data('total', data.final_amount);
        }
        
        // Reset coupon
        function resetCoupon() {
            currentDiscount = 0;
            currentTotal = originalSubtotal;
            
            $('#discount-row').hide();
            $('#total-amount').text('€' + originalSubtotal.toFixed(0));
            
            $('#applied_coupon_id').val('');
            $('#applied_coupon_code').val('');
            $('#discount_amount').val('0');
            $('#final_amount').val(originalSubtotal);
            
            $('#coupon-details').hide();
            
            $('.crs-pay-button').text('Pay €' + originalSubtotal.toFixed(0));
            $('.crs-pay-button').data('total', originalSubtotal);
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