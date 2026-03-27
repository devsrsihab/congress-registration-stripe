<?php
/**
 * Payment Form Template - Professional WooCommerce Integration
 */
$order = wc_get_order($order_id);
if (!$order) {
    echo '<div class="crs-error">Invalid order</div>';
    return;
}

$total = $order->get_total();
$items = $order->get_items();
$booking_data = get_post_meta($order_id, '_crs_registration_data', true);
$data = json_decode($booking_data, true);

// Get available payment gateways
$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
$chosen_gateway = WC()->session ? WC()->session->get('chosen_payment_method') : '';

// If no gateway chosen, use the first available
if (empty($chosen_gateway) && !empty($available_gateways)) {
    $chosen_gateway = current($available_gateways)->id;
}
?>

<div class="crs-payment-section">
    <div class="crs-payment-header">
        <h3 class="crs-payment-title">Complete Your Payment</h3>
        <p class="crs-payment-subtitle">Please review your order and select a payment method</p>
    </div>
    
    <!-- Order Summary Card -->
    <div class="crs-order-summary-card">
        <div class="crs-summary-header">
            <h4>Order Summary</h4>
            <span class="crs-order-number">#<?php echo $order->get_order_number(); ?></span>
        </div>
        
        <div class="crs-summary-items">
            <?php 
            $total_registration = 0;
            $total_meals = 0;
            $total_hotel = 0;
            
            if ($data): 
                // Calculate totals from registration data
                if (!empty($data['registration_type_id'])) {
                    global $wpdb;
                    $type = $wpdb->get_row($wpdb->prepare(
                        "SELECT price FROM {$wpdb->prefix}registration_types WHERE id = %d",
                        $data['registration_type_id']
                    ));
                    if ($type) {
                        $total_registration = $type->price;
                    }
                }
                
                if (!empty($data['meals']) && !empty($data['congress_id'])) {
                    $meals = get_post_meta($data['congress_id'], 'congress_meals', true);
                    if (is_array($meals)) {
                        foreach ($data['meals'] as $meal_index) {
                            $meal_key = is_array($meal_index) ? key($meal_index) : $meal_index;
                            if (isset($meals[$meal_key])) {
                                $total_meals += floatval($meals[$meal_key]['meal_price']);
                            }
                        }
                    }
                }
                
                if (!empty($data['hotel_id']) && $data['hotel_id'] != 0 && !empty($data['check_in_date']) && !empty($data['check_out_date'])) {
                    $price_per_night = get_post_meta($data['hotel_id'], 'price_per_night', true);
                    $nights = ceil((strtotime($data['check_out_date']) - strtotime($data['check_in_date'])) / (60 * 60 * 24));
                    $total_hotel = $price_per_night * $nights;
                }
            endif;
            ?>
            
            <?php if ($total_registration > 0): ?>
            <div class="crs-item-row">
                <div class="crs-item-info">
                    <span class="crs-item-name">Congress Registration</span>
                    <?php if (!empty($data['add_sidi'])): ?>
                        <span class="crs-item-badge">+ SIDI 2026</span>
                    <?php endif; ?>
                </div>
                <span class="crs-item-price">€<?php echo number_format($total_registration, 0); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($total_meals > 0): ?>
            <div class="crs-item-row">
                <span class="crs-item-name">Meals & Dining</span>
                <span class="crs-item-price">€<?php echo number_format($total_meals, 0); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($total_hotel > 0): ?>
            <div class="crs-item-row">
                <span class="crs-item-name">Accommodation</span>
                <span class="crs-item-price">€<?php echo number_format($total_hotel, 0); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="crs-total-row">
            <span class="crs-total-label">Total Amount</span>
            <span class="crs-total-amount"><?php echo wc_price($total); ?></span>
        </div>
    </div>
    
    <!-- Payment Methods -->
    <form id="crs-payment-form" class="crs-payment-form" method="post">
        <?php if (!empty($available_gateways)): ?>
            <div class="crs-payment-methods">
                <h4 class="crs-methods-title">Payment Methods</h4>
                
                <ul class="crs-payment-methods-list">
                    <?php foreach ($available_gateways as $gateway): ?>
                        <li class="crs-payment-method payment_method_<?php echo esc_attr($gateway->id); ?>">
                            <label for="payment_method_<?php echo esc_attr($gateway->id); ?>" class="crs-method-label">
                                <input 
                                    type="radio" 
                                    class="crs-method-radio" 
                                    name="payment_method" 
                                    id="payment_method_<?php echo esc_attr($gateway->id); ?>" 
                                    value="<?php echo esc_attr($gateway->id); ?>" 
                                    <?php checked($gateway->id, $chosen_gateway); ?>
                                    data-order-button-text="<?php echo esc_attr($gateway->order_button_text); ?>"
                                />
                                <span class="crs-method-title"><?php echo $gateway->get_title(); ?></span>
                                <?php echo $gateway->get_icon(); ?>
                            </label>
                            
                            <?php if ($gateway->has_fields() || $gateway->get_description()): ?>
                                <div class="crs-payment-box payment_box payment_method_<?php echo esc_attr($gateway->id); ?>" 
                                     style="<?php echo $gateway->id !== $chosen_gateway ? 'display:none;' : ''; ?>">
                                    <?php
                                    // Display payment fields
                                    if ($gateway->has_fields()) {
                                        $gateway->payment_fields();
                                    } else {
                                        echo '<p>' . wp_kses_post($gateway->get_description()) . '</p>';
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="crs-payment-actions">
                <button type="button" class="crs-btn crs-btn-primary" id="crs-process-payment" data-order-id="<?php echo $order_id; ?>">
                    <span class="crs-btn-text">Place Order</span>
                </button>
                <button type="button" class="crs-btn crs-btn-secondary" id="crs-cancel-payment">
                    Cancel
                </button>
            </div>
        <?php else: ?>
            <div class="crs-no-gateways">
                <p class="crs-warning">No payment methods available. Please contact support.</p>
                <?php if (current_user_can('manage_options')): ?>
                    <p class="crs-admin-note">
                        <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout'); ?>" target="_blank">
                            Configure Payment Methods →
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </form>
</div>

<style>
/* Payment Section Styles */
.crs-payment-section {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 30px;
    margin: 20px 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
}

.crs-payment-header {
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e2e8f0;
}

.crs-payment-title {
    font-size: 24px;
    font-weight: 600;
    color: #0f172a;
    margin: 0 0 5px 0;
}

.crs-payment-subtitle {
    color: #64748b;
    margin: 0;
}

/* Order Summary Card */
.crs-order-summary-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 25px;
}

