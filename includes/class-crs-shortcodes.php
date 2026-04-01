<?php
if (!defined('ABSPATH')) exit;

class CRS_Shortcodes {
    
    public static function register() {
        add_shortcode('congress_registration', [self::class, 'renderRegistrationForm']);
        add_shortcode('my_registrations', [self::class, 'renderMyRegistrations']);
        add_shortcode('congress_list', [self::class, 'renderCongressList']);
        add_shortcode('crs_booking_details', [self::class, 'renderBookingDetails']); // ADD THIS
    add_shortcode('crs_test', [self::class, 'test_shortcode']);

    }


    // DEBUG FUNCTION - temporarily add this
    public static function test_shortcode() {
        return 'Shortcode is working!';
    }
        
    public static function renderBookingDetails($atts) {
        ob_start();
        
        $booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

        if (!$booking_id) {
            echo '<div class="crs-error-container">
                    <div class="crs-error-icon">🔍</div>
                    <h3>Booking ID Required</h3>
                    <p>Please provide a valid booking ID.</p>
                </div>';
            return ob_get_clean();
        }
        
        global $wpdb;
        
        // Get booking data
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT 
                b.*,
                p.post_title AS congress_name
            FROM {$wpdb->prefix}cr_bookings b
            LEFT JOIN {$wpdb->posts} p ON b.congress_id = p.ID
            WHERE b.id = %d
        ", $booking_id));
        
        if (!$booking) {
            echo '<div class="crs-error">Booking not found</div>';
            return ob_get_clean();
        }
        
        // Decode JSON fields
        $additional = !empty($booking->additional_options) ? json_decode($booking->additional_options, true) : [];
        $meal_preferences = !empty($booking->meal_preferences) ? json_decode($booking->meal_preferences, true) : [];
        $workshop_ids = !empty($booking->workshop_ids) ? json_decode($booking->workshop_ids, true) : [];
        
        // Get registration type
        $reg_type_name = '';
        $reg_type_price = 0;
        if (!empty($additional['registration_type_id'])) {
            $type = $wpdb->get_row($wpdb->prepare(
                "SELECT name, price FROM {$wpdb->prefix}registration_types WHERE id = %d",
                $additional['registration_type_id']
            ));
            if ($type) {
                $reg_type_name = $type->name;
                $reg_type_price = $type->price;
            }
        }
        
        // Get meals
        $meals_list = [];
        $meals_total = 0;
        if (!empty($meal_preferences) && $booking->congress_id) {
            $congress_meals = get_post_meta($booking->congress_id, 'congress_meals', true);
            if (is_array($congress_meals)) {
                foreach ($meal_preferences as $meal_index) {
                    if (isset($congress_meals[$meal_index])) {
                        $meals_list[] = $congress_meals[$meal_index];
                        $meals_total += floatval($congress_meals[$meal_index]['meal_price']);
                    }
                }
            }
        }
        
        // Get workshops
        $workshops_list = [];
        if (!empty($workshop_ids) && $booking->congress_id) {
            $congress_workshops = get_post_meta($booking->congress_id, 'congress_workshop', true);
            if (is_array($congress_workshops)) {
                foreach ($workshop_ids as $workshop_index) {
                    if (isset($congress_workshops[$workshop_index])) {
                        $workshops_list[] = $congress_workshops[$workshop_index];
                    }
                }
            }
        }
        
        // Calculate nights
        $nights = 0;
        $hotel_name = '';
        $hotel_total = 0;
        if ($booking->selected_hotel_id) {
            $hotel = get_post($booking->selected_hotel_id);
            if ($hotel) {
                $hotel_name = get_the_title($hotel);
                $price_per_night = get_post_meta($booking->selected_hotel_id, 'price_per_night', true);
                if ($booking->check_in_date && $booking->check_out_date) {
                    $nights = ceil((strtotime($booking->check_out_date) - strtotime($booking->check_in_date)) / (60 * 60 * 24));
                    $hotel_total = floatval($price_per_night) * $nights;
                }
            }
        }
        
        $dietary = isset($additional['dietary_info']) ? $additional['dietary_info'] : [];
        
        // Include template
        include CRS_PLUGIN_DIR . 'templates/booking-details.php';
        
        return ob_get_clean();
    }
    
    
    public static function renderRegistrationForm($atts) {
        $congress_id = isset($_GET['congress_id']) ? intval($_GET['congress_id']) : 0;
        if (!$congress_id) {
            return '<div class="crs-error">Please select a congress to register.</div>';
        }
        
        ob_start();
        include CRS_PLUGIN_DIR . 'templates/registration-form.php';
        return ob_get_clean();
    }
    
    public static function renderMyRegistrations() {
        if (!is_user_logged_in()) {
            return '<div class="crs-login-required">Please login to view your registrations.</div>';
        }
        
        $user_id = get_current_user_id();
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'cr_bookings';
        $documents_table = $wpdb->prefix . 'cr_documents';
        
        $registrations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $bookings_table WHERE user_id = %d AND booking_status = 'confirmed' ORDER BY created_at DESC",
            $user_id
        ));
        
        $documents = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $documents_table WHERE user_id = %d ORDER BY created_at DESC",
            $user_id
        ));
        
        $customer_orders = wc_get_orders([
            'customer_id' => $user_id,
            'status' => ['confirmed', 'processing'],
            'limit' => -1,
        ]);
        
        ob_start();
        include CRS_PLUGIN_DIR . 'templates/my-registrations.php';
        return ob_get_clean();
    }
    
    public static function renderCongressList($atts) {
        $atts = shortcode_atts([
            'limit' => -1,
            'show_past' => 'no'
        ], $atts);
        
        $args = [
            'post_type' => 'congress',
            'posts_per_page' => $atts['limit'],
            'post_status' => 'publish'
        ];
        
        if ($atts['show_past'] == 'no') {
            $args['meta_query'] = [[
                'key' => 'end_date',
                'value' => date('Y-m-d'),
                'compare' => '>=',
                'type' => 'DATE'
            ]];
        }
        
        $conferences = new WP_Query($args);
        
        ob_start();
        if ($conferences->have_posts()) {
            echo '<div class="crs-conference-grid">';
            while ($conferences->have_posts()) {
                $conferences->the_post();
                $start_date = get_post_meta(get_the_ID(), 'start_date', true);
                $end_date = get_post_meta(get_the_ID(), 'end_date', true);
                $location = get_post_meta(get_the_ID(), 'location', true);
                include CRS_PLUGIN_DIR . 'templates/congress-list-item.php';
            }
            echo '</div>';
            wp_reset_postdata();
        } else {
            echo '<p>No conferences available for registration.</p>';
        }
        return ob_get_clean();
    }
}