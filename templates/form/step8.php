<?php 

global $wpdb;
$type_table = $wpdb->prefix . 'registration_types';
$coupon_table = $wpdb->prefix . 'cr_coupons';

$type = null;
if (isset($data['registration_type_id'])) {
    $type = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $type_table WHERE id = %d",
        $data['registration_type_id']
    ));
}

// Fix hotel data retrieval with proper checks
$hotel = null;
$hotel_name = '';
$price_per_night = 0;
$hotel_total = 0;
$nights = 0;
$check_in_display = '';
$check_out_display = '';

// Check if hotel_id exists and is valid
if (isset($data['hotel_id']) && !empty($data['hotel_id']) && $data['hotel_id'] != 0) {
    $hotel = get_post($data['hotel_id']);
    if ($hotel) {
        $hotel_name = get_the_title($hotel);
        $price_per_night = get_post_meta($hotel->ID, 'price_per_night', true);
        if (empty($price_per_night)) $price_per_night = 0;
        
        // IMPORTANT: Check if dates exist in the data array
        if (isset($data['check_in_date']) && !empty($data['check_in_date'])) {
            $check_in_display = $data['check_in_date'];
        }
        
        if (isset($data['check_out_date']) && !empty($data['check_out_date'])) {
            $check_out_display = $data['check_out_date'];
        }
        
        // Calculate nights and total if we have valid dates
        if (!empty($check_in_display) && !empty($check_out_display)) {
            $check_in_ts = strtotime($check_in_display);
            $check_out_ts = strtotime($check_out_display);
            
            if ($check_in_ts && $check_out_ts && $check_out_ts > $check_in_ts) {
                $nights = ceil(($check_out_ts - $check_in_ts) / (60 * 60 * 24));
                $hotel_total = floatval($price_per_night) * $nights;
            }
        }
    }
}

// Calculate totals
$registration_price = $type ? floatval($type->price) : 0;
$sidi_price = !empty($data['add_sidi']) ? 150 : 0;
$meals_total = 0;
$meals_list = array();

if (!empty($data['meals'])) {
    $meals = get_post_meta($congress_id, 'congress_meals', true);
    if (is_array($meals)) {
        foreach ($data['meals'] as $meal_index) {
            // Handle both string and array indices
            $meal_key = is_array($meal_index) ? key($meal_index) : $meal_index;
            if (isset($meals[$meal_key])) {
                $meals_total += floatval($meals[$meal_key]['meal_price']);
                $meals_list[] = $meals[$meal_key];
            }
        }
    }
}

// Get workshops
$workshops_list = array();
if (!empty($data['workshops'])) {
    $workshops = get_post_meta($congress_id, 'congress_workshop', true);
    if (is_array($workshops)) {
        foreach ($data['workshops'] as $workshop_index) {
            $workshop_key = is_array($workshop_index) ? key($workshop_index) : $workshop_index;
            if (isset($workshops[$workshop_key])) {
                $workshops_list[] = $workshops[$workshop_key];
            }
        }
    }
}

$subtotal = $registration_price + $sidi_price + $hotel_total + $meals_total;
$total = $subtotal;
$discount_amount = 0;
$applied_coupon = null;

// Check if coupon is applied (from session or previous selection)
if (isset($data['applied_coupon_code']) && !empty($data['applied_coupon_code'])) {
    if (!class_exists('CRS_Coupon')) {
        require_once CRS_PLUGIN_DIR . 'includes/class-crs-coupon.php';
    }
    $coupon_obj = new CRS_Coupon();
    $result = $coupon_obj->validate_coupon($data['applied_coupon_code'], $subtotal, get_current_user_id());
    
    if ($result['valid']) {
        $applied_coupon = $result['coupon'];
        $discount_amount = $result['discount'];
        $total = $result['final_amount'];
    }
}

