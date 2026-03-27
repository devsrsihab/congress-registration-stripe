<?php
if (!defined('ABSPATH')) exit;

class CRS_Metaboxes {
    
    public static function addMetaBoxes() {
        add_meta_box(
            'crs_congress_details',
            __('Congress Details', 'crscngres'),
            [self::class, 'renderCongressDetails'],
            'congress',
            'normal',
            'high'
        );
        
        add_meta_box(
            'crs_congress_meals',
            __('Congress Meals', 'crscngres'),
            [self::class, 'renderCongressMeals'],
            'congress',
            'normal',
            'default'
        );
        
        add_meta_box(
            'crs_congress_workshops',
            __('Workshops', 'crscngres'),
            [self::class, 'renderCongressWorkshops'],
            'congress',
            'normal',
            'default'
        );
        
        add_meta_box(
            'crs_congress_pricing',
            __('Pricing Information', 'crscngres'),
            [self::class, 'renderCongressPricing'],
            'congress',
            'side',
            'default'
        );
        
        add_meta_box(
            'crs_hotel_details',
            __('Hotel Details', 'crscngres'),
            [self::class, 'renderHotelDetails'],
            'hotels',
            'normal',
            'high'
        );
    }
    
    public static function renderCongressDetails($post) {
        wp_nonce_field('crs_save_congress', 'crs_congress_nonce');
        $start_date = get_post_meta($post->ID, 'start_date', true);
        $end_date = get_post_meta($post->ID, 'end_date', true);
        $location = get_post_meta($post->ID, 'location', true);
        $registration_deadline = get_post_meta($post->ID, 'registration_deadline', true);
        include CRS_PLUGIN_DIR . 'templates/metaboxes/congress-details.php';
    }
    
    public static function renderCongressMeals($post) {
        $meals = get_post_meta($post->ID, 'congress_meals', true);
        if (!is_array($meals)) $meals = [];
        if (empty($meals)) {
            $meals[] = ['meal_title' => '', 'meal_type' => 'Meal', 'meal_date' => '', 'meal_price' => '', 'meal_status' => 'Enable'];
        }
        include CRS_PLUGIN_DIR . 'templates/metaboxes/congress-meals.php';
    }
    
    public static function renderCongressWorkshops($post) {
        $workshops = get_post_meta($post->ID, 'workshops', true);
        if (!is_array($workshops)) $workshops = [];
        if (empty($workshops)) {
            $workshops[] = ['workshop_title' => '', 'workshop_date' => '', 'start_time' => '', 'end_time' => '', 'total_seats_capacity' => ''];
        }
        include CRS_PLUGIN_DIR . 'templates/metaboxes/congress-workshops.php';
    }
    
    public static function renderCongressPricing($post) {
        $base_price = get_post_meta($post->ID, 'base_price', true);
        include CRS_PLUGIN_DIR . 'templates/metaboxes/congress-pricing.php';
    }
    
    public static function renderHotelDetails($post) {
        wp_nonce_field('crs_save_hotel', 'crs_hotel_nonce');
        $price_per_night = get_post_meta($post->ID, 'price_per_night', true);
        $address = get_post_meta($post->ID, 'address', true);
        $phone = get_post_meta($post->ID, 'phone', true);
        $email = get_post_meta($post->ID, 'email', true);
        $website = get_post_meta($post->ID, 'website', true);
        include CRS_PLUGIN_DIR . 'templates/metaboxes/hotel-details.php';
    }
    
    public static function saveCongressMeta($post_id) {
        if (!isset($_POST['crs_congress_nonce']) || !wp_verify_nonce($_POST['crs_congress_nonce'], 'crs_save_congress')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        
        $fields = ['start_date', 'end_date', 'location', 'registration_deadline', 'base_price'];
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        if (isset($_POST['meals']) && is_array($_POST['meals'])) {
            update_post_meta($post_id, 'congress_meals', $_POST['meals']);
        }
        
        if (isset($_POST['workshops']) && is_array($_POST['workshops'])) {
            update_post_meta($post_id, 'workshops', $_POST['workshops']);
        }
    }
    
    public static function saveHotelMeta($post_id) {
        if (!isset($_POST['crs_hotel_nonce']) || !wp_verify_nonce($_POST['crs_hotel_nonce'], 'crs_save_hotel')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        
        $fields = ['price_per_night', 'address', 'phone', 'email', 'website'];
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    }
}