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
     
    // ADD THIS METHOD
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
        if (!$booking_id) {
            echo '<div class="crs-error">Booking ID required</div>';
            return ob_get_clean();
        }
        
        global $wpdb;
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT cr_bookings.*, posts.post_title AS congress_name
            FROM {$wpdb->prefix}cr_bookings AS cr_bookings
            INNER JOIN {$wpdb->posts} AS posts ON cr_bookings.congress_id = posts.ID
            WHERE cr_bookings.id = %d
        ", $booking_id));
        
        if (!$booking) {
            echo '<div class="crs-error">Booking not found</div>';
            return ob_get_clean();
        }
        
        $user = get_userdata($booking->user_id);
        $additional = json_decode($booking->additional_options, true);
        
        // Include the template file
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
            "SELECT * FROM $bookings_table WHERE user_id = %d AND booking_status = 'completed' ORDER BY created_at DESC",
            $user_id
        ));
        
        $documents = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $documents_table WHERE user_id = %d ORDER BY created_at DESC",
            $user_id
        ));
        
        $customer_orders = wc_get_orders([
            'customer_id' => $user_id,
            'status' => ['completed', 'processing'],
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