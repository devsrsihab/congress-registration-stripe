<?php
if (!defined('ABSPATH')) exit;

class CRS_Registration_Type {
    
    /**
     * Get all registration types
     */
    public static function get_all() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'registration_types';
        return $wpdb->get_results("SELECT * FROM $table_name ORDER BY sort_order ASC");
    }
    
    /**
     * Get single registration type by ID
     */
    public static function get($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'registration_types';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
    }
    
    /**
     * Add new registration type
     */
    public static function add($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'registration_types';
        
        return $wpdb->insert(
            $table_name,
            [
                'name' => sanitize_text_field($data['name']),
                'price' => floatval($data['price']),
                'is_proof_require' => isset($data['is_proof_require']) ? 1 : 0,
                'sort_order' => intval($data['sort_order'])
            ],
            ['%s', '%f', '%d', '%d']
        );
    }
    
    /**
     * Update registration type
     */
    public static function update($id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'registration_types';
        
        return $wpdb->update(
            $table_name,
            [
                'name' => sanitize_text_field($data['name']),
                'price' => floatval($data['price']),
                'is_proof_require' => isset($data['is_proof_require']) ? 1 : 0,
                'sort_order' => intval($data['sort_order'])
            ],
            ['id' => $id],
            ['%s', '%f', '%d', '%d'],
            ['%d']
        );
    }
    
    /**
     * Delete registration type
     */
    public static function delete($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'registration_types';
        return $wpdb->delete($table_name, ['id' => $id], ['%d']);
    }
    
    /**
     * Check if proof is required for a registration type
     */
    public static function is_proof_required($type_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'registration_types';
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT is_proof_require FROM $table_name WHERE id = %d",
            $type_id
        ));
        return $result == 1;
    }
    
    /**
     * Get registration type name by ID
     */
    public static function get_name($type_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'registration_types';
        return $wpdb->get_var($wpdb->prepare(
            "SELECT name FROM $table_name WHERE id = %d",
            $type_id
        ));
    }
    
    /**
     * Get registration type price by ID
     */
    public static function get_price($type_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'registration_types';
        $price = $wpdb->get_var($wpdb->prepare(
            "SELECT price FROM $table_name WHERE id = %d",
            $type_id
        ));
        return $price ? floatval($price) : 0;
    }
}