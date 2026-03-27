<?php
class CR_Activator {
    
    public static function activate() {
        self::create_custom_tables();
        self::create_default_pages();
        flush_rewrite_rules();
    }
    
    private static function create_custom_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'cr_bookings';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            booking_number varchar(50) NOT NULL,
            user_id int(11) NOT NULL,
            congress_id int(11) NOT NULL,
            registration_type varchar(20) DEFAULT 'personal',
            third_person_name varchar(255),
            third_person_email varchar(100),
            selected_hotel_id int(11),
            check_in_date date,
            check_out_date date,
            meal_preferences text,
            workshop_ids text,
            additional_options text,
            total_amount decimal(10,2) DEFAULT 0.00,
            payment_status varchar(20) DEFAULT 'pending',
            booking_status varchar(20) DEFAULT 'pending',
            woocommerce_order_id int(11),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY congress_id (congress_id),
            KEY booking_status (booking_status),
            KEY payment_status (payment_status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Create documents table for future use
        $documents_table = $wpdb->prefix . 'cr_documents';
        $sql_documents = "CREATE TABLE IF NOT EXISTS $documents_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            booking_id int(11) NOT NULL,
            document_type varchar(50),
            document_url text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY booking_id (booking_id)
        ) $charset_collate;";
        
        dbDelta($sql_documents);
    }
    
    private static function create_default_pages() {
        // Create dashboard page
        $dashboard_page = array(
            'post_title'    => 'Congress Dashboard',
            'post_content'  => '[congress_dashboard]',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_name'     => 'congress-dashboard'
        );
        
        if (!get_page_by_path('congress-dashboard')) {
            wp_insert_post($dashboard_page);
        }
        
        // Create registration page
        $registration_page = array(
            'post_title'    => 'Congress Registration',
            'post_content'  => '[congress_registration_form]',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_name'     => 'congress-registration'
        );
        
        if (!get_page_by_path('congress-registration')) {
            wp_insert_post($registration_page);
        }
    }
}