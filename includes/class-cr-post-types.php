<?php
class CR_Post_Types {
    
    public function __construct() {
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        add_filter('post_updated_messages', array($this, 'custom_post_messages'));
    }
    
    public function register_post_types() {
        // Register Congress Post Type
        $labels = array(
            'name'                  => _x('Congresses', 'Post type general name', CR_TEXT_DOMAIN),
            'singular_name'         => _x('Congress', 'Post type singular name', CR_TEXT_DOMAIN),
            'menu_name'             => _x('Congresses', 'Admin Menu text', CR_TEXT_DOMAIN),
            'add_new'               => __('Add New', CR_TEXT_DOMAIN),
            'add_new_item'          => __('Add New Congress', CR_TEXT_DOMAIN),
            'edit_item'             => __('Edit Congress', CR_TEXT_DOMAIN),
            'new_item'              => __('New Congress', CR_TEXT_DOMAIN),
            'view_item'             => __('View Congress', CR_TEXT_DOMAIN),
            'search_items'          => __('Search Congresses', CR_TEXT_DOMAIN),
            'not_found'             => __('No congresses found', CR_TEXT_DOMAIN),
            'not_found_in_trash'    => __('No congresses found in trash', CR_TEXT_DOMAIN),
        );
        
        $args = array(
            'labels'                => $labels,
            'public'                => true,
            'publicly_queryable'    => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'query_var'             => true,
            'rewrite'               => array('slug' => 'congress'),
            'capability_type'       => 'post',
            'has_archive'           => true,
            'hierarchical'          => false,
            'menu_position'         => 20,
            'menu_icon'             => 'dashicons-calendar-alt',
            'supports'              => array('title', 'editor', 'thumbnail', 'excerpt'),
            'show_in_rest'          => true,
        );
        
        register_post_type('congress', $args);
        
        // Register Hotels Post Type
        $hotel_labels = array(
            'name'                  => _x('Hotels', 'Post type general name', CR_TEXT_DOMAIN),
            'singular_name'         => _x('Hotel', 'Post type singular name', CR_TEXT_DOMAIN),
            'menu_name'             => _x('Hotels', 'Admin Menu text', CR_TEXT_DOMAIN),
            'add_new'               => __('Add New', CR_TEXT_DOMAIN),
            'add_new_item'          => __('Add New Hotel', CR_TEXT_DOMAIN),
            'edit_item'             => __('Edit Hotel', CR_TEXT_DOMAIN),
            'new_item'              => __('New Hotel', CR_TEXT_DOMAIN),
            'view_item'             => __('View Hotel', CR_TEXT_DOMAIN),
            'search_items'          => __('Search Hotels', CR_TEXT_DOMAIN),
            'not_found'             => __('No hotels found', CR_TEXT_DOMAIN),
            'not_found_in_trash'    => __('No hotels found in trash', CR_TEXT_DOMAIN),
        );
        
        $hotel_args = array(
            'labels'                => $hotel_labels,
            'public'                => true,
            'publicly_queryable'    => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'query_var'             => true,
            'rewrite'               => array('slug' => 'hotel'),
            'capability_type'       => 'post',
            'has_archive'           => true,
            'hierarchical'          => false,
            'menu_position'         => 21,
            'menu_icon'             => 'dashicons-building',
            'supports'              => array('title', 'editor', 'thumbnail'),
            'show_in_rest'          => true,
        );
        
        register_post_type('hotels', $hotel_args);
        

    }
    
    public function register_taxonomies() {
        // Register Congress Type Taxonomy
        register_taxonomy(
            'congress_type',
            'congress',
            array(
                'labels'            => array(
                    'name'              => _x('Congress Types', 'taxonomy general name', CR_TEXT_DOMAIN),
                    'singular_name'     => _x('Congress Type', 'taxonomy singular name', CR_TEXT_DOMAIN),
                ),
                'hierarchical'      => true,
                'public'            => true,
                'show_ui'           => true,
                'show_admin_column' => true,
                'show_in_rest'      => true,
            )
        );
    }
    
    public function custom_post_messages($messages) {
        $messages['congress'] = array(
            0  => '',
            1  => __('Congress updated.', CR_TEXT_DOMAIN),
            2  => __('Custom field updated.', CR_TEXT_DOMAIN),
            3  => __('Custom field deleted.', CR_TEXT_DOMAIN),
            4  => __('Congress updated.', CR_TEXT_DOMAIN),
            5  => __('Revision restored.', CR_TEXT_DOMAIN),
            6  => __('Congress published.', CR_TEXT_DOMAIN),
            7  => __('Congress saved.', CR_TEXT_DOMAIN),
            8  => __('Congress submitted.', CR_TEXT_DOMAIN),
        );
        
        return $messages;
    }
}