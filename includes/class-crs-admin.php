<?php
if (!defined('ABSPATH')) exit;

class CRS_Admin {
    
    public static function addMenus() {
        add_menu_page(
            __('Congress Registration', 'crscngres'),
            __('Congress Reg', 'crscngres'),
            'manage_options',
            'congress-registration',
            [self::class, 'renderDashboard'],
            'dashicons-calendar-alt',
            25
        );
        
        add_submenu_page(
            'congress-registration',
            __('Dashboard', 'crscngres'),
            __('Dashboard', 'crscngres'),
            'manage_options',
            'congress-registration',
            [self::class, 'renderDashboard']
        );
        
        add_submenu_page(
            'congress-registration',
            __('Bookings', 'crscngres'),
            __('Bookings', 'crscngres'),
            'manage_options',
            'crs-bookings',
            [self::class, 'renderBookingsPage']
        );
        
        add_submenu_page(
            'congress-registration',
            __('Registration Types', 'crscngres'),
            __('Registration Types', 'crscngres'),
            'manage_options',
            'crs-registration-types',
            [self::class, 'renderRegistrationTypesPage']
        );
        
        add_submenu_page(
            'congress-registration',
            __('Email Settings', 'crscngres'),
            __('Email', 'crscngres'),
            'manage_options',
            'crs-email-settings',
            [CRS_Email_Settings::class, 'renderPage']
        );
        
        add_submenu_page(
            'congress-registration',
            __('Settings', 'crscngres'),
            __('Settings', 'crscngres'),
            'manage_options',
            'crs-settings',
            [self::class, 'renderSettingsPage']
        );
        
        add_submenu_page(
            'congress-registration',
            __('Shortcodes', 'crscngres'),
            __('Shortcodes', 'crscngres'),
            'manage_options',
            'crs-shortcodes',
            [self::class, 'renderShortcodesPage']
        );
    }
    
    public static function renderDashboard() {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'cr_bookings';
        
        $total_congresses = wp_count_posts('congress')->publish;
        $total_hotels = wp_count_posts('hotels')->publish;
        $total_bookings = $wpdb->get_var("SELECT COUNT(*) FROM $bookings_table");
        $pending_bookings = $wpdb->get_var("SELECT COUNT(*) FROM $bookings_table WHERE booking_status = 'pending'");
        $completed_bookings = $wpdb->get_var("SELECT COUNT(*) FROM $bookings_table WHERE booking_status = 'confirmed'");
        $total_revenue = $wpdb->get_var("SELECT SUM(total_amount) FROM $bookings_table WHERE payment_status = 'confirmed'");
        
        include CRS_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }
    
    public static function renderBookingsPage() {
        $bookings_table = new CRS_Bookings_List_Table();

        // Check if we're viewing a single booking
        if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {

            include CRS_PLUGIN_DIR . 'templates/admin/booking-details-admin.php';
        } else {
            if (!class_exists('WP_List_Table')) {
                require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
            }
            $bookings_table->prepare_items();
            include CRS_PLUGIN_DIR . 'templates/admin/bookings-list.php';
        }
    }
    
    public static function renderRegistrationTypesPage() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'registration_types';
        
        if (isset($_POST['action']) && $_POST['action'] == 'add_type') {
            check_admin_referer('crs_add_registration_type');
            $wpdb->insert($table_name, [
                'name' => sanitize_text_field($_POST['name']),
                'price' => floatval($_POST['price']),
                'is_proof_require' => isset($_POST['is_proof_require']) ? 1 : 0,
                'sort_order' => intval($_POST['sort_order'])
            ]);
            echo '<div class="notice notice-success"><p>Registration type added.</p></div>';
        }
        