.crs-summary-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e2e8f0;
}

.crs-summary-header h4 {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.crs-order-number {
    font-size: 14px;
    font-weight: 500;
    color: #64748b;
}

.crs-summary-items {
    margin-bottom: 15px;
}

.crs-item-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px dashed #e2e8f0;
}

.crs-item-row:last-child {
    border-bottom: none;
}

.crs-item-info {
    display: flex;
    align-items: center;
    gap: 8px;
}

.crs-item-name {
    font-weight: 500;
    color: #1e293b;
}

.crs-item-badge {
    background: #f1f5f9;
    color: #475569;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 500;
}

.crs-item-price {
    font-weight: 600;
    color: #059669;
}

.crs-total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 15px;
    margin-top: 10px;
    border-top: 2px solid #e2e8f0;
}

.crs-total-label {
    font-size: 16px;
    font-weight: 500;
    color: #1e293b;
}

.crs-total-amount {
    font-size: 24px;
    font-weight: 700;
    color: #059669;
}

/* Payment Methods */
.crs-payment-methods {
    margin-bottom: 25px;
}

.crs-methods-title {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 15px 0;
}

.crs-payment-methods-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.crs-payment-method {
    margin-bottom: 15px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    background: white;
}

.crs-method-label {
    display: flex !important;
    align-items: center;
    padding: 15px 20px;
    background: #f8fafc;
    cursor: pointer;
    margin: 0 !important;
    font-weight: 500;
    color: #1e293b;
    transition: all 0.2s;
}

.crs-method-label:hover {
    background: #f1f5f9;
}

.crs-method-radio {
    margin-right: 15px !important;
    width: 18px;
    height: 18px;
    accent-color: #2271b1;
}

.crs-method-title {
    flex: 1;
    font-size: 15px;
}

.crs-payment-box {
    padding: 20px;
    background: #fff;
    border-top: 1px solid #e2e8f0;
}

/* Payment Box Fields */
.crs-payment-box p {
    margin: 0 0 10px 0;
    color: #475569;
}

