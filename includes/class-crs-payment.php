<?php
if (!defined('ABSPATH')) exit;
require_once CRS_PLUGIN_DIR . 'includes/stripe/init.php';

class CRS_Payment {
    
    public static function init() {
        add_action('admin_init', [self::class, 'registerSettings']);
        add_action('wp_ajax_nopriv_crs_stripe_webhook', [self::class, 'handleWebhook']);
        add_action('wp_ajax_crs_stripe_webhook', [self::class, 'handleWebhook']);
    }
    
    public static function registerSettings() {
        register_setting('crs_stripe_settings', 'crs_stripe_test_mode');
        register_setting('crs_stripe_settings', 'crs_stripe_test_publishable_key');
        register_setting('crs_stripe_settings', 'crs_stripe_test_secret_key');
        register_setting('crs_stripe_settings', 'crs_stripe_test_webhook_secret');
        register_setting('crs_stripe_settings', 'crs_stripe_live_publishable_key');
        register_setting('crs_stripe_settings', 'crs_stripe_live_secret_key');
        register_setting('crs_stripe_settings', 'crs_stripe_live_webhook_secret');
    }
    
    public static function saveSettings() {
        update_option('crs_currency', sanitize_text_field($_POST['currency']));
        update_option('crs_stripe_test_mode', isset($_POST['stripe_test_mode']) ? 1 : 0);
        update_option('crs_stripe_test_publishable_key', sanitize_text_field($_POST['stripe_test_publishable_key']));
        update_option('crs_stripe_test_secret_key', sanitize_text_field($_POST['stripe_test_secret_key']));
        update_option('crs_stripe_test_webhook_secret', sanitize_text_field($_POST['stripe_test_webhook_secret']));
        update_option('crs_stripe_live_publishable_key', sanitize_text_field($_POST['stripe_live_publishable_key']));
        update_option('crs_stripe_live_secret_key', sanitize_text_field($_POST['stripe_live_secret_key']));
        update_option('crs_stripe_live_webhook_secret', sanitize_text_field($_POST['stripe_live_webhook_secret']));
    }
    
    public static function getKeys() {
        $test_mode = get_option('crs_stripe_test_mode', 1);
        return $test_mode ? [
            'publishable' => get_option('crs_stripe_test_publishable_key', ''),
            'secret' => get_option('crs_stripe_test_secret_key', ''),
            'webhook_secret' => get_option('crs_stripe_test_webhook_secret', '')
        ] : [
            'publishable' => get_option('crs_stripe_live_publishable_key', ''),
            'secret' => get_option('crs_stripe_live_secret_key', ''),
            'webhook_secret' => get_option('crs_stripe_live_webhook_secret', '')
        ];
    }
    
    public static function createThirdPersonUser($data) {
        if (!isset($data['registration_type']) || $data['registration_type'] !== 'third_person') {
            return get_current_user_id();
        }
        
        $name = sanitize_text_field($data['third_person_name'] ?? '');
        $email = sanitize_email($data['third_person_email'] ?? '');
        $password = $data['third_person_password'] ?? '';
        
        if (empty($name) || empty($email) || empty($password)) {
            return get_current_user_id();
        }
        
        $user_id = email_exists($email);
        if ($user_id) return $user_id;
        
        $name_parts = explode(' ', $name, 2);
        $first_name = $name_parts[0];
        $last_name = $name_parts[1] ?? '';
        $username = sanitize_user(current(explode('@', $email)), true);
        
        $original_username = $username;
        $counter = 1;
        while (username_exists($username)) {
            $username = $original_username . $counter;
            $counter++;
        }
        
        $user_id = wp_create_user($username, $password, $email);
        
        if (!is_wp_error($user_id)) {
            wp_update_user([
                'ID' => $user_id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'display_name' => $name
            ]);
            $user = new WP_User($user_id);
            $user->set_role('subscriber');
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);
        }
        
