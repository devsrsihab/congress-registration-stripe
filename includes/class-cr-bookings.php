<?php
class CR_Bookings {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'cr_bookings';
        
        // Add hooks
        add_action('init', array($this, 'register_booking_post_status'));
        add_filter('manage_edit-congress_columns', array($this, 'add_congress_columns'));
        add_action('manage_congress_posts_custom_column', array($this, 'render_congress_columns'), 10, 2);
    }
    
    /**
     * Create a new booking
     */
    public function create_booking($data) {
        global $wpdb;
        
        $defaults = array(
            'booking_number' => $this->generate_booking_number(),
            'user_id' => get_current_user_id(),
            'congress_id' => 0,
            'registration_type' => 'personal',
            'third_person_name' => '',
            'third_person_email' => '',
            'selected_hotel_id' => 0,
            'check_in_date' => null,
            'check_out_date' => null,
            'meal_preferences' => '',
            'workshop_ids' => '',
            'additional_options' => '',
            'total_amount' => 0.00,
            'payment_status' => 'pending',
            'booking_status' => 'pending',
            'woocommerce_order_id' => 0
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Sanitize data
        $data['booking_number'] = sanitize_text_field($data['booking_number']);
        $data['third_person_name'] = sanitize_text_field($data['third_person_name']);
        $data['third_person_email'] = sanitize_email($data['third_person_email']);
        $data['meal_preferences'] = maybe_serialize($data['meal_preferences']);
        $data['workshop_ids'] = maybe_serialize($data['workshop_ids']);
        $data['additional_options'] = maybe_serialize($data['additional_options']);
        
        $result = $wpdb->insert(
            $this->table_name,
            $data,
            array(
                '%s', // booking_number
                '%d', // user_id
                '%d', // congress_id
                '%s', // registration_type
                '%s', // third_person_name
                '%s', // third_person_email
                '%d', // selected_hotel_id
                '%s', // check_in_date
                '%s', // check_out_date
                '%s', // meal_preferences
                '%s', // workshop_ids
                '%s', // additional_options
                '%f', // total_amount
                '%s', // payment_status
                '%s', // booking_status
                '%d'  // woocommerce_order_id
            )
        );
        
        if ($result) {
            $booking_id = $wpdb->insert_id;
            
            // Send notification
            $this->send_booking_notification($booking_id);
            
            return $booking_id;
        }
        
        return false;
    }
    
    /**
     * Update a booking
     */
    public function update_booking($booking_id, $data) {
        global $wpdb;
        
        // Sanitize data if needed
        if (isset($data['meal_preferences']) && is_array($data['meal_preferences'])) {
            $data['meal_preferences'] = maybe_serialize($data['meal_preferences']);
        }
        
        if (isset($data['workshop_ids']) && is_array($data['workshop_ids'])) {
            $data['workshop_ids'] = maybe_serialize($data['workshop_ids']);
        }
        
        return $wpdb->update(
            $this->table_name,
            $data,
            array('id' => $booking_id),
            $this->get_data_formats($data),
            array('%d')
        );
    }
    
    /**
     * Get a single booking
     */
    public function get_booking($booking_id) {
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $this->table_name WHERE id = %d",
            $booking_id
        ));
        
        if ($booking) {
            // Unserialize data
            $booking->meal_preferences = maybe_unserialize($booking->meal_preferences);
            $booking->workshop_ids = maybe_unserialize($booking->workshop_ids);
            $booking->additional_options = maybe_unserialize($booking->additional_options);
        }
        
        return $booking;
    }
    
    /**
     * Get booking by booking number
     */
    public function get_booking_by_number($booking_number) {
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $this->table_name WHERE booking_number = %s",
            $booking_number
        ));
        
        if ($booking) {
            // Unserialize data
            $booking->meal_preferences = maybe_unserialize($booking->meal_preferences);
            $booking->workshop_ids = maybe_unserialize($booking->workshop_ids);
            $booking->additional_options = maybe_unserialize($booking->additional_options);
        }
        
        return $booking;
    }
    
    /**
     * Get user bookings
     */
    public function get_user_bookings($user_id, $status = '') {
        global $wpdb;
        
        $where = "WHERE user_id = %d";
        $args = array($user_id);
        
        if (!empty($status)) {
            $where .= " AND booking_status = %s";
            $args[] = $status;
        }
        
        $bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $this->table_name $where ORDER BY created_at DESC",
            $args
        ));
        
        foreach ($bookings as $booking) {
            $booking->meal_preferences = maybe_unserialize($booking->meal_preferences);
            $booking->workshop_ids = maybe_unserialize($booking->workshop_ids);
            $booking->additional_options = maybe_unserialize($booking->additional_options);
        }
        
        return $bookings;
    }
    
    /**
     * Get congress bookings
     */
    public function get_congress_bookings($congress_id, $status = '') {
        global $wpdb;
        
        $where = "WHERE congress_id = %d";
        $args = array($congress_id);
        
        if (!empty($status)) {
            $where .= " AND booking_status = %s";
            $args[] = $status;
        }
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $this->table_name $where ORDER BY created_at DESC",
            $args
        ));
    }
    
    /**
     * Delete a booking
     */
    public function delete_booking($booking_id) {
        global $wpdb;
        
        // Delete associated documents first
        $documents_table = $wpdb->prefix . 'cr_documents';
        $wpdb->delete($documents_table, array('booking_id' => $booking_id), array('%d'));
        
        // Delete booking
        return $wpdb->delete($this->table_name, array('id' => $booking_id), array('%d'));
    }
    
    /**
     * Generate unique booking number
     */
    private function generate_booking_number() {
        $prefix = 'CR';
        $year = date('Y');
        $month = date('m');
        $random = wp_rand(1000, 9999);
        
        $booking_number = $prefix . '-' . $year . $month . '-' . $random;
        
        // Check if unique
        global $wpdb;
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $this->table_name WHERE booking_number = %s",
            $booking_number
        ));
        
        if ($exists > 0) {
            return $this->generate_booking_number(); // Recursive until unique
        }
        
        return $booking_number;
    }
    
    /**
     * Get data formats for wpdb update
     */
    private function get_data_formats($data) {
        $formats = array();
        
        foreach ($data as $key => $value) {
            switch ($key) {
                case 'user_id':
                case 'congress_id':
                case 'selected_hotel_id':
                case 'woocommerce_order_id':
                    $formats[] = '%d';
                    break;
                case 'total_amount':
                    $formats[] = '%f';
                    break;
                default:
                    $formats[] = '%s';
            }
        }
        
        return $formats;
    }
    
    /**
     * Send booking notification
     */
    private function send_booking_notification($booking_id) {
        $booking = $this->get_booking($booking_id);
        
        if (!$booking) {
            return;
        }
        
        $user = get_userdata($booking->user_id);
        $congress = get_post($booking->congress_id);
        
        // Email to admin
        $admin_email = get_option('admin_email');
        $subject = sprintf(__('New Booking: %s', CR_TEXT_DOMAIN), $booking->booking_number);
        
        $message = sprintf(
            __("A new booking has been created.\n\nBooking Number: %s\nUser: %s\nCongress: %s\nAmount: %s\nStatus: %s", CR_TEXT_DOMAIN),
            $booking->booking_number,
            $user->display_name,
            get_the_title($congress),
            wc_price($booking->total_amount),
            $booking->booking_status
        );
        
        wp_mail($admin_email, $subject, $message);
        
        // Email to user
        if ($user->user_email) {
            $user_subject = sprintf(__('Your Booking Confirmation: %s', CR_TEXT_DOMAIN), $booking->booking_number);
            
            $user_message = sprintf(
                __("Thank you for your booking!\n\nBooking Number: %s\nCongress: %s\nAmount: %s\nStatus: %s\n\nYou can view your booking details in your dashboard.", CR_TEXT_DOMAIN),
                $booking->booking_number,
                get_the_title($congress),
                wc_price($booking->total_amount),
                $booking->booking_status
            );
            
            wp_mail($user->user_email, $user_subject, $user_message);
        }
    }
    
    /**
     * Register booking post status (for future use)
     */
    public function register_booking_post_status() {
        register_post_status('booking-pending', array(
            'label' => _x('Pending', 'booking status', CR_TEXT_DOMAIN),
            'public' => false,
            'internal' => true,
        ));
        
        register_post_status('booking-confirmed', array(
            'label' => _x('Confirmed', 'booking status', CR_TEXT_DOMAIN),
            'public' => false,
            'internal' => true,
        ));
        
        register_post_status('booking-cancelled', array(
            'label' => _x('Cancelled', 'booking status', CR_TEXT_DOMAIN),
            'public' => false,
            'internal' => true,
        ));
    }
    
    /**
     * Add custom columns to congress post type
     */
    public function add_congress_columns($columns) {
        $columns['total_bookings'] = __('Total Bookings', CR_TEXT_DOMAIN);
        $columns['revenue'] = __('Revenue', CR_TEXT_DOMAIN);
        return $columns;
    }
    
    /**
     * Render custom columns for congress
     */
    public function render_congress_columns($column, $post_id) {
        global $wpdb;
        
        if ($column === 'total_bookings') {
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $this->table_name WHERE congress_id = %d AND booking_status = 'completed'",
                $post_id
            ));
            
            echo $count ?: 0;
        }
        
        if ($column === 'revenue') {
            $total = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(total_amount) FROM $this->table_name WHERE congress_id = %d AND payment_status = 'completed'",
                $post_id
            ));
            
            echo $total ? wc_price($total) : wc_price(0);
        }
    }
    
    /**
     * Update booking status based on WooCommerce order
     */
    public function update_booking_from_order($order_id) {
        global $wpdb;
        
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        // Find booking by order ID
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $this->table_name WHERE woocommerce_order_id = %d",
            $order_id
        ));
        
        if (!$booking) {
            return;
        }
        
        // Map order status to booking status
        $order_status = $order->get_status();
        $booking_status = 'pending';
        $payment_status = 'pending';
        
        switch ($order_status) {
            case 'completed':
                $booking_status = 'completed';
                $payment_status = 'completed';
                break;
            case 'processing':
                $booking_status = 'confirmed';
                $payment_status = 'processing';
                break;
            case 'on-hold':
                $booking_status = 'pending';
                $payment_status = 'pending';
                break;
            case 'cancelled':
                $booking_status = 'cancelled';
                $payment_status = 'cancelled';
                break;
            case 'refunded':
                $booking_status = 'cancelled';
                $payment_status = 'refunded';
                break;
            case 'failed':
                $booking_status = 'pending';
                $payment_status = 'failed';
                break;
        }
        
        // Update booking
        $this->update_booking($booking->id, array(
            'booking_status' => $booking_status,
            'payment_status' => $payment_status
        ));
        
        // If payment completed, generate documents
        if ($payment_status === 'completed') {
            $this->generate_booking_documents($booking->id);
        }
    }
    
    /**
     * Generate documents for booking
     */
    private function generate_booking_documents($booking_id) {
        $booking = $this->get_booking($booking_id);
        
        if (!$booking) {
            return;
        }
        
        // Generate invoice PDF
        $invoice_url = $this->generate_invoice($booking);
        
        // Generate registration confirmation
        $confirmation_url = $this->generate_confirmation($booking);
        
        // Save documents to database
        global $wpdb;
        $documents_table = $wpdb->prefix . 'cr_documents';
        
        if ($invoice_url) {
            $wpdb->insert(
                $documents_table,
                array(
                    'booking_id' => $booking_id,
                    'document_type' => 'invoice',
                    'document_url' => $invoice_url
                ),
                array('%d', '%s', '%s')
            );
        }
        
        if ($confirmation_url) {
            $wpdb->insert(
                $documents_table,
                array(
                    'booking_id' => $booking_id,
                    'document_type' => 'confirmation',
                    'document_url' => $confirmation_url
                ),
                array('%d', '%s', '%s')
            );
        }
    }
    
    /**
     * Generate invoice (placeholder - implement with PDF library)
     */
    private function generate_invoice($booking) {
        // This would use a PDF library like Dompdf or TCPDF
        // For now, return a placeholder
        return '';
    }
    
    /**
     * Generate confirmation document (placeholder)
     */
    private function generate_confirmation($booking) {
        // This would generate a confirmation PDF
        // For now, return a placeholder
        return '';
    }
    
    /**
     * Get booking statistics
     */
    public function get_statistics($congress_id = null) {
        global $wpdb;
        
        $where = '';
        $args = array();
        
        if ($congress_id) {
            $where = "WHERE congress_id = %d";
            $args[] = $congress_id;
        }
        
        // Total bookings
        $total_bookings = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $this->table_name $where",
            $args
        ));
        
        // Completed bookings
        $completed_bookings = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $this->table_name $where AND booking_status = 'completed'",
            $args
        ));
        
        // Pending bookings
        $pending_bookings = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $this->table_name $where AND booking_status = 'pending'",
            $args
        ));
        
        // Total revenue
        $total_revenue = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(total_amount) FROM $this->table_name $where AND payment_status = 'completed'",
            $args
        ));
        
        return array(
            'total' => intval($total_bookings),
            'completed' => intval($completed_bookings),
            'pending' => intval($pending_bookings),
            'revenue' => floatval($total_revenue)
        );
    }
    
    /**
     * Export bookings to CSV
     */
    public function export_to_csv($bookings) {
        $filename = 'bookings-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        fputcsv($output, array(
            'Booking Number',
            'User',
            'Congress',
            'Type',
            'Third Person',
            'Hotel',
            'Total Amount',
            'Booking Status',
            'Payment Status',
            'Created Date'
        ));
        
        // Data
        foreach ($bookings as $booking) {
            $user = get_userdata($booking->user_id);
            $congress = get_the_title($booking->congress_id);
            $hotel = $booking->selected_hotel_id ? get_the_title($booking->selected_hotel_id) : 'N/A';
            $third_person = $booking->registration_type === 'third_person' ? $booking->third_person_name : 'N/A';
            
            fputcsv($output, array(
                $booking->booking_number,
                $user ? $user->display_name : 'N/A',
                $congress,
                $booking->registration_type,
                $third_person,
                $hotel,
                $booking->total_amount,
                $booking->booking_status,
                $booking->payment_status,
                $booking->created_at
            ));
        }
        
        fclose($output);
        exit;
    }
}