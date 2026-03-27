<?php if (!defined('ABSPATH')) exit; 

// Get current tab from URL
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
var_dump('see the currency value' . $currency);
?>

<!-- Add this CSS in your admin-style.css file or here -->
<style>
        /* Professional Coupon Management Styles */
        :root {
            --crs-primary: #2271b1;
            --crs-primary-dark: #135e96;
            --crs-success: #00a32a;
            --crs-warning: #faa754;
            --crs-danger: #dc3232;
            --crs-gray: #646970;
            --crs-light: #f0f0f1;
            --crs-border: #c3c4c7;
            --crs-card-bg: #ffffff;
            --crs-shadow: 0 2px 8px rgba(0,0,0,0.05);
            --crs-radius: 8px;
            --crs-radius-sm: 4px;
        }

        /* Coupon Card Styles */
        .crs-coupon-section {
            margin: 30px 0;
        }

        .crs-section-title {
            font-size: 24px;
            font-weight: 600;
            color: #1d2327;
            margin: 0 0 10px 0;
            padding-bottom: 15px;
            border-bottom: 3px solid var(--crs-primary);
        }

        .crs-section-subtitle {
            color: var(--crs-gray);
            margin-top: -5px;
            margin-bottom: 25px;
            font-size: 14px;
        }

        /* Form Card */
        .crs-form-card {
            background: var(--crs-card-bg);
            border: 1px solid var(--crs-border);
            border-radius: var(--crs-radius);
            padding: 30px;
            margin-bottom: 40px;
            box-shadow: var(--crs-shadow);
        }

        .crs-form-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .crs-form-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }

        .crs-form-header h3 {
            font-size: 20px;
            font-weight: 600;
            color: #1d2327;
            margin: 0 0 5px 0;
        }

        .crs-form-header p {
            color: var(--crs-gray);
            margin: 0;
            font-size: 13px;
        }

        /* Grid Layout */
        .crs-coupon-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
        }

        @media (max-width: 782px) {
            .crs-coupon-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }

        .crs-full-width {
            grid-column: span 2;
        }

        @media (max-width: 782px) {
            .crs-full-width {
                grid-column: span 1;
            }
        }

        /* Form Fields */
        .crs-field-group {
            margin-bottom: 5px;
        }

        .crs-field-label {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 8px;
            font-weight: 500;
            color: #1d2327;
        }

        .crs-field-label .dashicons {
            color: var(--crs-primary);
            font-size: 16px;
            width: 16px;
            height: 16px;
        }

        .crs-field-input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--crs-border);
            border-radius: var(--crs-radius-sm);
            font-size: 14px;
            transition: all 0.2s;
            background: #fff;
        }

        .crs-field-input:focus {
            border-color: var(--crs-primary);
            box-shadow: 0 0 0 1px var(--crs-primary);
            outline: none;
        }

        .crs-field-input:hover {
            border-color: #135e96;
        }

        .crs-field-textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--crs-border);
            border-radius: var(--crs-radius-sm);
            font-size: 14px;
            transition: all 0.2s;
            min-height: 80px;
            resize: vertical;
        }

        .crs-field-select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--crs-border);
            border-radius: var(--crs-radius-sm);
            font-size: 14px;
            background: #fff;
            cursor: pointer;
        }

        .crs-field-hint {
            color: var(--crs-gray);
            font-size: 12px;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .crs-field-hint .dashicons {
            font-size: 14px;
            width: 14px;
            height: 14px;
        }

        /* Checkbox Styles */
        .crs-checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background: var(--crs-light);
            border-radius: var(--crs-radius-sm);
            border: 1px solid var(--crs-border);
        }

        .crs-checkbox-wrapper input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin: 0;
            cursor: pointer;
        }

        .crs-checkbox-wrapper label {
            font-weight: 500;
            color: #1d2327;
            cursor: pointer;
            flex: 1;
        }

        /* Submit Button */
        .crs-submit-wrapper {
            grid-column: span 2;
            margin-top: 20px;
            text-align: right;
        }

        @media (max-width: 782px) {
            .crs-submit-wrapper {
                grid-column: span 1;
                text-align: center;
            }
        }

        .crs-btn-primary {
            background: var(--crs-primary);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: var(--crs-radius-sm);
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .crs-btn-primary:hover {
            background: var(--crs-primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(34,113,177,0.2);
        }

        .crs-btn-primary .dashicons {
            font-size: 18px;
            width: 18px;
            height: 18px;
        }

        /* Coupons Table */
        .crs-table-card {
            background: var(--crs-card-bg);
            border: 1px solid var(--crs-border);
            border-radius: var(--crs-radius);
            padding: 25px;
            box-shadow: var(--crs-shadow);
        }

        .crs-table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .crs-table-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: #1d2327;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .crs-badge {
            background: var(--crs-light);
            color: var(--crs-gray);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .crs-search-box {
            display: flex;
            gap: 8px;
        }

        .crs-search-input {
            padding: 8px 12px;
            border: 1px solid var(--crs-border);
            border-radius: var(--crs-radius-sm);
            font-size: 13px;
            min-width: 250px;
        }

        .crs-search-btn {
            background: var(--crs-light);
            border: 1px solid var(--crs-border);
            padding: 8px 15px;
            border-radius: var(--crs-radius-sm);
            cursor: pointer;
            transition: all 0.2s;
        }

        .crs-search-btn:hover {
            background: #fff;
            border-color: var(--crs-primary);
            color: var(--crs-primary);
        }

        /* Table Styles */
        .crs-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .crs-table th {
            text-align: left;
            padding: 12px 10px;
            background: var(--crs-light);
            color: #1d2327;
            font-weight: 600;
            font-size: 13px;
            border-bottom: 2px solid var(--crs-border);
        }

        .crs-table td {
            padding: 15px 10px;
            border-bottom: 1px solid var(--crs-border);
            vertical-align: middle;
        }

        .crs-table tr:hover td {
            background: #f6f7f7;
        }

        /* Coupon Status Badges */
        .crs-status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .crs-status-active {
            background: #edfaef;
            color: #00a32a;
            border: 1px solid #b8e6b9;
        }

        .crs-status-inactive {
            background: #fcf0f1;
            color: #dc3232;
            border: 1px solid #f5b9b9;
        }

        .crs-type-badge {
            background: var(--crs-light);
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            color: var(--crs-gray);
        }

        /* Action Buttons */
        .crs-action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .crs-btn-icon {
            padding: 6px 12px;
            border: 1px solid var(--crs-border);
            background: #fff;
            border-radius: var(--crs-radius-sm);
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
        }

        .crs-btn-icon:hover {
            background: var(--crs-light);
        }

        .crs-btn-edit {
            color: var(--crs-primary);
        }

        .crs-btn-edit:hover {
            border-color: var(--crs-primary);
            background: #f0f6fc;
        }

        .crs-btn-delete {
            color: var(--crs-danger);
        }

        .crs-btn-delete:hover {
            border-color: var(--crs-danger);
            background: #fcf0f1;
        }

        /* Modal Styles */
        .crs-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 100000;
            align-items: center;
            justify-content: center;
        }

        .crs-modal.active {
            display: flex;
        }

        .crs-modal-content {
            background: #fff;
            border-radius: var(--crs-radius);
            width: 90%;
            max-width: 600px;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }

        .crs-modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--crs-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .crs-modal-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .crs-modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            line-height: 1;
        }

        .crs-modal-body {
            padding: 25px;
        }

        .crs-modal-footer {
            padding: 20px 25px;
            border-top: 1px solid var(--crs-border);
            text-align: right;
            background: var(--crs-light);
        }

        /* Empty State */
        .crs-empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--crs-light);
            border-radius: var(--crs-radius);
        }

        .crs-empty-icon {
            font-size: 48px;
            margin-bottom: 15px;
            color: var(--crs-gray);
        }

        .crs-empty-state h4 {
            font-size: 18px;
            margin: 0 0 10px 0;
            color: #1d2327;
        }

        .crs-empty-state p {
            color: var(--crs-gray);
            margin: 0 0 20px 0;
        }

        /* Responsive Table */
        @media (max-width: 782px) {
            .crs-table {
                display: block;
                overflow-x: auto;
            }
            
            .crs-table-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .crs-search-box {
                width: 100%;
            }
            
            .crs-search-input {
                flex: 1;
                min-width: auto;
            }
            
            .crs-action-buttons {
                flex-direction: column;
            }
            
            .crs-btn-icon {
                width: 100%;
                justify-content: center;
            }
        }

        /* Settings Tabs */
        .crs-settings-tabs {
            margin: 20px 0 15px;
            border-bottom: 1px solid #c3c4c7;
            padding-bottom: 0;
            background: #fff;
            padding: 10px 15px 0 15px;
            border-radius: 4px 4px 0 0;
        }

        .nav-tab {
            margin-left: 0;
            margin-right: 8px;
            font-size: 14px;
            font-weight: 500;
            line-height: 24px;
            padding: 8px 18px;
            border: 1px solid #c3c4c7;
            border-bottom: none;
            background: #f0f0f1;
            color: #2c3338;
            text-decoration: none;
            border-radius: 4px 4px 0 0;
            transition: all 0.2s;
        }

        .nav-tab:hover {
            background: #fff;
            color: #2271b1;
        }

        .nav-tab-active {
            background: #fff;
            color: #1d2327;
            border-bottom-color: #fff;
            box-shadow: 0 -2px 4px rgba(0,0,0,0.05);
        }

        /* Settings Tab Content */
        .crs-settings-tab {
            background: #fff;
            padding: 30px;
            border: 1px solid #c3c4c7;
            border-top: none;
            margin-bottom: 20px;
            border-radius: 0 0 4px 4px;
        }

        /* Form Table */
        .form-table th {
            width: 200px;
            font-weight: 600;
        }

        /* Stripe Info */
        .crs-stripe-info {
            margin-top: 30px;
            padding: 20px 25px;
            background: #f0f6fc;
            border-left: 4px solid #2271b1;
            border-radius: 4px;
        }

        .crs-stripe-info h4 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 16px;
            color: #1d2327;
        }

        .crs-stripe-info ol {
            margin: 0;
            padding-left: 20px;
        }

        .crs-stripe-info li {
            margin-bottom: 8px;
            color: #2c3338;
        }

        .crs-stripe-info code {
            background: #e5e5e5;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
        }

        /* Submit Button */
        .submit {
            margin-top: 30px;
            padding: 15px 0;
            border-top: 1px solid #e5e5e5;
        }

        .submit .button-primary {
            background: #2271b1;
            color: white !important;
            border: none;
            padding: 8px 25px;
            font-size: 14px;
            font-weight: 500;
        }

        .submit .button-primary:hover {
            background: #135e96;
        }