        return $user_id;
    }
    
    public static function calculateTotal($data) {
        global $wpdb;
        $subtotal = 0;
        
        // Calculate subtotal (original price without discount)
        if (!empty($data['registration_type_id'])) {
            $type = $wpdb->get_row($wpdb->prepare(
                "SELECT price FROM {$wpdb->prefix}registration_types WHERE id = %d",
                $data['registration_type_id']
            ));
            $subtotal += $type ? $type->price : 0;
        }
        
        if (!empty($data['add_sidi'])) $subtotal += 150;
        
        if (!empty($data['hotel_id']) && $data['hotel_id'] != 0 && !empty($data['check_in_date']) && !empty($data['check_out_date'])) {
            $price_per_night = get_post_meta($data['hotel_id'], 'price_per_night', true);
            $nights = ceil((strtotime($data['check_out_date']) - strtotime($data['check_in_date'])) / (60 * 60 * 24));
            $subtotal += $price_per_night * $nights;
        }
        
        if (!empty($data['meals']) && !empty($data['congress_id'])) {
            $meals = get_post_meta($data['congress_id'], 'congress_meals', true);
            foreach ($data['meals'] as $meal_index) {
                if (isset($meals[$meal_index])) {
                    $subtotal += $meals[$meal_index]['meal_price'];
                }
            }
        }
        
        // Apply coupon discount if exists
        $total = $subtotal;
        
        // Check for coupon in data
        $coupon_code = '';
        if (!empty($data['applied_coupon_code'])) {
            $coupon_code = $data['applied_coupon_code'];
        } elseif (!empty($data['coupon_code'])) {
            $coupon_code = $data['coupon_code'];
        }
        
        if (!empty($coupon_code)) {
            if (!class_exists('CRS_Coupon')) {
                require_once CRS_PLUGIN_DIR . 'includes/class-crs-coupon.php';
            }
            $coupon_obj = new CRS_Coupon();
            $result = $coupon_obj->validate_coupon($coupon_code, $subtotal, get_current_user_id());
            
            if ($result['valid']) {
                $total = $result['final_amount'];
            }
        }
        
        return $total;
    }
    
    public static function storeTempBookingData($user_id, $data, $proof_file_id, $proof_file_url, $proof_file_name) {
        $temp_id = uniqid('temp_', true) . '_' . $user_id . '_' . time();
        
        $temp_data = [
            'user_id' => $user_id,
            'data' => $data,
            'proof_file_id' => $proof_file_id,
            'proof_file_url' => $proof_file_url,
            'proof_file_name' => $proof_file_name,
            'created_at' => time(),
            'registration_type' => isset($data['registration_type']) ? $data['registration_type'] : 'personal'
        ];
        
        set_transient('crs_temp_booking_' . $temp_id, $temp_data, 24 * HOUR_IN_SECONDS);
        
        error_log('CRS: Temp booking stored - ID: ' . $temp_id . ', User: ' . $user_id . ', Type: ' . $temp_data['registration_type']);
        
        return $temp_id;
    }
    
    public static function createPaymentIntent($total, $user_id, $data, $proof_file_id, $proof_file_url, $proof_file_name) {
        $keys = self::getKeys();
        
        if (empty($keys['secret'])) {
            return ['success' => false, 'error' => 'Stripe not configured'];
        }
        
        try {
            \Stripe\Stripe::setApiKey($keys['secret']);
            $currency = strtolower(get_option('crs_currency', 'EUR'));
            
            // Log the amount being sent to Stripe
            error_log('CRS: Creating Stripe Payment Intent - Amount: ' . round($total * 100) . ' ' . $currency . ', User: ' . $user_id);
            
            $temp_booking_id = self::storeTempBookingData($user_id, $data, $proof_file_id, $proof_file_url, $proof_file_name);
            
            $intent = \Stripe\PaymentIntent::create([
                'amount' => round($total * 100),
                'currency' => $currency,
                'metadata' => [
                    'user_id' => (string)$user_id,
                    'congress_id' => (string)($data['congress_id'] ?? 0),
                    'temp_booking_id' => $temp_booking_id,
                    'total_amount' => (string)$total
                ]
            ]);
            
            return ['success' => true, 'data' => [
                'clientSecret' => $intent->client_secret,
                'intentId' => $intent->id,
                'temp_booking_id' => $temp_booking_id,
                'publishableKey' => $keys['publishable'],
                'amount' => $total
            ]];
        } catch (Exception $e) {
            error_log('CRS: Stripe Payment Intent Error - ' . $e->getMessage());
            return ['success' => false, 'error' => 'Stripe error: ' . $e->getMessage()];
        }
    }
    
