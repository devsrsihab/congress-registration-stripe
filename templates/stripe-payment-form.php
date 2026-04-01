<?php
/**
 * Stripe Payment Form Template
 * templates/stripe-payment-form.php
 */

$temp_booking_id = isset($temp_booking_id) ? sanitize_text_field($temp_booking_id) : '';
$intent_id = isset($intent_id) ? sanitize_text_field($intent_id) : '';
$client_secret = isset($client_secret) ? sanitize_text_field($client_secret) : '';
$publishable_key = isset($publishable_key) ? sanitize_text_field($publishable_key) : '';

// Get temporary data
global $wpdb;
$temp_data = get_transient('crs_temp_booking_' . $temp_booking_id);

if (!$temp_data) {
    echo '<div class="crs-error">Session expired. Please try again.</div>';
    return;
}

$data = $temp_data['data'];
// FIXED: Use CRS_Payment::calculateTotal() instead of $this->
if (class_exists('CRS_Payment')) {
    $total = CRS_Payment::calculateTotal($data);
} else {
    // Fallback calculation if class not available
    $total = 0;
    if (!empty($data['total'])) {
        $total = floatval($data['total']);
    }
}



$amount = intval($total);
?>

<div class="crs-payment-section">
    <div class="crs-payment-header">
        <h3 class="crs-payment-title">Complete Your Payment</h3>
        <p class="crs-payment-subtitle">Secure payment powered by Stripe</p>
    </div>
    
    <!-- Order Summary - show only amount, not booking details -->
    <div class="crs-order-summary">
        <h4>Payment Summary</h4>
        
        <div class="crs-price-breakdown">
            <div class="crs-price-row">
                <span class="crs-price-label">Total Amount:</span>
                <span class="crs-price-value crs-total-value">€<?php echo number_format($amount, 0); ?></span>
            </div>
        </div>
        
        <p class="crs-note">Your booking will be created after successful payment.</p>
    </div>
    
    <!-- Stripe Payment Form -->
    <div class="crs-stripe-payment">
        <div id="stripe-payment-element" class="crs-stripe-element">
            <div class="crs-stripe-loading">Loading payment form...</div>
        </div>
        
        <div id="stripe-payment-message" class="crs-payment-message"></div>
        
        <div class="crs-payment-actions">
            <button type="button" id="stripe-submit-button" class="crs-btn crs-btn-primary" disabled>
                <span class="crs-btn-text">Pay €<?php echo number_format($amount, 0); ?></span>
                <span class="crs-spinner" style="display: none;"></span>
            </button>
            
            <button type="button" id="crs-cancel-payment" class="crs-btn crs-btn-secondary">
                Cancel
            </button>
        </div>
        
        <div class="crs-payment-security">
            <span class="crs-security-icon">🔒</span>
            <span>Payments are secure and encrypted</span>
        </div>
    </div>
</div>

<!-- Add this hidden field for the temp booking ID -->
<input type="hidden" id="crs_temp_booking_id" value="<?php echo esc_attr($temp_booking_id); ?>">

<script src="https://js.stripe.com/v3/"></script>

<script>
// Rest of your Stripe initialization code...
// Make sure to use the temp_booking_id in the success handler

function initStripePayment() {
    if (typeof Stripe === 'undefined') {
        setTimeout(initStripePayment, 100);
        return;
    }
    
    jQuery(document).ready(function($) {
        const clientSecret = '<?php echo esc_js($client_secret); ?>';
        const tempBookingId = $('#crs_temp_booking_id').val();
        
        if (!clientSecret) {
            $('#stripe-payment-element').html(
                '<div class="crs-stripe-error">Payment initialization failed. Please refresh the page.</div>'
            );
            return;
        }
        
        const stripe = Stripe('<?php echo esc_js($publishable_key); ?>');
        const elements = stripe.elements({
            clientSecret: clientSecret,
            appearance: {
                theme: 'stripe',
                variables: {
                    colorPrimary: '#059669',
                    colorBackground: '#ffffff',
                    colorText: '#1e293b',
                },
            },
        });
        
        const paymentElement = elements.create('payment');
        paymentElement.mount('#stripe-payment-element');
        $('#stripe-payment-element .crs-stripe-loading').fadeOut();
        
        paymentElement.on('ready', function() {
            $('#stripe-submit-button').prop('disabled', false);
        });
        
        $('#stripe-submit-button').on('click', async function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const $spinner = $btn.find('.crs-spinner');
            const $text = $btn.find('.crs-btn-text');
            const $message = $('#stripe-payment-message');
            
            $message.hide();
            $btn.prop('disabled', true);
            $text.hide();
            $spinner.show();
            
            try {
                const { error, paymentIntent } = await stripe.confirmPayment({
                    elements,
                    confirmParams: {
                        return_url: '<?php echo home_url('/account/'); ?>',
                    },
                    redirect: 'if_required'
                });
                
                if (error) {
                    $message
                        .text(error.message)
                        .addClass('crs-error-message')
                        .show();
                    
                    $btn.prop('disabled', false);
                    $text.show();
                    $spinner.hide();
                    
                } else if (paymentIntent && paymentIntent.status === 'succeeded') {
                    $message
                        .text('Payment successful! Creating your booking...')
                        .addClass('crs-success-message')
                        .show();
                    
                    // Confirm with server - use tempBookingId
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'crs_confirm_stripe_payment',
                            intent_id: '<?php echo esc_js($intent_id); ?>',
                            temp_booking_id: tempBookingId,
                            nonce: '<?php echo wp_create_nonce('crs_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                window.location.href = response.data.redirect;
                            } else {
                                $message
                                    .text('Payment successful but booking creation failed: ' + (response.data || 'Unknown error'))
                                    .addClass('crs-error-message')
                                    .show();
                                $btn.prop('disabled', false);
                                $text.show();
                                $spinner.hide();
                            }
                        },
                        error: function() {
                            $message
                                .text('Payment successful but booking creation failed. Please contact support.')
                                .addClass('crs-error-message')
                                .show();
                            $btn.prop('disabled', false);
                            $text.show();
                            $spinner.hide();
                        }
                    });
                }
            } catch (err) {
                console.error('Payment error:', err);
                $message
                    .text('An unexpected error occurred. Please try again.')
                    .addClass('crs-error-message')
                    .show();
                
                $btn.prop('disabled', false);
                $text.show();
                $spinner.hide();
            }
        });
        
        $('#crs-cancel-payment').on('click', function() {
            $('#crs-payment-container').slideUp(300, function() {
                $(this).empty();
                $('.crs-pay-button').fadeIn(300);
            });
        });
    });
}

