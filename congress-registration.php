<?php
/**
 * Plugin Name: Congress Registration System
 * Description: Complete WooCommerce integration for congress registration
 * Version: 2.0.0
 * Author: Md. Sohanur Rohman Sihab
 * Text Domain: crscngres
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

define('CRS_VERSION', '2.0.0');
define('CRS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CRS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoloader for classes
spl_autoload_register(function ($class) {
    // Only autoload CRS_ classes
    if (strpos($class, 'CRS_') !== 0) {
        return;
    }
    
    // Convert class name to file name
    $class_name = str_replace('CRS_', '', $class);
    $file_name = 'class-' . str_replace('_', '-', strtolower($class_name)) . '.php';
    
    // Possible paths
    $paths = [
        CRS_PLUGIN_DIR . 'includes/' . $file_name,
        CRS_PLUGIN_DIR . 'includes/email/' . $file_name,
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Initialize plugin
function crs_init() {
    require_once CRS_PLUGIN_DIR . 'includes/class-crs-core.php';
    CRS_Core::getInstance();
}
add_action('plugins_loaded', 'crs_init');