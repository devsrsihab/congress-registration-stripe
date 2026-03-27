<?php
if (!defined('ABSPATH')) exit;

class CRS_Install {
    
    public static function createTables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Bookings table
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
            discount_amount decimal(10,2) DEFAULT 0.00,
            final_amount decimal(10,2) DEFAULT 0.00,
            applied_coupon_id int(11) DEFAULT NULL,
            payment_status varchar(20) DEFAULT 'pending',
            booking_status varchar(20) DEFAULT 'pending',
            woocommerce_order_id int(11),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY congress_id (congress_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Registration types table
        $types_table = $wpdb->prefix . 'registration_types';
        $sql_types = "CREATE TABLE IF NOT EXISTS $types_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            price decimal(10,2) NOT NULL,
            is_proof_require tinyint(1) DEFAULT 0,
            proof_file varchar(255) DEFAULT '',
            sort_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        dbDelta($sql_types);
        
        // Documents table
        $docs_table = $wpdb->prefix . 'cr_documents';
        $sql_docs = "CREATE TABLE IF NOT EXISTS $docs_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            booking_id int(11) NOT NULL,
            user_id int(11) NOT NULL,
            congress_id int(11) NOT NULL,
            document_type varchar(50),
            document_url text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY booking_id (booking_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        dbDelta($sql_docs);
        
        // NEW: Coupons table
        $coupons_table = $wpdb->prefix . 'cr_coupons';
        $sql_coupons = "CREATE TABLE IF NOT EXISTS $coupons_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            code varchar(50) NOT NULL,
            description text,
            discount_type enum('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
            discount_value decimal(10,2) NOT NULL,
            min_order_amount decimal(10,2) DEFAULT 0,
            max_discount_amount decimal(10,2) DEFAULT NULL,
            usage_limit int(11) DEFAULT NULL,
            usage_count int(11) DEFAULT 0,
            per_user_limit int(11) DEFAULT 1,
            start_date datetime DEFAULT NULL,
            expiry_date datetime DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_coupon_code (code),  /* Changed from 'code' to 'unique_coupon_code' */
            KEY idx_is_active (is_active)           /* Changed from 'is_active' to 'idx_is_active' */
        ) $charset_collate;";

        dbDelta($sql_coupons);

        // NEW: Coupon usage tracking table
        $usage_table = $wpdb->prefix . 'cr_coupon_usage';
        $sql_usage = "CREATE TABLE IF NOT EXISTS $usage_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            coupon_id int(11) NOT NULL,
            booking_id int(11) NOT NULL,
            user_id int(11) NOT NULL,
            discount_amount decimal(10,2) NOT NULL,
            original_amount decimal(10,2) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_coupon_id (coupon_id),    /* Changed from 'coupon_id' to 'idx_coupon_id' */
            KEY idx_booking_id (booking_id),  /* Changed from 'booking_id' to 'idx_booking_id' */
            KEY idx_user_id (user_id)         /* Changed from 'user_id' to 'idx_user_id' */
        ) $charset_collate;";

        dbDelta($sql_usage);
    
    }
}