public static function confirmPayment($intent_id, $temp_booking_id) {
    $keys = self::getKeys();
    
    if (empty($keys['secret'])) {
        return ['success' => false, 'error' => 'Stripe not configured'];
    }
    
    \Stripe\Stripe::setApiKey($keys['secret']);
    
    try {
        $intent = \Stripe\PaymentIntent::retrieve($intent_id);
        
        if ($intent->status === 'succeeded') {
            $temp_data = get_transient('crs_temp_booking_' . $temp_booking_id);
            
            if (!$temp_data) {
                error_log('CRS: Temp booking data not found for ID: ' . $temp_booking_id);
                return ['success' => false, 'error' => 'Temporary booking data expired'];
            }
            
            error_log('CRS: Temp data found - User ID: ' . $temp_data['user_id'] . ', Type: ' . ($temp_data['data']['registration_type'] ?? 'personal'));
            
            $booking_id = self::createBookingAfterPayment(
                $temp_data['data'],
                $intent_id,
                $temp_data['proof_file_id'] ?? 0,
                $temp_data['proof_file_url'] ?? '',
                $temp_data['proof_file_name'] ?? '',
                $temp_data['user_id']
            );
            
            error_log('CRS: Booking created with ID: ' . $booking_id);
            
            if ($booking_id) {
                delete_transient('crs_temp_booking_' . $temp_booking_id);
                CRS_Documents::generateBookingDocuments($booking_id);
                
                global $wpdb;
                $booking_data = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}cr_bookings WHERE id = %d",
                    $booking_id
                ));
                
                do_action('crs_after_registration_complete', $booking_id, $booking_data);
                
                return ['success' => true, 'data' => [
                    'message' => 'Payment successful!',
                    'redirect' => home_url('/booking-confirmation/?booking=' . $booking_id),
                    'booking_id' => $booking_id
                ]];
            } else {
                error_log('CRS: Failed to create booking after payment');
                return ['success' => false, 'error' => 'Failed to create booking'];
            }
        } else {
            $temp_data = get_transient('crs_temp_booking_' . $temp_booking_id);
            if ($temp_data) {
                $failed_booking = (object)[
                    'id' => 0,
                    'user_id' => $temp_data['user_id'],
                    'booking_number' => 'TEMP-' . $temp_booking_id,
                    'congress_id' => $temp_data['data']['congress_id'] ?? 0,
                    'total_amount' => $intent->amount / 100,
                    'additional_options' => json_encode($temp_data['data'])
                ];
                do_action('crs_payment_failed', 0, $failed_booking);
            }
            error_log('CRS: Payment not confirmed - Status: ' . $intent->status);
            return ['success' => false, 'error' => 'Payment not confirmed'];
        }
    } catch (Exception $e) {
        error_log('CRS: Stripe error in confirmPayment - ' . $e->getMessage());
        return ['success' => false, 'error' => 'Stripe error: ' . $e->getMessage()];
    }
}
    