ob_start();
?>
<div class="crs-step-content">
    <h2 class="crs-step-title"><?php _e('Summary and Confirmation wwww', 'crscngres'); ?></h2>
    <p class="crs-step-description"><?php _e('Please review your registration details before confirming.', 'crscngres'); ?></p>
    <div class="crs-summary-grid">
        <!-- Left Column - Details -->
        <div class="crs-summary-left">
            <!-- Personal Data -->
            <div class="crs-summary-card">
                <h3 class="crs-card-title"><?php _e('Personal Data', 'crscngres'); ?></h3>
                <div class="crs-data-list">
                    <div class="crs-data-row">
                        <span class="crs-data-label"><?php _e('Name:', 'crscngres'); ?></span>
                        <span class="crs-data-value"><?php echo isset($data['first_name']) ? esc_html($data['first_name'] . ' ' . ($data['last_name'] ?? '')) : ''; ?></span>
                    </div>
                    <div class="crs-data-row">
                        <span class="crs-data-label"><?php _e('DNI:', 'crscngres'); ?></span>
                        <span class="crs-data-value"><?php echo isset($data['id_number']) ? esc_html($data['id_number']) : ''; ?></span>
                    </div>
                    <div class="crs-data-row">
                        <span class="crs-data-label"><?php _e('Email:', 'crscngres'); ?></span>
                        <span class="crs-data-value"><?php echo isset($data['email']) ? esc_html($data['email']) : ''; ?></span>
                    </div>
                    <div class="crs-data-row">
                        <span class="crs-data-label"><?php _e('Telephone:', 'crscngres'); ?></span>
                        <span class="crs-data-value"><?php echo isset($data['phone']) ? esc_html($data['phone']) : ''; ?></span>
                    </div>
                    <div class="crs-data-row">
                        <span class="crs-data-label"><?php _e('Direction:', 'crscngres'); ?></span>
                        <span class="crs-data-value"><?php 
                            $address_parts = [];
                            if (!empty($data['address'])) $address_parts[] = $data['address'];
                            if (!empty($data['location'])) $address_parts[] = $data['location'];
                            if (!empty($data['postal_code'])) $address_parts[] = $data['postal_code'];
                            if (!empty($data['country'])) $address_parts[] = $data['country'];
                            echo esc_html(implode(', ', $address_parts));
                        ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Registration Type -->
            <?php if ($type): ?>
            <div class="crs-summary-card">
                <h3 class="crs-card-title"><?php _e('Registration Type', 'crscngres'); ?></h3>
                <div class="crs-data-row">
                    <span class="crs-data-label"><?php echo esc_html($type->name); ?></span>
                    <span class="crs-data-value"><?php printf(__('€%s', 'crscngres'), number_format($type->price, 0)); ?></span>
                </div>
                <?php if (!empty($data['add_sidi'])): ?>
                <div class="crs-data-row">
                    <span class="crs-data-label"><?php _e('+ SIDI Congress 2026', 'crscngres'); ?></span>
                    <span class="crs-data-value"><?php _e('€150', 'crscngres'); ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Accommodation -->
            <?php if ($hotel && !empty($check_in_display) && !empty($check_out_display) && $nights > 0): ?>
            <div class="crs-summary-card">
                <h3 class="crs-card-title"><?php _e('Accommodation', 'crscngres'); ?></h3>
                <div class="crs-data-row">
                    <span class="crs-data-label"><?php echo esc_html($hotel_name); ?></span>
                    <span class="crs-data-value"><?php printf(__('€%s', 'crscngres'), number_format($hotel_total, 0)); ?></span>
                </div>
                <div class="crs-hotel-dates">
                    <?php echo sprintf(_n('%s night', '%s nights', $nights, 'crscngres'), $nights); ?> (<?php echo $check_in_display; ?> - <?php echo $check_out_display; ?>)
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Lunches and Dinners -->
            <?php if (!empty($meals_list)): ?>
            <div class="crs-summary-card">
                <h3 class="crs-card-title"><?php _e('Lunches and Dinners', 'crscngres'); ?></h3>
                <?php foreach ($meals_list as $meal): ?>
                <div class="crs-data-row">
                    <span class="crs-data-label"><?php echo esc_html($meal['meal_title'] ?? $meal['title'] ?? __('Meal', 'crscngres')); ?></span>
                    <span class="crs-data-value"><?php printf(__('€%s', 'crscngres'), $meal['meal_price'] ?? $meal['price'] ?? 0); ?></span>
                </div>
                <?php endforeach; ?>
                <div class="crs-data-row crs-total-row">
                    <span class="crs-data-label"><?php _e('Total Meals', 'crscngres'); ?></span>
                    <span class="crs-data-value"><?php printf(__('€%s', 'crscngres'), $meals_total); ?></span>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Workshops -->
            <?php if (!empty($workshops_list)): ?>
            <div class="crs-summary-card">
                <h3 class="crs-card-title"><?php _e('Workshops', 'crscngres'); ?></h3>
                <?php foreach ($workshops_list as $workshop): ?>
                <div class="crs-data-row">
                    <span class="crs-data-label crs-checkmark">✓ <?php echo esc_html($workshop['workshop_title'] ?? $workshop['title'] ?? __('Workshop', 'crscngres')); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Right Column - Economic Summary -->
        <div class="crs-summary-right">
            <!-- Economic Summary Card -->
            <div class="crs-economic-card">
                <h3 class="crs-card-title"><?php _e('Economic Summary', 'crscngres'); ?></h3>
                
                <div class="crs-economic-row">
                    <span class="crs-economic-label"><?php _e('Registration', 'crscngres'); ?></span>
                    <span class="crs-economic-value" id="reg-price"><?php printf(__('€%s', 'crscngres'), number_format($registration_price, 0)); ?></span>
                </div>
                
                <?php if ($meals_total > 0): ?>
                <div class="crs-economic-row">
                    <span class="crs-economic-label"><?php _e('Food', 'crscngres'); ?></span>
                    <span class="crs-economic-value" id="meals-price"><?php printf(__('€%s', 'crscngres'), number_format($meals_total, 0)); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($hotel_total > 0): ?>
                <div class="crs-economic-row">
                    <span class="crs-economic-label"><?php _e('Accommodation', 'crscngres'); ?></span>
                    <span class="crs-economic-value" id="hotel-price"><?php printf(__('€%s', 'crscngres'), number_format($hotel_total, 0)); ?></span>
                </div>
                <?php endif; ?>
                
                <!-- Discount Row (shown only when coupon applied) -->
                <div class="crs-economic-row crs-economic-discount" id="discount-row" style="<?php echo $discount_amount > 0 ? 'display: flex;' : 'display: none;'; ?>">
                    <span class="crs-economic-label"><?php _e('Discount', 'crscngres'); ?></span>
                    <span class="crs-economic-value" id="discount-amount">-€<?php echo number_format($discount_amount, 0); ?></span>
                </div>
                
                <div class="crs-economic-row crs-economic-total">
                    <span class="crs-economic-label"><?php _e('Total', 'crscngres'); ?></span>
                    <span class="crs-economic-value" id="total-amount"><?php printf(__('€%s', 'crscngres'), number_format($total, 0)); ?></span>
                </div>
            </div>
            
            <!-- Coupon Section -->
            <div class="crs-option-card coupon-section">
                <h3 class="crs-card-title"><?php _e('Have a Coupon?', 'crscngres'); ?></h3>
                <div class="crs-coupon-input-group">
                    <input type="text" class="crs-input" id="coupon_code" placeholder="<?php _e('Enter coupon code', 'crscngres'); ?>" value="<?php echo isset($data['applied_coupon_code']) ? esc_attr($data['applied_coupon_code']) : ''; ?>">
                    <button type="button" class="crs-btn crs-btn-secondary" id="apply-coupon"><?php _e('Apply', 'crscngres'); ?></button>
                </div>
                <div id="coupon-message" class="crs-coupon-message" style="display: none;"></div>
                
                <!-- Applied Coupon Details -->
                <div id="coupon-details" class="crs-coupon-details" style="<?php echo $applied_coupon ? 'display: flex;' : 'display: none;'; ?>">
                    <span class="crs-coupon-label"><?php _e('Coupon Applied:', 'crscngres'); ?></span>
                    <span class="crs-coupon-code"><?php echo $applied_coupon ? esc_html($applied_coupon->code) : ''; ?></span>
                    <span class="crs-coupon-discount">-€<?php echo number_format($discount_amount, 0); ?></span>
                    <button type="button" class="crs-coupon-remove" id="remove-coupon">×</button>
                </div>
            </div>
            
            <!-- Request Invoice -->
            <div class="crs-option-card invoice_rqquest_section">
                <label class="crs-checkbox-label">
                    <input type="checkbox" name="request_invoice" id="request_invoice" <?php echo isset($data['request_invoice']) && $data['request_invoice'] ? 'checked' : ''; ?>>
                    <span class="crs-checkbox-custom"></span>
                    <span class="crs-checkbox-text"><?php _e('Request an invoice', 'crscngres'); ?></span>
                </label>
                
                <div class="crs-invoice-fields" style="display: <?php echo isset($data['request_invoice']) && $data['request_invoice'] ? 'block' : 'none'; ?>;">
                    <input type="text" class="crs-input" name="company_name" placeholder="<?php _e('Company name', 'crscngres'); ?>" value="<?php echo isset($data['company_name']) ? esc_attr($data['company_name']) : ''; ?>">
                    <input type="text" class="crs-input" name="tax_address" placeholder="<?php _e('Tax address', 'crscngres'); ?>" value="<?php echo isset($data['tax_address']) ? esc_attr($data['tax_address']) : ''; ?>">
                    <input type="text" class="crs-input" name="cif" placeholder="<?php _e('CIF', 'crscngres'); ?>" value="<?php echo isset($data['cif']) ? esc_attr($data['cif']) : ''; ?>">
                    <input type="tel" class="crs-input" name="invoice_phone" placeholder="<?php _e('Phone', 'crscngres'); ?>" value="<?php echo isset($data['invoice_phone']) ? esc_attr($data['invoice_phone']) : ''; ?>">
                    <input type="email" class="crs-input" name="invoice_email" placeholder="<?php _e('Email for invoice', 'crscngres'); ?>" value="<?php echo isset($data['invoice_email']) ? esc_attr($data['invoice_email']) : ''; ?>">
                </div>
            </div>
            
            <!-- Terms and Conditions -->
            <div class="crs-option-card term_and_conditions">
                <label class="crs-checkbox-label">
                    <input type="checkbox" name="accept_terms" id="accept_terms" <?php echo isset($data['accept_terms']) && $data['accept_terms'] ? 'checked' : ''; ?>>
                    <span class="crs-checkbox-custom"></span>
                    <span class="crs-checkbox-text"><?php printf(__('I have read and agree to the <a href="%s" target="_blank">Terms of Use</a>', 'crscngres'), '/terms-of-use'); ?></span>
                </label>
            </div>

            <!-- Payment Container (hidden initially) -->
            <div id="crs-payment-container" class="crs-payment-container"></div>

            <!-- Hidden fields for coupon data -->
            <input type="hidden" name="applied_coupon_id" id="applied_coupon_id" value="<?php echo $applied_coupon ? esc_attr($applied_coupon->id) : ''; ?>">
            <input type="hidden" name="applied_coupon_code" id="applied_coupon_code" value="<?php echo $applied_coupon ? esc_attr($applied_coupon->code) : ''; ?>">
            <input type="hidden" name="discount_amount" id="discount_amount" value="<?php echo $discount_amount; ?>">
            <input type="hidden" name="subtotal_amount" id="subtotal_amount" value="<?php echo $subtotal; ?>">
            <input type="hidden" name="final_amount" id="final_amount" value="<?php echo $total; ?>">
            
            <!-- Payment Button -->
            <button type="button" class="crs-pay-button" data-total="<?php echo $total; ?>" data-subtotal="<?php echo $subtotal; ?>">
                <?php printf(__('Pay €%s', 'crscngres'), number_format($total, 0)); ?>
            </button>
            
            <!-- Save without paying -->
            <button type="button" class="crs-save-button">
                <?php _e('Save without paying (pending)', 'crscngres'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Coupon JavaScript -->
<script>
jQuery(document).ready(function($) {
    let originalSubtotal = <?php echo $subtotal; ?>;
    let currentTotal = <?php echo $total; ?>;
    let currentDiscount = <?php echo $discount_amount; ?>;
    
    // Apply coupon
    $('#apply-coupon').on('click', function() {
        const code = $('#coupon_code').val().trim();
        if (!code) {
            showCouponMessage('Please enter a coupon code', 'error');
            return;
        }
        
        $(this).prop('disabled', true).text('Applying...');
        
        $.ajax({
            url: crs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'crs_validate_coupon',
                code: code,
                total: originalSubtotal,
                nonce: crs_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    applyCoupon(response.data);
                    showCouponMessage('Coupon applied successfully!', 'success');
                } else {
                    showCouponMessage(response.data.message || 'Invalid coupon', 'error');
                    resetCoupon();
                }
            },
            error: function() {
                showCouponMessage('Failed to validate coupon', 'error');
            },
            complete: function() {
                $('#apply-coupon').prop('disabled', false).text('Apply');
            }
        });
    });
    
    // Remove coupon
    $('#remove-coupon').on('click', function() {
        resetCoupon();
        $('#coupon_code').val('').focus();
        showCouponMessage('Coupon removed', 'success');
    });
    
    // Apply coupon data to UI
    function applyCoupon(data) {
        currentDiscount = data.discount;
        currentTotal = data.final_amount;
        
        // Update discount row
        $('#discount-row').show();
        $('#discount-amount').text('-€' + data.discount.toFixed(0));
        $('#total-amount').text('€' + data.final_amount.toFixed(0));
        
        // Update hidden fields
        $('#applied_coupon_id').val(data.coupon.id);
        $('#applied_coupon_code').val(data.coupon.code);
        $('#discount_amount').val(data.discount);
        $('#final_amount').val(data.final_amount);
        
        // Update coupon details display
        $('#coupon-details').show();
        $('.crs-coupon-code').text(data.coupon.code);
        $('.crs-coupon-discount').text('-€' + data.discount.toFixed(0));
        
        // Update pay button
        $('.crs-pay-button').text('Pay €' + data.final_amount.toFixed(0));
        $('.crs-pay-button').data('total', data.final_amount);
    }
    
    // Reset coupon
    function resetCoupon() {
        currentDiscount = 0;
        currentTotal = originalSubtotal;
        
        $('#discount-row').hide();
        $('#total-amount').text('€' + originalSubtotal.toFixed(0));
        
        $('#applied_coupon_id').val('');
        $('#applied_coupon_code').val('');
        $('#discount_amount').val('0');
        $('#final_amount').val(originalSubtotal);
        
        $('#coupon-details').hide();
        
        $('.crs-pay-button').text('Pay €' + originalSubtotal.toFixed(0));
        $('.crs-pay-button').data('total', originalSubtotal);
    }
    
    // Show message
    function showCouponMessage(text, type) {
        const $msg = $('#coupon-message');
        $msg.text(text)
            .removeClass('success error')
            .addClass(type)
            .show();
        
        setTimeout(function() {
            $msg.fadeOut();
        }, 3000);
    }
    
    // Toggle invoice fields
    $('#request_invoice').on('change', function() {
        if ($(this).is(':checked')) {
            $('.crs-invoice-fields').slideDown();
        } else {
            $('.crs-invoice-fields').slideUp();
        }
    });
});
</script>

