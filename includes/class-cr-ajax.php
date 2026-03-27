<?php
class CR_Ajax {
    
    public function __construct() {
        // Admin AJAX
        add_action('wp_ajax_cr_get_booking_details', array($this, 'get_booking_details'));
        add_action('wp_ajax_cr_update_booking', array($this, 'update_booking'));
        
        // Public AJAX
        add_action('wp_ajax_cr_check_availability', array($this, 'check_availability'));
        add_action('wp_ajax_nopriv_cr_check_availability', array($this, 'check_availability'));

        add_action('wp_ajax_crs_get_status_nonce', [$this, 'ajaxGetStatusNonce']);
    }

    // Add this method in your class
    public function ajaxGetStatusNonce() {
        check_ajax_referer('crs_ajax_nonce', 'nonce');
        
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
        
        if ($status && $booking_id) {
            $nonce = wp_create_nonce('mark_' . $status . '_' . $booking_id);
            wp_send_json_success(['nonce' => $nonce]);
        }
        
        wp_send_json_error('Invalid parameters');
    }
        
    public function get_booking_details() {
        // Verify nonce
        if (!check_ajax_referer('cr_admin_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $booking_id = intval($_POST['booking_id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'cr_bookings';
        
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $booking_id
        ));
        
        if ($booking) {
            // Decode JSON fields
            $booking->meal_preferences = $booking->meal_preferences ? json_decode($booking->meal_preferences) : array();
            $booking->workshop_ids = $booking->workshop_ids ? json_decode($booking->workshop_ids) : array();
            
            wp_send_json_success($booking);
        } else {
            wp_send_json_error('Booking not found');
        }
    }
    
    public function update_booking() {
        // Verify nonce
        if (!check_ajax_referer('cr_admin_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $booking_id = intval($_POST['booking_id']);
        $data = array(
            'booking_status' => sanitize_text_field($_POST['booking_status']),
            'payment_status' => sanitize_text_field($_POST['payment_status']),
            'total_amount' => floatval($_POST['total_amount'])
        );
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'cr_bookings';
        
        $result = $wpdb->update(
            $table_name,
            $data,
            array('id' => $booking_id),
            array('%s', '%s', '%f'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success('Booking updated successfully');
        } else {
            wp_send_json_error('Failed to update booking');
        }
    }
    
    public function check_availability() {
        // Verify nonce
        if (!check_ajax_referer('cr_public_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }
        
        $congress_id = intval($_POST['congress_id']);
        $check_date = sanitize_text_field($_POST['date']);
        
        // Check availability logic here
        $available = true;
        
        wp_send_json_success(array(
            'available' => $available,
            'message' => __('Available', CR_TEXT_DOMAIN)
        ));
    }
}