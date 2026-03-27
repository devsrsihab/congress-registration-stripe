<?php
if (!defined('ABSPATH')) exit;

class CRS_Coupon {
    
    private $wpdb;
    private $table;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . 'cr_coupons';
    }
    
    /**
     * Validate and apply coupon
     */
    public function validate_coupon($code, $total_amount, $user_id = null) {
        $coupon = $this->get_coupon_by_code($code);
        
        if (!$coupon) {
            return ['valid' => false, 'message' => 'Invalid coupon code'];
        }
        
        // Check if active
        if ($coupon->is_active != 1) {
            return ['valid' => false, 'message' => 'This coupon is inactive'];
        }
        
        // Check expiry
        $now = current_time('mysql');
        if ($coupon->expiry_date && $coupon->expiry_date < $now) {
            return ['valid' => false, 'message' => 'This coupon has expired'];
        }
        
        if ($coupon->start_date && $coupon->start_date > $now) {
            return ['valid' => false, 'message' => 'This coupon is not active yet'];
        }
        
        // Check minimum order
        if ($coupon->min_order_amount > 0 && $total_amount < $coupon->min_order_amount) {
            return [
                'valid' => false, 
                'message' => sprintf('Minimum order amount of €%s required', $coupon->min_order_amount)
            ];
        }
        
        // Check usage limit
        if ($coupon->usage_limit && $coupon->usage_count >= $coupon->usage_limit) {
            return ['valid' => false, 'message' => 'This coupon has reached its usage limit'];
        }
        
        // Check per-user limit
        if ($user_id && $coupon->per_user_limit > 0) {
            $user_usage = $this->get_user_usage_count($coupon->id, $user_id);
            if ($user_usage >= $coupon->per_user_limit) {
                return ['valid' => false, 'message' => 'You have already used this coupon'];
            }
        }
        
        // Calculate discount
        $discount = $this->calculate_discount($coupon, $total_amount);
        
        return [
            'valid' => true,
            'coupon' => $coupon,
            'discount' => $discount,
            'final_amount' => $total_amount - $discount
        ];
    }
    
    /**
     * Calculate discount amount
     */
    private function calculate_discount($coupon, $total_amount) {
        if ($coupon->discount_type === 'percentage') {
            $discount = ($total_amount * $coupon->discount_value) / 100;
            // Apply max discount limit if set
            if ($coupon->max_discount_amount && $discount > $coupon->max_discount_amount) {
                $discount = $coupon->max_discount_amount;
            }
        } else {
            $discount = $coupon->discount_value;
            // Don't allow discount to exceed total
            if ($discount > $total_amount) {
                $discount = $total_amount;
            }
        }
        
        return round($discount, 2);
    }
    
    /**
     * Get coupon by code
     */
    public function get_coupon_by_code($code) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE code = %s",
            $code
        ));
    }
    
    /**
     * Get coupon by ID
     */
    public function get_coupon($id) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE id = %d",
            $id
        ));
    }
    
    /**
     * Get all coupons
     */
    public function get_all_coupons() {
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->table} ORDER BY created_at DESC"
        );
    }
    
    /**
     * Add new coupon
     */
    public function add_coupon($data) {
        return $this->wpdb->insert($this->table, [
            'code' => strtoupper(sanitize_text_field($data['code'])),
            'description' => sanitize_textarea_field($data['description']),
            'discount_type' => $data['discount_type'],
            'discount_value' => floatval($data['discount_value']),
            'min_order_amount' => floatval($data['min_order_amount']),
            'max_discount_amount' => !empty($data['max_discount_amount']) ? floatval($data['max_discount_amount']) : null,
            'usage_limit' => !empty($data['usage_limit']) ? intval($data['usage_limit']) : null,
            'per_user_limit' => intval($data['per_user_limit']),
            'start_date' => !empty($data['start_date']) ? $data['start_date'] : null,
            'expiry_date' => !empty($data['expiry_date']) ? $data['expiry_date'] : null,
            'is_active' => isset($data['is_active']) ? 1 : 0
        ]);
    }
    
    /**
     * Update coupon
     */
    public function update_coupon($id, $data) {
        return $this->wpdb->update($this->table, [
            'code' => strtoupper(sanitize_text_field($data['code'])),
            'description' => sanitize_textarea_field($data['description']),
            'discount_type' => $data['discount_type'],
            'discount_value' => floatval($data['discount_value']),
            'min_order_amount' => floatval($data['min_order_amount']),
            'max_discount_amount' => !empty($data['max_discount_amount']) ? floatval($data['max_discount_amount']) : null,
            'usage_limit' => !empty($data['usage_limit']) ? intval($data['usage_limit']) : null,
            'per_user_limit' => intval($data['per_user_limit']),
            'start_date' => !empty($data['start_date']) ? $data['start_date'] : null,
            'expiry_date' => !empty($data['expiry_date']) ? $data['expiry_date'] : null,
            'is_active' => isset($data['is_active']) ? 1 : 0
        ], ['id' => $id]);
    }
    
    /**
     * Delete coupon
     */
    public function delete_coupon($id) {
        return $this->wpdb->delete($this->table, ['id' => $id]);
    }
    
    /**
     * Record coupon usage
     */
    public function record_usage($coupon_id, $booking_id, $user_id, $discount_amount, $original_amount) {
        $usage_table = $this->wpdb->prefix . 'cr_coupon_usage';
        
        // Insert usage record
        $this->wpdb->insert($usage_table, [
            'coupon_id' => $coupon_id,
            'booking_id' => $booking_id,
            'user_id' => $user_id,
            'discount_amount' => $discount_amount,
            'original_amount' => $original_amount,
            'created_at' => current_time('mysql')
        ]);
        
        // Increment usage count
        $this->wpdb->query($this->wpdb->prepare(
            "UPDATE {$this->table} SET usage_count = usage_count + 1 WHERE id = %d",
            $coupon_id
        ));
    }
    
    /**
     * Get user usage count for a coupon
     */
    private function get_user_usage_count($coupon_id, $user_id) {
        $usage_table = $this->wpdb->prefix . 'cr_coupon_usage';
        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM $usage_table WHERE coupon_id = %d AND user_id = %d",
            $coupon_id,
            $user_id
        ));
    }
}