<style>
/* Coupon Section Styles */
.coupon-section {
    margin-top: 20px;
    padding: 20px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
}

.crs-coupon-input-group {
    display: flex;
    gap: 10px;
    margin-top: 10px;
}

.crs-coupon-input-group input {
    flex: 1;
}

.crs-coupon-message {
    margin-top: 10px;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 13px;
}

.crs-coupon-message.success {
    background: #d1fae5;
    color: #065f46;
}

.crs-coupon-message.error {
    background: #fee2e2;
    color: #991b1b;
}

.crs-coupon-details {
    margin-top: 15px;
    padding: 12px;
    background: #e6f7e6;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.crs-coupon-label {
    color: #059669;
    font-weight: 500;
}

.crs-coupon-code {
    font-weight: 600;
    color: #059669;
    background: white;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 13px;
}

.crs-coupon-discount {
    background: #059669;
    color: white;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

.crs-coupon-remove {
    margin-left: auto;
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #666;
    padding: 0 8px;
    line-height: 1;
}

.crs-coupon-remove:hover {
    color: #dc2626;
}

.crs-economic-discount {
    color: #059669;
    font-weight: 500;
    border-top: 1px dashed #e2e8f0;
    margin-top: 5px;
    padding-top: 5px;
    display: flex;
    justify-content: space-between;
}
</style>
<?php
return ob_get_clean();