        if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
            check_admin_referer('crs_delete_registration_type');
            $wpdb->delete($table_name, ['id' => intval($_GET['id'])]);
            echo '<div class="notice notice-success"><p>Registration type deleted.</p></div>';
        }
        
        $types = $wpdb->get_results("SELECT * FROM $table_name ORDER BY sort_order ASC");
        include CRS_PLUGIN_DIR . 'templates/admin/registration-types.php';
    }
    
    public static function renderSettingsPage() {
        // Handle coupon actions (these are separate, not part of main settings)
        if (isset($_POST['coupon_action'])) {
            self::handleCouponActions();
        }
        
        // Handle main settings save
        if (isset($_POST['save_settings'])) {
            check_admin_referer('crs_save_settings');
            
            // Get which tab is being saved
            $settings_tab = isset($_POST['settings_tab']) ? sanitize_text_field($_POST['settings_tab']) : 'general';
            
            if ($settings_tab === 'general') {
                // Save general settings
                if (isset($_POST['currency'])) {
                    update_option('crs_currency', sanitize_text_field($_POST['currency']));
                }
                echo '<div class="notice notice-success"><p>General settings saved successfully.</p></div>';
            }
            
            if ($settings_tab === 'stripe') {
                // Save Stripe settings
                CRS_Payment::saveSettings();
                echo '<div class="notice notice-success"><p>Stripe settings saved successfully.</p></div>';
            }
        }
        
        // Get current values with proper defaults
        $currency = get_option('crs_currency', 'EUR');
        $test_mode = get_option('crs_stripe_test_mode', 1);
        $test_publishable = get_option('crs_stripe_test_publishable_key', '');
        $test_secret = get_option('crs_stripe_test_secret_key', '');
        $test_webhook = get_option('crs_stripe_test_webhook_secret', '');
        $live_publishable = get_option('crs_stripe_live_publishable_key', '');
        $live_secret = get_option('crs_stripe_live_secret_key', '');
        $live_webhook = get_option('crs_stripe_live_webhook_secret', '');
        $webhook_url = home_url('wp-admin/admin-ajax.php?action=crs_stripe_webhook');
        
        // Get coupons
        $coupon_obj = new CRS_Coupon();
        $coupons = $coupon_obj->get_all_coupons();
        
        include CRS_PLUGIN_DIR . 'templates/admin/settings.php';
    }

    private static function handleCouponActions() {
        check_admin_referer('crs_coupon_action');
        
        $coupon_obj = new CRS_Coupon();
        $action = $_POST['coupon_action'];
        
        if ($action === 'add' && isset($_POST['code'])) {
            $result = $coupon_obj->add_coupon($_POST);
            if ($result) {
                echo '<div class="notice notice-success"><p>Coupon added successfully!</p></div>';
            }
        }
        
        if ($action === 'edit' && isset($_POST['coupon_id'])) {
            $result = $coupon_obj->update_coupon($_POST['coupon_id'], $_POST);
            if ($result) {
                echo '<div class="notice notice-success"><p>Coupon updated successfully!</p></div>';
            }
        }
        
        if ($action === 'delete' && isset($_POST['coupon_id'])) {
            $result = $coupon_obj->delete_coupon($_POST['coupon_id']);
            if ($result) {
                echo '<div class="notice notice-success"><p>Coupon deleted successfully!</p></div>';
            }
        }
    }
    
    public static function renderShortcodesPage() {
        include CRS_PLUGIN_DIR . 'templates/admin/shortcodes.php';
    }
    
    public static function renderOrderMetaBox($post) {
        $order_id = is_a($post, 'WP_Post') ? $post->ID : (is_numeric($post) ? $post : $post->get_id());
        $order = wc_get_order($order_id);
        if (!$order) return;
        
        $booking_id = $order->get_meta('_crs_booking_id');
        $booking_number = $order->get_meta('_crs_booking_number');
        $congress_id = $order->get_meta('_crs_congress_id');
        $registration_data = $order->get_meta('_crs_registration_data');
        $hotel_id = $order->get_meta('_crs_hotel_id');
        $check_in = $order->get_meta('_crs_check_in');
        $check_out = $order->get_meta('_crs_check_out');
        $meals = $order->get_meta('_crs_meals');
        $workshops = $order->get_meta('_crs_workshops');
        $add_sidi = $order->get_meta('_crs_add_sidi');
        $request_invoice = $order->get_meta('_crs_request_invoice');
        $registration_type_id = $order->get_meta('_crs_registration_type_id');
        
        include CRS_PLUGIN_DIR . 'templates/admin/order-metabox.php';
    }
}