.crs-payment-box input[type="text"],
.crs-payment-box input[type="email"],
.crs-payment-box input[type="tel"],
.crs-payment-box select {
    width: 100%;
    padding: 12px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    margin-bottom: 10px;
}

/* Stripe Specific */
.wc-stripe-elements-field {
    padding: 12px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background: #fff;
}

/* Payment Actions */
.crs-payment-actions {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    margin-top: 25px;
}

.crs-btn {
    padding: 14px 32px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.crs-btn-primary {
    background: #059669;
    color: white;
}

.crs-btn-primary:hover {
    background: #047857;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
}

.crs-btn-primary:disabled {
    background: #94a3b8;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.crs-btn-secondary {
    background: white;
    color: #475569;
    border: 1px solid #cbd5e1;
}

.crs-btn-secondary:hover {
    background: #f1f5f9;
}

/* No Gateways */
.crs-no-gateways {
    text-align: center;
    padding: 30px;
    background: #f8fafc;
    border-radius: 8px;
}

.crs-warning {
    color: #dc2626;
    margin: 0 0 10px 0;
}

.crs-admin-note a {
    color: #2271b1;
    text-decoration: none;
}

.crs-admin-note a:hover {
    text-decoration: underline;
}

/* Loading State */
.crs-spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: crs-spin 0.8s linear infinite;
    margin-right: 8px;
}

@keyframes crs-spin {
    to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
    .crs-payment-section {
        padding: 20px;
    }
    
    .crs-payment-actions {
        flex-direction: column;
    }
    
    .crs-btn {
        width: 100%;
    }
    
    .crs-method-label {
        flex-wrap: wrap;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Payment method switching
    $('.crs-method-radio').on('change', function() {
        var method = $(this).val();
        
        // Hide all payment boxes
        $('.crs-payment-box').slideUp(300);
        
        // Show selected payment box
        $('.payment_box.payment_method_' + method).slideDown(300);
        
        // Trigger Stripe elements to initialize if needed
        if (method === 'stripe' && typeof wc_stripe_upe_form !== 'undefined') {
            setTimeout(function() {
                $(document.body).trigger('wc_stripe_upe_form_loaded');
            }, 350);
        }
    });
    
    // Trigger change on page load for selected method
    $('.crs-method-radio:checked').trigger('change');
    
    // Process payment
    $('#crs-process-payment').on('click', function() {
        var $btn = $(this);
        var orderId = $btn.data('order-id');
        var paymentMethod = $('.crs-method-radio:checked').val();
        
        if (!paymentMethod) {
            alert('Please select a payment method');
            return;
        }
        
        // Disable button and show loading
        $btn.prop('disabled', true).html('<span class="crs-spinner"></span> Processing...');
        
        // Collect form data
        var formData = new FormData();
        formData.append('action', 'crs_process_payment');
        formData.append('order_id', orderId);
        formData.append('payment_method', paymentMethod);
        formData.append('nonce', crs_ajax.nonce);
        
        // Add payment method specific fields
        var $paymentBox = $('.payment_box.payment_method_' + paymentMethod);
        $paymentBox.find('input, select, textarea').each(function() {
            var $field = $(this);
            if ($field.attr('name') && !$field.is(':disabled')) {
                formData.append($field.attr('name'), $field.val());
            }
        });
        
        // Add credit card form fields if any
        if (paymentMethod === 'stripe') {
            var stripeData = $paymentBox.find('.wc-stripe-upe-element').data('stripe');
            if (stripeData) {
                formData.append('stripe_data', JSON.stringify(stripeData));
            }
        }
        
        // Send AJAX request
        $.ajax({
            url: crs_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success && response.data.redirect) {
                    window.location.href = response.data.redirect;
                } else {
                    alert('Payment failed: ' + (response.data || 'Unknown error'));
                    $btn.prop('disabled', false).html('Place Order');
                }
            },
            error: function(xhr, status, error) {
                console.error('Payment error:', error);
                alert('Payment processing failed. Please try again.');
                $btn.prop('disabled', false).html('Place Order');
            }
        });
    });
    
    // Cancel payment
    $('#crs-cancel-payment').on('click', function() {
        if (confirm('Are you sure you want to cancel?')) {
            $('#crs-payment-container').slideUp(300, function() {
                $(this).empty();
            });
        }
    });
});
</script>