public static function createBookingAfterPayment($data, $intent_id, $proof_file_id, $proof_file_url, $proof_file_name, $user_id) {
    global $wpdb;
    $bookings_table = $wpdb->prefix . 'cr_bookings';
    
    $total = self::calculateTotal($data);
    
    // Calculate subtotal for record keeping
    $subtotal = $total;
    $discount_amount = 0;
    $applied_coupon_id = null;
    
    if (!empty($data['applied_coupon_code']) && !empty($data['applied_coupon_id'])) {
        $applied_coupon_id = intval($data['applied_coupon_id']);
        $discount_amount = floatval($data['discount_amount'] ?? 0);
        $subtotal = floatval($data['subtotal_amount'] ?? $total);
    }
    
    $booking_number = 'CR-' . time() . '-' . $user_id;
    
    error_log('CRS: Creating booking - Number: ' . $booking_number . ', User: ' . $user_id . ', Total: ' . $total);
    
    $meal_preferences = !empty($data['meals']) ? json_encode($data['meals']) : '';
    $workshop_ids = !empty($data['workshops']) ? json_encode($data['workshops']) : '';
    
    if (!empty($data['workshops']) && !empty($data['congress_id'])) {
        self::decreaseWorkshopSeats($data['congress_id'], $data['workshops']);
    }
    
    $diet_value = [];
    if (isset($data['diet'])) {
        $diet_value = is_array($data['diet']) ? $data['diet'] : (is_string($data['diet']) ? [$data['diet']] : []);
    }
    
    $allergy_value = 'no';
    if (isset($data['allergy'])) {
        $allergy_value = is_array($data['allergy']) ? (in_array('yes', $data['allergy']) ? 'yes' : 'no') : $data['allergy'];
    }
    
    $booking_data = [
        'booking_number' => $booking_number,
        'user_id' => $user_id,
        'congress_id' => intval($data['congress_id'] ?? 0),
        'registration_type' => sanitize_text_field($data['registration_type'] ?? 'personal'),
        'third_person_name' => sanitize_text_field($data['third_person_name'] ?? ''),
        'third_person_email' => sanitize_email($data['third_person_email'] ?? ''),
        'selected_hotel_id' => intval($data['hotel_id'] ?? 0),
        'check_in_date' => sanitize_text_field($data['check_in_date'] ?? ''),
        'check_out_date' => sanitize_text_field($data['check_out_date'] ?? ''),
        'meal_preferences' => $meal_preferences,
        'workshop_ids' => $workshop_ids,
        'total_amount' => floatval($total),
        'booking_status' => 'confirmed',
        'payment_status' => 'confirmed',
        'created_at' => current_time('mysql')
    ];
    
    $additional_options = [
        'personal_data' => [
            'first_name' => sanitize_text_field($data['first_name'] ?? ''),
            'last_name' => sanitize_text_field($data['last_name'] ?? ''),
            'id_number' => sanitize_text_field($data['id_number'] ?? ''),
            'phone' => sanitize_text_field($data['phone'] ?? ''),
            'address' => sanitize_text_field($data['address'] ?? ''),
            'location' => sanitize_text_field($data['location'] ?? ''),
            'postal_code' => sanitize_text_field($data['postal_code'] ?? ''),
            'country' => sanitize_text_field($data['country'] ?? ''),
            'province' => sanitize_text_field($data['province'] ?? ''),
            'work_center' => sanitize_text_field($data['work_center'] ?? ''),
            'email' => sanitize_email($data['email'] ?? '')
        ],
        'dietary_info' => [
            'diet' => $diet_value,
            'diet_other' => sanitize_text_field($data['diet_other'] ?? ''),
            'allergy' => $allergy_value,
            'allergy_details' => sanitize_textarea_field($data['allergy_details'] ?? '')
        ],
        'other_details' => [
            'image_release' => !empty($data['image_release']) ? 1 : 0,
            'observations' => sanitize_textarea_field($data['observations'] ?? ''),
            'free_communication' => sanitize_text_field($data['free_communication'] ?? '')
        ],
        'invoice_request' => !empty($data['request_invoice']) ? 1 : 0,
        'company_details' => [
            'company_name' => sanitize_text_field($data['company_name'] ?? ''),
            'tax_address' => sanitize_text_field($data['tax_address'] ?? ''),
            'cif' => sanitize_text_field($data['cif'] ?? ''),
            'invoice_phone' => sanitize_text_field($data['invoice_phone'] ?? ''),
            'invoice_email' => sanitize_email($data['invoice_email'] ?? '')
        ],
        'proof_file_id' => $proof_file_id,
        'proof_file_url' => $proof_file_url,
        'proof_file_name' => $proof_file_name,
        'registration_type_id' => sanitize_text_field($data['registration_type_id'] ?? ''),
        'add_sidi' => !empty($data['add_sidi']) ? 1 : 0,
        'accept_terms' => !empty($data['accept_terms']) ? 1 : 0,
        'stripe_payment_intent' => $intent_id,
        'applied_coupon_id' => $applied_coupon_id,
        'applied_coupon_code' => sanitize_text_field($data['applied_coupon_code'] ?? ''),
        'subtotal_amount' => $subtotal,
        'discount_amount' => $discount_amount
    ];
    
    $booking_data['additional_options'] = json_encode($additional_options);
    
    // Insert booking
    $result = $wpdb->insert($bookings_table, $booking_data);
    
    if (!$result) {
        error_log('CRS: Database insert failed - ' . $wpdb->last_error);
        return false;
    }
    
    $new_booking_id = $wpdb->insert_id;
    error_log('CRS: Booking created successfully with ID: ' . $new_booking_id);
    
    // Record coupon usage if coupon was applied
    if ($new_booking_id && $applied_coupon_id && $discount_amount > 0) {
        if (!class_exists('CRS_Coupon')) {
            require_once CRS_PLUGIN_DIR . 'includes/class-crs-coupon.php';
        }
        $coupon_obj = new CRS_Coupon();
        $coupon_obj->record_usage($applied_coupon_id, $new_booking_id, $user_id, $discount_amount, $subtotal);
        error_log('CRS: Coupon usage recorded - Coupon ID: ' . $applied_coupon_id . ', Booking ID: ' . $new_booking_id);
    }
    
    return $new_booking_id;
}
    
    private static function decreaseWorkshopSeats($congress_id, $selected_workshops) {
        $workshops = get_post_meta($congress_id, 'congress_workshop', true);
        if (!is_array($workshops)) return;
        
        $updated = false;
        foreach ($selected_workshops as $workshop_index) {
            $search_index = is_string($workshop_index) && strpos($workshop_index, 'item-') === 0 ? $workshop_index : $workshop_index;
        }
        
        if ($updated) {
            update_post_meta($congress_id, 'congress_workshop', $workshops);
        }
    }
    
    public static function saveBooking($data, $status = 'pending', $stripe_intent_id = '', $user_id = null) {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'cr_bookings';
        
        if (!$user_id) $user_id = get_current_user_id();

         // Check if third person user ID is provided in form data
        if (isset($data['third_person_user_id']) && !empty($data['third_person_user_id'])) {
            $user_id = intval($data['third_person_user_id']);
            error_log('CRS: Using third person user ID: ' . $user_id . ' (original: ' . get_current_user_id() . ')');
        }
        
        $booking_number = 'CR-' . time() . '-' . $user_id;
        $total = self::calculateTotal($data);
        
        // Calculate subtotal for record keeping
        $subtotal = $total;
        $discount_amount = 0;
        $applied_coupon_id = null;
        
        if (!empty($data['applied_coupon_code']) && !empty($data['applied_coupon_id'])) {
            $applied_coupon_id = intval($data['applied_coupon_id']);
            $discount_amount = floatval($data['discount_amount'] ?? 0);
            $subtotal = floatval($data['subtotal_amount'] ?? $total);
        }
        
        $proof_file_id = $proof_file_url = $proof_file_name = '';
        if (!empty($_FILES['proof_file'])) {
            $attachment_id = media_handle_upload('proof_file', 0);
            if (!is_wp_error($attachment_id)) {
                $proof_file_id = $attachment_id;
                $proof_file_url = wp_get_attachment_url($attachment_id);
                $proof_file_name = $_FILES['proof_file']['name'];
            }
        }
        
        $meal_preferences = !empty($data['meals']) ? json_encode($data['meals']) : '';
        $workshop_ids = !empty($data['workshops']) ? json_encode($data['workshops']) : '';
        
        $diet_value = [];
        if (isset($data['diet'])) {
            $diet_value = is_array($data['diet']) ? $data['diet'] : (is_string($data['diet']) ? [$data['diet']] : []);
        }
        
        $allergy_value = 'no';
        if (isset($data['allergy'])) {
            $allergy_value = is_array($data['allergy']) ? (in_array('yes', $data['allergy']) ? 'yes' : 'no') : $data['allergy'];
        }
        
        $booking_data = [
            'booking_number' => $booking_number,
            'user_id' => $user_id,
            'congress_id' => intval($data['congress_id'] ?? 0),
            'registration_type' => sanitize_text_field($data['registration_type'] ?? 'personal'),
            'third_person_name' => sanitize_text_field($data['third_person_name'] ?? ''),
            'third_person_email' => sanitize_email($data['third_person_email'] ?? ''),
            'selected_hotel_id' => intval($data['hotel_id'] ?? 0),
            'check_in_date' => sanitize_text_field($data['check_in_date'] ?? ''),
            'check_out_date' => sanitize_text_field($data['check_out_date'] ?? ''),
            'meal_preferences' => $meal_preferences,
            'workshop_ids' => $workshop_ids,
            'total_amount' => floatval($total),
            'booking_status' => $status,
            'payment_status' => 'pending',
            'created_at' => current_time('mysql')
        ];
        
        $additional_options = [
            'personal_data' => [
                'first_name' => sanitize_text_field($data['first_name'] ?? ''),
                'last_name' => sanitize_text_field($data['last_name'] ?? ''),
                'id_number' => sanitize_text_field($data['id_number'] ?? ''),
                'phone' => sanitize_text_field($data['phone'] ?? ''),
                'address' => sanitize_text_field($data['address'] ?? ''),
                'location' => sanitize_text_field($data['location'] ?? ''),
                'postal_code' => sanitize_text_field($data['postal_code'] ?? ''),
                'country' => sanitize_text_field($data['country'] ?? ''),
                'province' => sanitize_text_field($data['province'] ?? ''),
                'work_center' => sanitize_text_field($data['work_center'] ?? ''),
                'email' => sanitize_email($data['email'] ?? '')
            ],
            'dietary_info' => [
                'diet' => $diet_value,
                'diet_other' => sanitize_text_field($data['diet_other'] ?? ''),
                'allergy' => $allergy_value,
                'allergy_details' => sanitize_textarea_field($data['allergy_details'] ?? '')
            ],
            'other_details' => [
                'image_release' => !empty($data['image_release']) ? 1 : 0,
                'observations' => sanitize_textarea_field($data['observations'] ?? ''),
                'free_communication' => sanitize_text_field($data['free_communication'] ?? '')
            ],
            'invoice_request' => !empty($data['request_invoice']) ? 1 : 0,
            'company_details' => [
                'company_name' => sanitize_text_field($data['company_name'] ?? ''),
                'tax_address' => sanitize_text_field($data['tax_address'] ?? ''),
                'cif' => sanitize_text_field($data['cif'] ?? ''),
                'invoice_phone' => sanitize_text_field($data['invoice_phone'] ?? ''),
                'invoice_email' => sanitize_email($data['invoice_email'] ?? '')
            ],
            'proof_file_id' => $proof_file_id,
            'proof_file_url' => $proof_file_url,
            'proof_file_name' => $proof_file_name,
            'registration_type_id' => sanitize_text_field($data['registration_type_id'] ?? ''),
            'add_sidi' => !empty($data['add_sidi']) ? 1 : 0,
            'accept_terms' => !empty($data['accept_terms']) ? 1 : 0,
            'stripe_payment_intent' => $stripe_intent_id,
            // New coupon fields
            'applied_coupon_id' => $applied_coupon_id,
            'applied_coupon_code' => sanitize_text_field($data['applied_coupon_code'] ?? ''),
            'subtotal_amount' => $subtotal,
            'discount_amount' => $discount_amount
        ];
        
        $booking_data['additional_options'] = json_encode($additional_options);
        
        $result = $wpdb->insert($bookings_table, $booking_data);
        
        // Record coupon usage if coupon was applied (only for confirmed bookings)
        if ($result && $status === 'confirmed' && $applied_coupon_id && $discount_amount > 0) {
            if (!class_exists('CRS_Coupon')) {
                require_once CRS_PLUGIN_DIR . 'includes/class-crs-coupon.php';
            }
            $coupon_obj = new CRS_Coupon();
            $coupon_obj->record_usage($applied_coupon_id, $wpdb->insert_id, $user_id, $discount_amount, $subtotal);
        }
        
        return $result ? $wpdb->insert_id : false;
    }
    
    public static function handleWebhook() {
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        
        $keys = self::getKeys();
        $webhook_secret = $keys['webhook_secret'];
        
        if (empty($webhook_secret)) {
            http_response_code(400);
            exit();
        }
        
        \Stripe\Stripe::setApiKey($keys['secret']);
        
        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $webhook_secret);
        } catch (\Exception $e) {
            http_response_code(400);
            exit();
        }
        
        switch ($event->type) {
            case 'payment_intent.succeeded':
                self::handleSuccessfulPaymentWebhook($event->data->object->id);
                break;
            case 'payment_intent.payment_failed':
                self::handleFailedPaymentWebhook($event->data->object->id);
                break;
        }
        
        http_response_code(200);
    }
    
    private static function handleSuccessfulPaymentWebhook($intent_id) {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'cr_bookings';
        
        $bookings = $wpdb->get_results("SELECT id, additional_options FROM $bookings_table");
        foreach ($bookings as $booking) {
            $options = json_decode($booking->additional_options, true);
            if (!empty($options['stripe_payment_intent']) && $options['stripe_payment_intent'] === $intent_id) {
                $wpdb->update($bookings_table, [
                    'booking_status' => 'confirmed',
                    'payment_status' => 'confirmed'
                ], ['id' => $booking->id]);
                CRS_Documents::generateBookingDocuments($booking->id);
                break;
            }
        }
    }
    
    private static function handleFailedPaymentWebhook($intent_id) {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'cr_bookings';
        
        $bookings = $wpdb->get_results("SELECT id, additional_options FROM $bookings_table");
        foreach ($bookings as $booking) {
            $options = json_decode($booking->additional_options, true);
            if (!empty($options['stripe_payment_intent']) && $options['stripe_payment_intent'] === $intent_id) {
                $wpdb->update($bookings_table, ['payment_status' => 'failed'], ['id' => $booking->id]);
                
                $full_booking = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $bookings_table WHERE id = %d",
                    $booking->id
                ));
                do_action('crs_payment_failed', $booking->id, $full_booking);
                break;
            }
        }
    }
}