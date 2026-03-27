<?php
if (!defined('ABSPATH')) exit;

class CRS_Core {
    
    private static $instance = null;
    
    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->loadDependencies();
        $this->initHooks();
    }
    
    private function loadDependencies() {
        // Load all required classes
        $files = [
        'includes/class-crs-install.php',
        'includes/class-crs-admin.php',
        // 'includes/class-crs-metaboxes.php',
        'includes/class-crs-ajax.php',
        'includes/class-crs-payment.php',
        'includes/class-crs-shortcodes.php',
        'includes/class-crs-documents.php',
        'includes/email/class-crs-email-core.php',
        'includes/email/class-crs-email-settings.php',  // ADD THIS class-crs-email-settings
        'includes/class-crs-bookings-list.php',         // ADD THIS
        'includes/class-crs-registration-type.php',
        'includes/class-crs-bookings-list.php',
        'includes/class-crs-email.php',
        // 'templates/booking-details.php',
        'templates/my-documents.php',
        'templates/booking-confirmation.php',
        'includes/class-crs-steps.php',
        'includes/class-crs-coupon.php'


        ];
        
    
        foreach ($files as $file) {
            $path = CRS_PLUGIN_DIR . $file;

            
            if (file_exists($path)) {
                require_once $path;
            } 
        }
    }
    
    private function initHooks() {
        // Load text domain
        add_action('init', [$this, 'loadPluginTextDomain']);
        
        // Register shortcodes
        add_action('init', [$this, 'registerShortcodes']);
        
        // Enqueue assets
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        
        // Database creation
        add_action('init', [CRS_Install::class, 'createTables']);
        
        // Admin menus
        add_action('admin_menu', [CRS_Admin::class, 'addMenus']);
        add_filter('the_content', [$this, 'renderCongressShortcode']);
        // Meta boxes
        // add_action('add_meta_boxes', [CRS_Metaboxes::class, 'addMetaBoxes']);
        // add_action('save_post_congress', [CRS_Metaboxes::class, 'saveCongressMeta']);
        // add_action('save_post_hotels', [CRS_Metaboxes::class, 'saveHotelMeta']);
        
        // AJAX handlers
        CRS_Ajax::init();
        
        // Payment integration
        CRS_Payment::init();
        
        // Email
        CRS_Email_Core::init();
        
        // WooCommerce order meta box
        add_action('add_meta_boxes', [$this, 'addOrderMetaBox']);
    }
    
    public function loadPluginTextDomain() {
        load_plugin_textdomain(
            'crscngres',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }
    
    public function registerShortcodes() {
        CRS_Shortcodes::register();
    }
    
    public function enqueueAssets() {
        wp_enqueue_style('crs-frontend', CRS_PLUGIN_URL . 'assets/css/frontend.css', [], CRS_VERSION);
        wp_enqueue_style('crs-form', CRS_PLUGIN_URL . 'assets/css/form.css', [], CRS_VERSION);
        wp_enqueue_script('jquery');
        
        // ========== CACHE BUSTING FOR FRONTEND.JS ==========
        // Get the actual file modification time for cache busting
        $js_file_path = CRS_PLUGIN_DIR . 'assets/js/frontend.js';
        $js_version = CRS_VERSION;
        
        // If file exists, use its modification time as version
        if (file_exists($js_file_path)) {
            $js_version = filemtime($js_file_path);
        }
        
        // Enqueue with dynamic version
        wp_enqueue_script('crs-frontend', 
            CRS_PLUGIN_URL . 'assets/js/frontend.js', 
            ['jquery', 'wp-util'], 
            $js_version,  // This changes every time frontend.js is modified
            true
        );
        
        wp_localize_script('crs-frontend', 'crs_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('crs_nonce'),
            'user_logged_in' => is_user_logged_in(),
            'wc_ajax_url' => class_exists('WC_AJAX') ? WC_AJAX::get_endpoint('%%endpoint%%') : '',
            'checkout_nonce' => wp_create_nonce('woocommerce-process_checkout')
        ]);
    }
        
    private function enqueueWooCommerceAssets() {
        if (!class_exists('WooCommerce')) return;
        
        wp_enqueue_style('woocommerce-general');
        wp_enqueue_style('woocommerce-layout');
        wp_enqueue_script('wc-checkout');
        wp_enqueue_script('wc-credit-card-form');
        
        foreach (WC()->payment_gateways->get_available_payment_gateways() as $gateway) {
            if (method_exists($gateway, 'payment_scripts')) {
                $gateway->payment_scripts();
            }
        }
    }
    
    public function enqueueAdminAssets($hook) {
        if (strpos($hook, 'crs-') !== false || $hook == 'post.php' || $hook == 'post-new.php') {
            wp_enqueue_style('crs-admin', CRS_PLUGIN_URL . 'assets/css/admin-style.css', [], CRS_VERSION);
            wp_enqueue_script('crs-admin', CRS_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], CRS_VERSION, true);
        }
    }
    
    public function addOrderMetaBox() {
        if (!class_exists('WooCommerce')) return;
        
        $screen = class_exists('\Automattic\WooCommerce\Utilities\OrderUtil') && 
                  \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() 
                  ? wc_get_page_screen_id('shop-order') 
                  : 'shop_order';
        
        add_meta_box(
            'crs_registration_details',
            __('Congress Registration Details', 'crscngres'),
            [CRS_Admin::class, 'renderOrderMetaBox'],
            $screen,
            'normal',
            'high'
        );
    }

    public function renderCongressShortcode($content) {

        global $post;

        if ($post && $post->post_type === 'congress') {

            if (!empty($_GET['congress_id']) && !empty($_GET['step'])) {
                return do_shortcode('[congress_registration]');
            }
        }

        return $content;
    }
}