</style>

<div class="wrap crs-coupon-settings">
    <h1 class="wp-heading-inline"><?php _e('Congress Registration Settings', 'crscngres'); ?></h1>
    
    <!-- URL-based Tabs -->
    <div class="crs-settings-tabs">
        <a href="?page=crs-settings&tab=general" class="nav-tab <?php echo $current_tab == 'general' ? 'nav-tab-active' : ''; ?>"><?php _e('General', 'crscngres'); ?></a>
        <a href="?page=crs-settings&tab=stripe" class="nav-tab <?php echo $current_tab == 'stripe' ? 'nav-tab-active' : ''; ?>"><?php _e('Stripe Payment', 'crscngres'); ?></a>
        <a href="?page=crs-settings&tab=coupons" class="nav-tab <?php echo $current_tab == 'coupons' ? 'nav-tab-active' : ''; ?>"><?php _e('Coupons', 'crscngres'); ?></a>
    </div>
    
    <!-- General Tab Content -->
    <?php if ($current_tab == 'general'): ?>
    <div id="tab-general" class="crs-settings-tab">
        <h2><?php _e('General Settings', 'crscngres'); ?></h2>
        <form method="post" action="">
            <?php wp_nonce_field('crs_save_settings'); ?>
            <input type="hidden" name="settings_tab" value="general">
            <input type="hidden" name="save_settings" value="1">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="currency"><?php _e('Currency', 'crscngres'); ?></label></th>
                    <td>
                        <select name="currency" id="currency">
                            <option value="EUR" <?php selected($currency, 'EUR'); ?>><?php _e('EUR', 'crscngres'); ?> (€)</option>
                            <option value="USD" <?php selected($currency, 'USD'); ?>><?php _e('USD', 'crscngres'); ?> ($)</option>
                            <option value="GBP" <?php selected($currency, 'GBP'); ?>><?php _e('GBP', 'crscngres'); ?> (£)</option>
                        </select>
                        <p class="description"><?php _e('Select your preferred currency for payments.', 'crscngres'); ?></p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="submit_general" class="button button-primary" value="<?php _e('Save General Settings', 'crscngres'); ?>">
            </p>
        </form>
    </div>
    <?php endif; ?>
    
    <!-- Stripe Tab Content -->
    <?php if ($current_tab == 'stripe'): ?>
    <div id="tab-stripe" class="crs-settings-tab">
        <h2><?php _e('Stripe Payment Gateway', 'crscngres'); ?></h2>
        <form method="post" action="">
            <?php wp_nonce_field('crs_save_settings'); ?>
            <input type="hidden" name="settings_tab" value="stripe">
            <input type="hidden" name="save_settings" value="1">
            
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="stripe_test_mode"><?php _e('Test Mode', 'crscngres'); ?></label></th>
                    <td>
                        <input type="checkbox" name="stripe_test_mode" id="stripe_test_mode" value="1" <?php checked($test_mode, 1); ?>>
                        <label for="stripe_test_mode"><?php _e('Enable test mode (use test keys)', 'crscngres'); ?></label>
                        <p class="description"><?php _e('When enabled, test API keys will be used. Disable for live payments.', 'crscngres'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th colspan="2"><h3 class="crs-section-heading">🔧 <?php _e('Test Keys (for testing)', 'crscngres'); ?></h3></th>
                </tr>
                <tr>
                    <th scope="row"><label for="test_publishable"><?php _e('Test Publishable Key', 'crscngres'); ?></label></th>
                    <td>
                        <input type="text" name="stripe_test_publishable_key" id="test_publishable" 
                            value="<?php echo esc_attr($test_publishable); ?>" class="regular-text" placeholder="pk_test_...">
                        <p class="description"><?php _e('Starts with \'pk_test_\' - find in Stripe Dashboard → API Keys', 'crscngres'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="test_secret"><?php _e('Test Secret Key', 'crscngres'); ?></label></th>
                    <td>
                        <input type="password" name="stripe_test_secret_key" id="test_secret" 
                            value="<?php echo esc_attr($test_secret); ?>" class="regular-text" placeholder="sk_test_...">
                        <p class="description"><?php _e('Starts with \'sk_test_\' - keep this secret!', 'crscngres'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="test_webhook"><?php _e('Test Webhook Secret', 'crscngres'); ?></label></th>
                    <td>
                        <input type="password" name="stripe_test_webhook_secret" id="test_webhook" 
                            value="<?php echo esc_attr($test_webhook); ?>" class="regular-text" placeholder="whsec_...">
                        <p class="description"><?php _e('Starts with \'whsec_\' - from your Stripe webhook endpoint', 'crscngres'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th colspan="2"><h3 class="crs-section-heading">🔒 <?php _e('Live Keys (for production)', 'crscngres'); ?></h3></th>
                </tr>
                <tr>
                    <th scope="row"><label for="live_publishable"><?php _e('Live Publishable Key', 'crscngres'); ?></label></th>
                    <td>
                        <input type="text" name="stripe_live_publishable_key" id="live_publishable" 
                            value="<?php echo esc_attr($live_publishable); ?>" class="regular-text" placeholder="pk_live_...">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="live_secret"><?php _e('Live Secret Key', 'crscngres'); ?></label></th>
                    <td>
                        <input type="password" name="stripe_live_secret_key" id="live_secret" 
                            value="<?php echo esc_attr($live_secret); ?>" class="regular-text" placeholder="sk_live_...">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="live_webhook"><?php _e('Live Webhook Secret', 'crscngres'); ?></label></th>
                    <td>
                        <input type="password" name="stripe_live_webhook_secret" id="live_webhook" 
                            value="<?php echo esc_attr($live_webhook); ?>" class="regular-text" placeholder="whsec_...">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label><?php _e('Webhook URL', 'crscngres'); ?></label></th>
                    <td>
                        <code style="background: #f0f0f1; padding: 8px 12px; display: inline-block; border-radius: 4px;"><?php echo esc_url($webhook_url); ?></code>
                        <p class="description">
                            ⚡ <strong><?php _e('Important:', 'crscngres'); ?></strong>
                            <?php _e('Add this URL to your Stripe webhook endpoints (Developers → Webhooks → Add endpoint)', 'crscngres'); ?>
                        </p>
                        <p class="description">
                            <?php _e('Events to listen for:', 'crscngres'); ?> <code>payment_intent.succeeded</code>, <code>payment_intent.payment_failed</code>
                        </p>
                    </td>
                </tr>
            </table>
            
            <div class="crs-stripe-info">
                <h4>📌 <?php _e('How to get Stripe API Keys:', 'crscngres'); ?></h4>
                <ol>
                    <li><?php _e('Login to', 'crscngres'); ?> <a href="https://dashboard.stripe.com" target="_blank"><?php _e('Stripe Dashboard', 'crscngres'); ?></a></li>
                    <li><?php _e('Go to', 'crscngres'); ?> <strong><?php _e('Developers → API Keys', 'crscngres'); ?></strong></li>
                    <li><?php _e('Copy your', 'crscngres'); ?> <strong><?php _e('Publishable key', 'crscngres'); ?></strong> <?php _e('and', 'crscngres'); ?> <strong><?php _e('Secret key', 'crscngres'); ?></strong></li>
                    <li><?php _e('For webhook:', 'crscngres'); ?> <?php _e('Go to', 'crscngres'); ?> <strong><?php _e('Developers → Webhooks', 'crscngres'); ?></strong> → <?php _e('Add endpoint', 'crscngres'); ?></li>
                    <li><?php _e('Use the URL above and select events:', 'crscngres'); ?> <code>payment_intent.succeeded</code>, <code>payment_intent.payment_failed</code></li>
                    <li><?php _e('After creating, copy the', 'crscngres'); ?> <strong><?php _e('Signing secret', 'crscngres'); ?></strong> (whsec_...)</li>
                </ol>
            </div>
            
            <p class="submit">
                <input type="submit" name="submit_stripe" class="button button-primary" value="<?php _e('Save Stripe Settings', 'crscngres'); ?>">
            </p>
        </form>
    </div>
    <?php endif; ?>
    
    <!-- Coupons Tab Content -->
    <?php if ($current_tab == 'coupons'): ?>
    <div id="tab-coupons" class="crs-settings-tab">
        <div class="crs-coupon-section">
            <h2 class="crs-section-title">
                <span class="dashicons dashicons-tickets-alt" style="font-size: 28px; width: 28px; height: 28px; margin-right: 10px;"></span>
                <?php _e('Coupon Management', 'crscngres'); ?>
            </h2>
            <p class="crs-section-subtitle"><?php _e('Create and manage discount coupons for your congress registrations', 'crscngres'); ?></p>
            
            <!-- Add/Edit Coupon Form Card -->
            <div class="crs-form-card">
                <div class="crs-form-header">
                    <div class="crs-form-icon">🏷️</div>
                    <div>
                        <h3><?php _e('Create New Coupon', 'crscngres'); ?></h3>
                        <p><?php _e('Add a new discount coupon for your customers', 'crscngres'); ?></p>
                    </div>
                </div>
                
                <form method="post" action="" id="crs-coupon-form">
                    <?php wp_nonce_field('crs_coupon_action'); ?>
                    <input type="hidden" name="coupon_action" id="coupon_action" value="add">
                    <input type="hidden" name="coupon_id" id="coupon_id" value="">
                    
                    <div class="crs-coupon-grid">
                        <!-- Coupon Code -->
                        <div class="crs-field-group crs-full-width">
                            <div class="crs-field-label">
                                <span class="dashicons dashicons-tag"></span>
                                <label for="coupon_code"><?php _e('Coupon Code', 'crscngres'); ?> <span style="color: #dc3232;">*</span></label>
                            </div>
                            <input type="text" name="code" id="coupon_code" class="crs-field-input" 
                                   placeholder="<?php _e('e.g., SUMMER2024', 'crscngres'); ?>" required 
                                   style="text-transform: uppercase;">
                            <div class="crs-field-hint">
                                <span class="dashicons dashicons-info"></span>
                                <?php _e('Code will be automatically converted to uppercase', 'crscngres'); ?>
                            </div>
                        </div>
                        
                        <!-- Description -->
                        <div class="crs-field-group crs-full-width">
                            <div class="crs-field-label">
                                <span class="dashicons dashicons-edit"></span>
                                <label for="coupon_description"><?php _e('Description', 'crscngres'); ?></label>
                            </div>
                            <textarea name="description" id="coupon_description" class="crs-field-textarea" 
                                      placeholder="<?php _e('Enter coupon description (optional)', 'crscngres'); ?>"></textarea>
                        </div>
                        
                        <!-- Discount Type -->
                        <div class="crs-field-group">
                            <div class="crs-field-label">
                                <span class="dashicons dashicons-calculator"></span>
                                <label for="discount_type"><?php _e('Discount Type', 'crscngres'); ?></label>
                            </div>
                            <select name="discount_type" id="discount_type" class="crs-field-select">
                                <option value="percentage"><?php _e('Percentage (%)', 'crscngres'); ?></option>
                                <option value="fixed"><?php _e('Fixed Amount (€)', 'crscngres'); ?></option>
                            </select>
                        </div>
                        
                        <!-- Discount Value -->
                        <div class="crs-field-group">
                            <div class="crs-field-label">
                                <span class="dashicons dashicons-money-alt"></span>
                                <label for="discount_value"><?php _e('Discount Value', 'crscngres'); ?> <span style="color: #dc3232;">*</span></label>
                            </div>
                            <input type="number" name="discount_value" id="discount_value" class="crs-field-input" 
                                   step="0.01" min="0" required placeholder="<?php _e('0.00', 'crscngres'); ?>">
                        </div>
                        
                        <!-- Min Order Amount -->
                        <div class="crs-field-group">
                            <div class="crs-field-label">
                                <span class="dashicons dashicons-chart-line"></span>
                                <label for="min_order_amount"><?php _e('Min Order (€)', 'crscngres'); ?></label>
                            </div>
                            <input type="number" name="min_order_amount" id="min_order_amount" class="crs-field-input" 
                                   step="0.01" min="0" value="0" placeholder="<?php _e('0 = No minimum', 'crscngres'); ?>">
                        </div>
                        
                        <!-- Max Discount -->
                        <div class="crs-field-group">
                            <div class="crs-field-label">
                                <span class="dashicons dashicons-arrow-up-alt"></span>
                                <label for="max_discount_amount"><?php _e('Max Discount (€)', 'crscngres'); ?></label>
                            </div>
                            <input type="number" name="max_discount_amount" id="max_discount_amount" class="crs-field-input" 
                                   step="0.01" min="0" placeholder="<?php _e('Unlimited', 'crscngres'); ?>">
                            <div class="crs-field-hint">
                                <span class="dashicons dashicons-info"></span>
                                <?php _e('For percentage discounts only', 'crscngres'); ?>
                            </div>
                        </div>
                        
                        <!-- Usage Limit -->
                        <div class="crs-field-group">
                            <div class="crs-field-label">
                                <span class="dashicons dashicons-clock"></span>
                                <label for="usage_limit"><?php _e('Usage Limit', 'crscngres'); ?></label>
                            </div>
                            <input type="number" name="usage_limit" id="usage_limit" class="crs-field-input" 
                                   min="0" placeholder="<?php _e('Unlimited', 'crscngres'); ?>">
                        </div>
                        
                        <!-- Per User Limit -->
                        <div class="crs-field-group">
                            <div class="crs-field-label">
                                <span class="dashicons dashicons-admin-users"></span>
                                <label for="per_user_limit"><?php _e('Per User Limit', 'crscngres'); ?></label>
                            </div>
                            <input type="number" name="per_user_limit" id="per_user_limit" class="crs-field-input" 
                                   min="1" value="1">
                        </div>
                        
                        <!-- Start Date -->
                        <div class="crs-field-group">
                            <div class="crs-field-label">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <label for="start_date"><?php _e('Start Date', 'crscngres'); ?></label>
                            </div>
                            <input type="datetime-local" name="start_date" id="start_date" class="crs-field-input">
                        </div>
                        
                        <!-- Expiry Date -->
                        <div class="crs-field-group">
                            <div class="crs-field-label">
                                <span class="dashicons dashicons-calendar-alt" style="color: #dc3232;"></span>
                                <label for="expiry_date"><?php _e('Expiry Date', 'crscngres'); ?></label>
                            </div>
                            <input type="datetime-local" name="expiry_date" id="expiry_date" class="crs-field-input">
                        </div>
                        
                        <!-- Active Checkbox -->
                        <div class="crs-field-group crs-full-width">
                            <div class="crs-checkbox-wrapper">
                                <input type="checkbox" name="is_active" id="is_active" value="1" checked>
                                <label for="is_active">
                                    <strong><?php _e('Active', 'crscngres'); ?></strong>
                                    <span style="color: var(--crs-gray); font-weight: normal; margin-left: 10px;">
                                        <?php _e('Enable this coupon for immediate use', 'crscngres'); ?>
                                    </span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Submit Buttons -->
                        <div class="crs-submit-wrapper">
                            <button type="submit" class="crs-btn-primary" id="crs-submit-coupon">
                                <span class="dashicons dashicons-plus-alt2"></span>
                                <span class="btn-text"><?php _e('Add New Coupon', 'crscngres'); ?></span>
                            </button>
                            <button type="button" class="crs-btn-primary" id="crs-update-coupon" style="display: none; background: #00a32a;">
                                <span class="dashicons dashicons-update"></span>
                                <span class="btn-text"><?php _e('Update Coupon', 'crscngres'); ?></span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Existing Coupons Table Card -->
            <div class="crs-table-card">
                <div class="crs-table-header">
                    <h3>
                        <span class="dashicons dashicons-list-view"></span>
                        <?php _e('Existing Coupons', 'crscngres'); ?>
                        <span class="crs-badge"><?php echo count($coupons); ?> <?php _e('total', 'crscngres'); ?></span>
                    </h3>
                    <div class="crs-search-box">
                        <input type="text" id="crs-coupon-search" class="crs-search-input" 
                               placeholder="<?php _e('Search coupons...', 'crscngres'); ?>">
                        <button type="button" class="crs-search-btn">
                            <span class="dashicons dashicons-search"></span>
                        </button>
                    </div>
                </div>
                
                <?php if (!empty($coupons)): ?>
                    <div class="crs-table-responsive">
                        <table class="crs-table">
                            <thead>
                                <tr>
                                    <th><?php _e('Code', 'crscngres'); ?></th>
                                    <th><?php _e('Type', 'crscngres'); ?></th>
                                    <th><?php _e('Value', 'crscngres'); ?></th>
                                    <th><?php _e('Usage', 'crscngres'); ?></th>
                                    <th><?php _e('Min Order', 'crscngres'); ?></th>
                                    <th><?php _e('Expiry', 'crscngres'); ?></th>
                                    <th><?php _e('Status', 'crscngres'); ?></th>
                                    <th><?php _e('Actions', 'crscngres'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($coupons as $coupon): ?>
                                    <tr data-coupon-id="<?php echo $coupon->id; ?>" class="crs-coupon-row">
                                        <td>
                                            <strong><?php echo esc_html($coupon->code); ?></strong>
                                            <?php if ($coupon->description): ?>
                                                <br><small style="color: #666;"><?php echo esc_html(substr($coupon->description, 0, 30)) . (strlen($coupon->description) > 30 ? '...' : ''); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="crs-type-badge">
                                                <?php echo $coupon->discount_type === 'percentage' ? '%' : '€'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?php echo $coupon->discount_value; ?></strong>
                                            <?php echo $coupon->discount_type === 'percentage' ? '%' : '€'; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $usage_percent = $coupon->usage_limit ? round(($coupon->usage_count / $coupon->usage_limit) * 100) : 0;
                                            ?>
                                            <div style="display: flex; align-items: center; gap: 5px;">
                                                <span><?php echo $coupon->usage_count . '/' . ($coupon->usage_limit ?: '∞'); ?></span>
                                                <?php if ($coupon->usage_limit): ?>
                                                    <div style="width: 50px; height: 4px; background: #e0e0e0; border-radius: 2px;">
                                                        <div style="width: <?php echo $usage_percent; ?>%; height: 4px; background: <?php echo $usage_percent > 80 ? '#dc3232' : '#00a32a'; ?>; border-radius: 2px;"></div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo $coupon->min_order_amount ? '€' . $coupon->min_order_amount : '—'; ?></td>
                                        <td>
                                            <?php if ($coupon->expiry_date): ?>
                                                <?php 
                                                $expiry = strtotime($coupon->expiry_date);
                                                $now = current_time('timestamp');
                                                $days_left = ceil(($expiry - $now) / (60 * 60 * 24));
                                                ?>
                                                <span style="color: <?php echo $days_left < 7 ? '#dc3232' : 'inherit'; ?>;">
                                                    <?php echo date_i18n('Y-m-d', $expiry); ?>
                                                    <?php if ($days_left > 0 && $days_left < 7): ?>
                                                        <br><small style="color: #dc3232;"><?php echo $days_left; ?> days left</small>
                                                    <?php endif; ?>
                                                </span>
                                            <?php else: ?>
                                                <?php _e('Never', 'crscngres'); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="crs-status-badge <?php echo $coupon->is_active ? 'crs-status-active' : 'crs-status-inactive'; ?>">
                                                <?php echo $coupon->is_active ? __('Active', 'crscngres') : __('Inactive', 'crscngres'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="crs-action-buttons">
                                                <button type="button" class="crs-btn-icon crs-btn-edit edit-coupon" 
                                                        data-id="<?php echo $coupon->id; ?>"
                                                        data-code="<?php echo esc_attr($coupon->code); ?>"
                                                        data-description="<?php echo esc_attr($coupon->description); ?>"
                                                        data-discount_type="<?php echo $coupon->discount_type; ?>"
                                                        data-discount_value="<?php echo $coupon->discount_value; ?>"
                                                        data-min_order_amount="<?php echo $coupon->min_order_amount; ?>"
                                                        data-max_discount_amount="<?php echo $coupon->max_discount_amount; ?>"
                                                        data-usage_limit="<?php echo $coupon->usage_limit; ?>"
                                                        data-per_user_limit="<?php echo $coupon->per_user_limit; ?>"
                                                        data-start_date="<?php echo $coupon->start_date ? date('Y-m-d\TH:i', strtotime($coupon->start_date)) : ''; ?>"
                                                        data-expiry_date="<?php echo $coupon->expiry_date ? date('Y-m-d\TH:i', strtotime($coupon->expiry_date)) : ''; ?>"
                                                        data-is_active="<?php echo $coupon->is_active; ?>">
                                                    <span class="dashicons dashicons-edit"></span>
                                                    <?php _e('Edit', 'crscngres'); ?>
                                                </button>
                                                
                                                <form method="post" style="display: inline;" onsubmit="return confirm('<?php _e('Are you sure you want to delete this coupon?', 'crscngres'); ?>')">
                                                    <?php wp_nonce_field('crs_coupon_action'); ?>
                                                    <input type="hidden" name="coupon_action" value="delete">
                                                    <input type="hidden" name="coupon_id" value="<?php echo $coupon->id; ?>">
                                                    <button type="submit" class="crs-btn-icon crs-btn-delete">
                                                        <span class="dashicons dashicons-trash"></span>
                                                        <?php _e('Delete', 'crscngres'); ?>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="crs-empty-state">
                        <div class="crs-empty-icon">🏷️</div>
                        <h4><?php _e('No Coupons Found', 'crscngres'); ?></h4>
                        <p><?php _e('Start creating coupons to offer discounts to your customers.', 'crscngres'); ?></p>
                        <button type="button" class="crs-btn-primary" style="margin-top: 15px;" onclick="document.getElementById('coupon_code').focus();">
                            <span class="dashicons dashicons-plus-alt2"></span>
                            <?php _e('Create Your First Coupon', 'crscngres'); ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Edit Coupon Modal -->
<div id="crs-edit-coupon-modal" class="crs-modal">
    <div class="crs-modal-content">
        <div class="crs-modal-header">
            <h3>
                <span class="dashicons dashicons-edit" style="font-size: 20px;"></span>
                <?php _e('Edit Coupon', 'crscngres'); ?>
            </h3>
            <button type="button" class="crs-modal-close" id="crs-modal-close">&times;</button>
        </div>
        <div class="crs-modal-body">
            <div id="crs-edit-form-container"></div>
        </div>
        <div class="crs-modal-footer">
            <button type="button" class="button button-secondary" id="crs-modal-cancel"><?php _e('Cancel', 'crscngres'); ?></button>
            <button type="button" class="button button-primary" id="crs-modal-save"><?php _e('Update Coupon', 'crscngres'); ?></button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle test/live mode for stripe tab
// Toggle test/live mode for stripe tab
function toggleTestMode() {
    if ($('#stripe_test_mode').is(':checked')) {
        // Test mode ON - show test keys, hide live keys
        $('tr:has(input[name="stripe_test_publishable_key"])').show();
        $('tr:has(input[name="stripe_test_secret_key"])').show();
        $('tr:has(input[name="stripe_test_webhook_secret"])').show();
        
        $('tr:has(input[name="stripe_live_publishable_key"])').hide();
        $('tr:has(input[name="stripe_live_secret_key"])').hide();
        $('tr:has(input[name="stripe_live_webhook_secret"])').hide();
    } else {
        // Test mode OFF - show live keys, hide test keys
        $('tr:has(input[name="stripe_test_publishable_key"])').hide();
        $('tr:has(input[name="stripe_test_secret_key"])').hide();
        $('tr:has(input[name="stripe_test_webhook_secret"])').hide();
        
        $('tr:has(input[name="stripe_live_publishable_key"])').show();
        $('tr:has(input[name="stripe_live_secret_key"])').show();
        $('tr:has(input[name="stripe_live_webhook_secret"])').show();
    }
}
    
    $('#stripe_test_mode').on('change', toggleTestMode);
    toggleTestMode();
    
    // Coupon code uppercase
    $('#coupon_code').on('input', function() {
        $(this).val($(this).val().toUpperCase());
    });
    
    // Edit coupon functionality
    $('.edit-coupon').on('click', function() {
        var $btn = $(this);
        
        // Populate form fields
        $('#coupon_action').val('edit');
        $('#coupon_id').val($btn.data('id'));
        $('#coupon_code').val($btn.data('code'));
        $('#coupon_description').val($btn.data('description'));
        $('#discount_type').val($btn.data('discount_type'));
        $('#discount_value').val($btn.data('discount_value'));
        $('#min_order_amount').val($btn.data('min_order_amount'));
        $('#max_discount_amount').val($btn.data('max_discount_amount') || '');
        $('#usage_limit').val($btn.data('usage_limit') || '');
        $('#per_user_limit').val($btn.data('per_user_limit'));
        $('#start_date').val($btn.data('start_date'));
        $('#expiry_date').val($btn.data('expiry_date'));
        $('#is_active').prop('checked', $btn.data('is_active') == 1);
        
        // Change button text and style
        $('#crs-submit-coupon').hide();
        $('#crs-update-coupon').show();
        $('.btn-text').text('Update Coupon');
        
        // Scroll to form
        $('html, body').animate({
            scrollTop: $('#crs-coupon-form').offset().top - 50
        }, 500);
    });
    
    // Reset form for new coupon
    $('#crs-update-coupon').on('click', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to update this coupon?')) {
            $('#crs-coupon-form').submit();
        }
    });
    
    // Cancel edit - reset form
    function resetCouponForm() {
        $('#crs-coupon-form')[0].reset();
        $('#coupon_action').val('add');
        $('#coupon_id').val('');
        $('#crs-submit-coupon').show();
        $('#crs-update-coupon').hide();
        $('.btn-text').text('Add New Coupon');
        $('#is_active').prop('checked', true);
    }
    
    // Search functionality
    $('#crs-coupon-search').on('keyup', function() {
        var searchTerm = $(this).val().toLowerCase();
        
        $('.crs-coupon-row').each(function() {
            var $row = $(this);
            var text = $row.text().toLowerCase();
            
            if (text.indexOf(searchTerm) > -1) {
                $row.show();
            } else {
                $row.hide();
            }
        });
    });
    
    // Modal functionality
    var $modal = $('#crs-edit-coupon-modal');
    
    $('.edit-coupon').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        
        var editForm = `
            <form method="post" id="crs-edit-form" action="">
                <?php wp_nonce_field('crs_coupon_action'); ?>
                <input type="hidden" name="coupon_action" value="edit">
                <input type="hidden" name="coupon_id" value="${$btn.data('id')}">
                
                <div class="crs-coupon-grid">
                    <div class="crs-field-group crs-full-width">
                        <label><strong><?php _e('Coupon Code', 'crscngres'); ?></strong></label>
                        <input type="text" name="code" class="crs-field-input" value="${$btn.data('code')}" required style="text-transform: uppercase;">
                    </div>
                    
                    <div class="crs-field-group crs-full-width">
                        <label><strong><?php _e('Description', 'crscngres'); ?></strong></label>
                        <textarea name="description" class="crs-field-textarea">${$btn.data('description') || ''}</textarea>
                    </div>
                    
                    <div class="crs-field-group">
                        <label><strong><?php _e('Discount Type', 'crscngres'); ?></strong></label>
                        <select name="discount_type" class="crs-field-select">
                            <option value="percentage" ${$btn.data('discount_type') == 'percentage' ? 'selected' : ''}>Percentage (%)</option>
                            <option value="fixed" ${$btn.data('discount_type') == 'fixed' ? 'selected' : ''}>Fixed Amount (€)</option>
                        </select>
                    </div>
                    
                    <div class="crs-field-group">
                        <label><strong><?php _e('Discount Value', 'crscngres'); ?></strong></label>
                        <input type="number" name="discount_value" step="0.01" min="0" class="crs-field-input" value="${$btn.data('discount_value')}" required>
                    </div>
                    
                    <div class="crs-field-group">
                        <label><strong><?php _e('Min Order (€)', 'crscngres'); ?></strong></label>
                        <input type="number" name="min_order_amount" step="0.01" min="0" class="crs-field-input" value="${$btn.data('min_order_amount') || 0}">
                    </div>
                    
                    <div class="crs-field-group">
                        <label><strong><?php _e('Max Discount (€)', 'crscngres'); ?></strong></label>
                        <input type="number" name="max_discount_amount" step="0.01" min="0" class="crs-field-input" value="${$btn.data('max_discount_amount') || ''}">
                    </div>
                    
                    <div class="crs-field-group">
                        <label><strong><?php _e('Usage Limit', 'crscngres'); ?></strong></label>
                        <input type="number" name="usage_limit" min="0" class="crs-field-input" value="${$btn.data('usage_limit') || ''}">
                    </div>
                    
                    <div class="crs-field-group">
                        <label><strong><?php _e('Per User Limit', 'crscngres'); ?></strong></label>
                        <input type="number" name="per_user_limit" min="1" class="crs-field-input" value="${$btn.data('per_user_limit') || 1}">
                    </div>
                    
                    <div class="crs-field-group">
                        <label><strong><?php _e('Start Date', 'crscngres'); ?></strong></label>
                        <input type="datetime-local" name="start_date" class="crs-field-input" value="${$btn.data('start_date') || ''}">
                    </div>
                    
                    <div class="crs-field-group">
                        <label><strong><?php _e('Expiry Date', 'crscngres'); ?></strong></label>
                        <input type="datetime-local" name="expiry_date" class="crs-field-input" value="${$btn.data('expiry_date') || ''}">
                    </div>
                    
                    <div class="crs-field-group crs-full-width">
                        <label class="crs-checkbox-wrapper">
                            <input type="checkbox" name="is_active" value="1" ${$btn.data('is_active') == 1 ? 'checked' : ''}>
                            <span><?php _e('Active', 'crscngres'); ?></span>
                        </label>
                    </div>
                </div>
            </form>
        `;
        
        $('#crs-edit-form-container').html(editForm);
        $modal.addClass('active');
    });
    
    $('#crs-modal-close, #crs-modal-cancel').on('click', function() {
        $modal.removeClass('active');
    });
    
    $('#crs-modal-save').on('click', function() {
        $('#crs-edit-form').submit();
    });
    
    // Close modal when clicking outside
    $(window).on('click', function(e) {
        if ($(e.target).is($modal)) {
            $modal.removeClass('active');
        }
    });
    
    // Auto-hide messages after 3 seconds
    setTimeout(function() {
        $('.notice').fadeOut();
    }, 3000);
});
</script>