initStripePayment();
</script>


<style>
/* Your existing CSS here */
.crs-payment-section {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 25px;
    margin-top: 20px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
    animation: crs-slideIn 0.5s ease-out;
}

@keyframes crs-slideIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.crs-payment-header {
    margin-bottom: 20px;
}

.crs-payment-title {
    font-size: 20px;
    font-weight: 600;
    color: #0f172a;
    margin: 0 0 5px 0;
}

.crs-payment-subtitle {
    color: #64748b;
    font-size: 14px;
    margin: 0;
}

.crs-order-summary {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 25px;
    border: 1px solid #e2e8f0;
}

.crs-order-summary h4 {
    margin: 0 0 15px 0;
    color: #1e293b;
    font-size: 16px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e2e8f0;
}

.crs-summary-details {
    margin-bottom: 15px;
}

.crs-summary-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    color: #475569;
    border-bottom: 1px dashed #f1f5f9;
}

.crs-summary-row:last-child {
    border-bottom: none;
}

.crs-summary-label {
    font-weight: 500;
    color: #64748b;
}

.crs-summary-value {
    color: #1e293b;
    font-weight: 500;
}

.crs-price-breakdown {
    background: #f8fafc;
    padding: 15px;
    border-radius: 8px;
    margin-top: 10px;
}

.crs-price-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
}

.crs-total-value {
    color: #059669;
    font-size: 20px;
    font-weight: 700;
}

.crs-stripe-payment {
    margin-top: 20px;
}

.crs-stripe-element {
    background: white;
    padding: 20px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 20px;
    min-height: 200px;
    transition: border-color 0.2s;
}

.crs-stripe-element:focus-within {
    border-color: #059669;
    box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
}

.crs-stripe-loading {
    text-align: center;
    color: #64748b;
    padding: 40px 20px;
}

.crs-stripe-error {
    text-align: center;
    color: #dc2626;
    padding: 40px 20px;
    background: #fee2e2;
    border-radius: 6px;
}

.crs-payment-message {
    padding: 15px;
    border-radius: 8px;
    margin: 15px 0;
    display: none;
    font-size: 14px;
    line-height: 1.5;
}

.crs-error-message {
    background: #fee2e2;
    border: 1px solid #fecaca;
    color: #991b1b;
}

.crs-success-message {
    background: #dcfce7;
    border: 1px solid #86efac;
    color: #166534;
}

.crs-payment-actions {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    margin: 20px 0;
}

.crs-btn {
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 160px;
}

.crs-btn-primary {
    background: #059669;
    color: white;
}

.crs-btn-primary:hover:not(:disabled) {
    background: #047857;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
}

.crs-btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background: #94a3b8;
}

.crs-btn-secondary {
    background: white;
    color: #475569;
    border: 1px solid #cbd5e1;
}

.crs-btn-secondary:hover {
    background: #f8fafc;
    border-color: #94a3b8;
}

.crs-payment-security {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 15px;
    background: #f8fafc;
    border-radius: 8px;
    color: #475569;
    font-size: 14px;
    border: 1px solid #e2e8f0;
}

.crs-security-icon {
    font-size: 18px;
}

.crs-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: crs-spin 0.8s linear infinite;
    vertical-align: middle;
}

@keyframes crs-spin {
    to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 640px) {
    .crs-payment-section {
        padding: 20px;
    }
    
    .crs-payment-actions {
        flex-direction: column;
    }
    
    .crs-btn {
        width: 100%;
        min-width: auto;
    }
    
    .crs-summary-row {
        flex-direction: column;
        gap: 5px;
    }
    
    .crs-summary-label {
        width: 100%;
    }
}
</style>