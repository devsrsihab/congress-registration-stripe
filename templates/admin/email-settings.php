<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap crs-email-settings">
    <!-- Header with Gradient -->
    <div class="crs-settings-header">
        <div class="crs-header-content">
            <h1 class="crs-page-title">
                <span class="crs-title-icon">📧</span>
                Email Configuration
            </h1>
            <p class="crs-page-subtitle">Configure all email notifications for congress registrations</p>
        </div>

    </div>

    <!-- Modern Tab Navigation -->
    <div class="crs-tabs-container">
        <div class="crs-tabs">
            <a href="#general" class="crs-tab active" data-tab="general">
                <span class="tab-icon">⚙️</span>
                <span class="tab-text">General</span>
            </a>
            <a href="#triggers" class="crs-tab" data-tab="triggers">
                <span class="tab-icon">🔔</span>
                <span class="tab-text">Triggers</span>
            </a>
            <a href="#templates" class="crs-tab" data-tab="templates">
                <span class="tab-icon">📝</span>
                <span class="tab-text">Templates</span>
            </a>
            <a href="#test" class="crs-tab" data-tab="test">
                <span class="tab-icon">✉️</span>
                <span class="tab-text">Test Email</span>
            </a>
        </div>
    </div>

    <form method="post" action="" class="crs-email-form">
        <?php wp_nonce_field('crs_email_settings_nonce'); ?>

        <!-- General Settings Tab -->
        <div id="tab-general" class="crs-tab-pane active">
            <div class="crs-card">
                <div class="crs-card-header">
                    <h3>Sender Information</h3>
                    <p>Configure who your emails will come from</p>
                </div>
                <div class="crs-card-body">
                    <div class="crs-form-grid">
                        <div class="crs-form-group">
                            <label for="from_name">
                                <span class="label-icon">👤</span>
                                From Name
                            </label>
                            <input type="text" name="from_name" id="from_name" 
                                   value="<?php echo esc_attr($from_name); ?>" 
                                   placeholder="e.g., Congress Registration" class="crs-input">
                            <p class="crs-field-note">The name that recipients will see</p>
                        </div>

                        <div class="crs-form-group">
                            <label for="from_email">
                                <span class="label-icon">📧</span>
                                From Email
                            </label>
                            <input type="email" name="from_email" id="from_email" 
                                   value="<?php echo esc_attr($from_email); ?>" 
                                   placeholder="noreply@yourdomain.com" class="crs-input">
                            <p class="crs-field-note">The email address emails will come from</p>
                        </div>

                        <div class="crs-form-group">
                            <label for="reply_to">
                                <span class="label-icon">↩️</span>
                                Reply-To Email
                            </label>
                            <input type="email" name="reply_to" id="reply_to" 
                                   value="<?php echo esc_attr($reply_to); ?>" 
                                   placeholder="support@yourdomain.com" class="crs-input">
                            <p class="crs-field-note">Where replies will go</p>
                        </div>

                        <div class="crs-form-group">
                            <label for="admin_email">
                                <span class="label-icon">👑</span>
                                Admin Email
                            </label>
                            <input type="email" name="admin_email" id="admin_email" 
                                   value="<?php echo esc_attr($admin_email); ?>" 
                                   placeholder="admin@yourdomain.com" class="crs-input">
                            <p class="crs-field-note">Where admin notifications will be sent</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Email Triggers Tab -->
        <div id="tab-triggers" class="crs-tab-pane">
            <div class="crs-card">
                <div class="crs-card-header">
                    <h3>Email Triggers</h3>
                    <p>Choose when to send email notifications</p>
                </div>
                <div class="crs-card-body">
                    <div class="crs-triggers-list">
                        <!-- Registration Complete -->
                        <div class="crs-trigger-item">
                            <div class="crs-trigger-icon">✅</div>
                            <div class="crs-trigger-content">
                                <h4>Registration Complete</h4>
                                <p>Send when payment is successful and registration is confirmed</p>
                                <div class="crs-trigger-options">
                                    <label class="crs-toggle">
                                        <input type="checkbox" name="enable_registration_user" value="1" <?php checked($enable_registration_user, 1); ?>>
                                        <span class="crs-toggle-slider"></span>
                                        <span class="crs-toggle-label">Send to User</span>
                                    </label>
                                    <label class="crs-toggle">
                                        <input type="checkbox" name="enable_registration_admin" value="1" <?php checked($enable_registration_admin, 1); ?>>
                                        <span class="crs-toggle-slider"></span>
                                        <span class="crs-toggle-label">Send to Admin</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Pending Registration -->
                        <div class="crs-trigger-item">
                            <div class="crs-trigger-icon">⏳</div>
                            <div class="crs-trigger-content">
                                <h4>Pending Registration</h4>
                                <p>Send when registration is saved without payment</p>
                                <div class="crs-trigger-options">
                                    <label class="crs-toggle">
                                        <input type="checkbox" name="enable_pending_user" value="1" <?php checked($enable_pending_user, 1); ?>>
                                        <span class="crs-toggle-slider"></span>
                                        <span class="crs-toggle-label">Send to User</span>
                                    </label>
                                    <label class="crs-toggle">
                                        <input type="checkbox" name="enable_pending_admin" value="1" <?php checked($enable_pending_admin, 1); ?>>
                                        <span class="crs-toggle-slider"></span>
                                        <span class="crs-toggle-label">Send to Admin</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Invoice Email -->
                        <div class="crs-trigger-item">
                            <div class="crs-trigger-icon">📄</div>
                            <div class="crs-trigger-content">
                                <h4>Invoice Email</h4>
                                <p>Send invoice manually from booking details page</p>
                                <div class="crs-trigger-options">
                                    <label class="crs-toggle">
                                        <input type="checkbox" name="enable_invoice" value="1" <?php checked($enable_invoice, 1); ?>>
                                        <span class="crs-toggle-slider"></span>
                                        <span class="crs-toggle-label">Enable Invoice Emails</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Failed -->
                        <div class="crs-trigger-item">
                            <div class="crs-trigger-icon">❌</div>
                            <div class="crs-trigger-content">
                                <h4>Payment Failed</h4>
                                <p>Send when payment processing fails</p>
                                <div class="crs-trigger-options">
                                    <label class="crs-toggle">
                                        <input type="checkbox" name="enable_payment_failed" value="1" <?php checked($enable_payment_failed, 1); ?>>
                                        <span class="crs-toggle-slider"></span>
                                        <span class="crs-toggle-label">Send to User</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Email Templates Tab -->
        <div id="tab-templates" class="crs-tab-pane">
            <div class="crs-card">
                <div class="crs-card-header">
                    <h3>Email Templates</h3>
                    <p>Customize your email content</p>
                </div>
                <div class="crs-card-body">
                    <div class="crs-template-selector-wrapper">
                        <label for="template-select">Select Template:</label>
                        <div class="crs-select-wrapper">
                            <select id="template-select" class="crs-select">
                                <option value="registration_user">📧 Registration - User</option>
                                <option value="registration_admin" selected>📧 Registration - Admin</option>
                                <option value="pending_user">⏳ Pending - User</option>
                                <option value="pending_admin">⏳ Pending - Admin</option>
                                <option value="invoice">📄 Invoice</option>
                                <option value="payment_failed">❌ Payment Failed</option>
                            </select>
                            <span class="crs-select-arrow">▼</span>
                        </div>
                    </div>

                    <!-- Template Fields Container -->
                    <div class="crs-templates-container">
                        <?php
                        $templates = [
                            'registration_user' => ['title' => 'Registration Confirmation (User)', 'subject' => $subject_registration_user, 'body' => $body_registration_user],
                            'registration_admin' => ['title' => 'Registration Confirmation (Admin)', 'subject' => $subject_registration_admin, 'body' => $body_registration_admin],
                            'pending_user' => ['title' => 'Pending Registration (User)', 'subject' => $subject_pending_user, 'body' => $body_pending_user],
                            'pending_admin' => ['title' => 'Pending Registration (Admin)', 'subject' => $subject_pending_admin, 'body' => $body_pending_admin],
                            'invoice' => ['title' => 'Invoice Email', 'subject' => $subject_invoice, 'body' => $body_invoice],
                            'payment_failed' => ['title' => 'Payment Failed', 'subject' => $subject_payment_failed, 'body' => $body_payment_failed],
                        ];

                        foreach ($templates as $key => $template): 
                            $display = $key === 'registration_admin' ? 'block' : 'none';
                        ?>
                        <div class="template-fields" id="template-<?php echo $key; ?>" style="display: <?php echo $display; ?>;">
                            <div class="crs-template-header">
                                <h4><?php echo $template['title']; ?></h4>
                                <div class="crs-template-badge">HTML Template</div>
                            </div>
                            
                            <div class="crs-form-group">
                                <label for="subject_<?php echo $key; ?>">Email Subject</label>
                                <input type="text" name="subject_<?php echo $key; ?>" 
                                       id="subject_<?php echo $key; ?>" 
                                       value="<?php echo esc_attr($template['subject']); ?>" 
                                       class="crs-input crs-subject-input">
                            </div>

                            <div class="crs-form-group">
                                <label>Email Body</label>
                                <div class="crs-editor-wrapper">
                                    <?php 
                                    wp_editor($template['body'], 'body_' . $key, [
                                        'textarea_name' => 'body_' . $key,
                                        'textarea_rows' => 20,
                                        'media_buttons' => true,
                                        'teeny' => false,
                                        'quicktags' => true,
                                        'editor_height' => 400,
                                        'editor_class' => 'crs-editor'
                                    ]); 
                                    ?>
                                </div>
                            </div>

                            <!-- Available Tags for this template -->
                            <div class="crs-tags-panel">
                                <div class="crs-tags-header">
                                    <span class="crs-tags-icon">🏷️</span>
                                    <h5>Available Dynamic Tags</h5>
                                    <button type="button" class="crs-tags-toggle">Show Tags</button>
                                </div>
                                <div class="crs-tags-content" style="display: none;">
                                    <div class="crs-tags-grid">
                                        <div class="crs-tag-category">
                                            <h6>User Information</h6>
                                            <code>{user_name}</code> <code>{user_first_name}</code> <code>{user_last_name}</code>
                                            <code>{user_email}</code> <code>{user_phone}</code>
                                        </div>
                                        <div class="crs-tag-category">
                                            <h6>Congress Information</h6>
                                            <code>{congress_name}</code> <code>{congress_start}</code> <code>{congress_end}</code>
                                            <code>{congress_location}</code>
                                        </div>
                                        <div class="crs-tag-category">
                                            <h6>Booking Information</h6>
                                            <code>{booking_number}</code> <code>{booking_date}</code> <code>{total_amount}</code>
                                            <code>{payment_status}</code> <code>{booking_status}</code>
                                        </div>
                                        <div class="crs-tag-category">
                                            <h6>Invoice Information</h6>
                                            <code>{invoice_number}</code> <code>{invoice_amount}</code> <code>{invoice_date}</code>
                                            <code>{invoice_url}</code>
                                        </div>
                                        <div class="crs-tag-category">
                                            <h6>General</h6>
                                            <code>{site_name}</code> <code>{site_url}</code> <code>{current_year}</code>
                                        </div>
                                    </div>
                                    <p class="crs-tags-note">Click on any tag to copy it to clipboard</p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test Email Tab -->
        <div id="tab-test" class="crs-tab-pane">
            <div class="crs-card">
                <div class="crs-card-header">
                    <h3>Test Email Configuration</h3>
                    <p>Send a test email to verify your settings</p>
                </div>
                <div class="crs-card-body">
                    <div class="crs-test-container">
                        <div class="crs-test-grid">
                            <div class="crs-form-group">
                                <label for="test_email">
                                    <span class="label-icon">📨</span>
                                    Send Test Email To
                                </label>
                                <input type="email" id="test_email" class="crs-input" 
                                       value="<?php echo esc_attr($admin_email); ?>" 
                                       placeholder="test@example.com">
                            </div>

                            <div class="crs-form-group">
                                <label for="test_template">
                                    <span class="label-icon">📝</span>
                                    Test Template
                                </label>
                                <div class="crs-select-wrapper">
                                    <select id="test_template" class="crs-select">
                                        <option value="registration_user">📧 Registration - User</option>
                                        <option value="registration_admin">📧 Registration - Admin</option>
                                        <option value="pending_user">⏳ Pending - User</option>
                                        <option value="pending_admin">⏳ Pending - Admin</option>
                                        <option value="invoice">📄 Invoice</option>
                                        <option value="payment_failed">❌ Payment Failed</option>
                                    </select>
                                    <span class="crs-select-arrow">▼</span>
                                </div>
                            </div>
                        </div>

                        <div class="crs-test-actions">
                            <button type="button" id="crs-send-test-email" class="crs-button crs-button-primary">
                                <span class="button-icon">✈️</span>
                                Send Test Email
                            </button>
                            <div id="crs-test-result" class="crs-test-result"></div>
                        </div>

                        <div class="crs-test-info">
                            <div class="crs-info-icon">ℹ️</div>
                            <div class="crs-info-content">
                                <strong>Note:</strong> Test emails will use sample data. Make sure your SMTP settings are configured correctly.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="crs-save-section">
            <button type="submit" name="save_email_settings" class="crs-button crs-button-save">
                Save Email Settings
            </button>
        </div>
    </form>
</div>

<style>
/* Modern CSS with Variables */
:root {
    --primary: #2271b1;
    --primary-dark: #135e96;
    --secondary: #10b981;
    --danger: #ef4444;
    --warning: #f59e0b;
    --dark: #1e293b;
    --gray: #64748b;
    --light-gray: #f1f5f9;
    --border: #e2e8f0;
    --card-bg: #ffffff;
    --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
    --radius: 12px;
    --radius-sm: 8px;
}

/* Main Container */
.crs-email-settings {
    max-width: 1200px;
    margin: 30px 20px 0 2px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
}

/* Header Section */
.crs-settings-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: var(--radius);
    padding: 40px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
    box-shadow: var(--shadow-lg);
}

.crs-header-content {
    flex: 1;
}

.crs-page-title {
    font-size: 32px;
    font-weight: 700;
    color: white;
    margin: 0 0 10px 0;
    display: flex;
    align-items: center;
    gap: 15px;
}

.crs-title-icon {
    font-size: 40px;
    background: rgba(255,255,255,0.2);
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.crs-page-subtitle {
    color: rgba(255,255,255,0.9);
    font-size: 16px;
    margin: 0;
    line-height: 1.5;
}

.crs-status-badge {
    background: rgba(255,255,255,0.2);
    color: white;
    padding: 8px 16px;
    border-radius: 40px;
    font-size: 14px;
    font-weight: 500;
    backdrop-filter: blur(10px);
}

/* Tabs Navigation */
.crs-tabs-container {
    background: var(--card-bg);
    border-radius: var(--radius) var(--radius) 0 0;
    border: 1px solid var(--border);
    border-bottom: none;
    padding: 0 20px;
}

.crs-tabs {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.crs-tabs::-webkit-scrollbar {
    display: none;
}

.crs-tab {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 16px 24px;
    color: var(--gray);
    text-decoration: none;
    font-weight: 500;
    border-bottom: 2px solid transparent;
    transition: all 0.2s;
    white-space: nowrap;
}

.crs-tab .tab-icon {
    font-size: 18px;
}

.crs-tab:hover {
    color: var(--primary);
    background: var(--light-gray);
}

.crs-tab.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
    background: linear-gradient(to top, rgba(34,113,177,0.05), transparent);
}

/* Tab Panes */
.crs-tab-pane {
    display: none;
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-top: none;
    border-radius: 0 0 var(--radius) var(--radius);
    padding: 30px;
}

.crs-tab-pane.active {
    display: block;
}

/* Cards */
.crs-card {
    background: var(--card-bg);
    border-radius: var(--radius);
    margin-bottom: 30px;
}

.crs-card-header {
    padding: 0 0 20px 0;
    border-bottom: 1px solid var(--border);
}

.crs-card-header h3 {
    font-size: 20px;
    font-weight: 600;
    color: var(--dark);
    margin: 0 0 8px 0;
}

.crs-card-header p {
    color: var(--gray);
    margin: 0;
    font-size: 14px;
}

.crs-card-body {
    padding: 20px 0 0 0;
}

/* Form Elements */
.crs-form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 30px;
}

@media (max-width: 768px) {
    .crs-form-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}

.crs-form-group {
    margin-bottom: 20px;
}

.crs-form-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
    color: var(--dark);
    margin-bottom: 8px;
}

.label-icon {
    font-size: 16px;
}

.crs-input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid var(--border);
    border-radius: var(--radius-sm);
    font-size: 14px;
    transition: all 0.2s;
    background: white;
}

.crs-input:focus {
    border-color: var(--primary);
    outline: none;
    box-shadow: 0 0 0 3px rgba(34,113,177,0.1);
}

.crs-field-note {
    color: var(--gray);
    font-size: 12px;
    margin-top: 6px;
}

/* Triggers */
.crs-triggers-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.crs-trigger-item {
    display: flex;
    gap: 20px;
    padding: 20px;
    background: var(--light-gray);
    border-radius: var(--radius-sm);
    border: 1px solid var(--border);
}

.crs-trigger-icon {
    font-size: 32px;
    width: 60px;
    height: 60px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: var(--shadow);
}

.crs-trigger-content {
    flex: 1;
}

.crs-trigger-content h4 {
    font-size: 16px;
    font-weight: 600;
    color: var(--dark);
    margin: 0 0 5px 0;
}

.crs-trigger-content p {
    color: var(--gray);
    font-size: 13px;
    margin: 0 0 15px 0;
}

.crs-trigger-options {
    display: flex;
    gap: 30px;
    flex-wrap: wrap;
}

/* Toggle Switch */
.crs-toggle {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    user-select: none;
}

.crs-toggle input {
    display: none;
}

.crs-toggle-slider {
    position: relative;
    width: 50px;
    height: 26px;
    background: var(--border);
    border-radius: 30px;
    transition: all 0.3s;
}

.crs-toggle-slider:before {
    content: '';
    position: absolute;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: white;
    top: 2px;
    left: 2px;
    transition: all 0.3s;
    box-shadow: var(--shadow);
}

.crs-toggle input:checked + .crs-toggle-slider {
    background: var(--primary);
}

.crs-toggle input:checked + .crs-toggle-slider:before {
    left: 26px;
}

.crs-toggle-label {
    font-size: 14px;
    color: var(--dark);
}

/* Template Selector */
.crs-template-selector-wrapper {
    margin-bottom: 30px;
    padding: 20px;
    background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%);
    border-radius: var(--radius-sm);
    border: 1px solid var(--border);
}

.crs-select-wrapper {
    position: relative;
    display: inline-block;
    width: 100%;
    max-width: 400px;
    margin-top: 10px;
}

.crs-select {
    width: 100%;
    padding: 14px 20px;
    border: 2px solid var(--border);
    border-radius: var(--radius-sm);
    background: white;
    font-size: 15px;
    color: var(--dark);
    appearance: none;
    cursor: pointer;
}

.crs-select-arrow {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray);
    pointer-events: none;
}

/* Template Header */
.crs-template-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 15px;
    background: var(--light-gray);
    border-radius: var(--radius-sm);
}

.crs-template-header h4 {
    font-size: 18px;
    font-weight: 600;
    color: var(--dark);
    margin: 0;
}

.crs-template-badge {
    background: var(--primary);
    color: white;
    padding: 6px 12px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 500;
}

/* Editor Wrapper */
.crs-editor-wrapper {
    border: 2px solid var(--border);
    border-radius: var(--radius-sm);
    overflow: hidden;
}

/* Tags Panel */
.crs-tags-panel {
    margin-top: 20px;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    overflow: hidden;
}

.crs-tags-header {
    background: var(--light-gray);
    padding: 15px 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}

.crs-tags-icon {
    font-size: 20px;
}

.crs-tags-header h5 {
    flex: 1;
    font-size: 15px;
    font-weight: 600;
    color: var(--dark);
    margin: 0;
}

.crs-tags-toggle {
    background: none;
    border: 1px solid var(--border);
    padding: 6px 12px;
    border-radius: 30px;
    font-size: 12px;
    color: var(--gray);
    cursor: pointer;
    transition: all 0.2s;
}

.crs-tags-toggle:hover {
    background: white;
    color: var(--primary);
}

.crs-tags-content {
    padding: 20px;
    border-top: 1px solid var(--border);
}

.crs-tags-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 15px;
}

.crs-tag-category h6 {
    font-size: 13px;
    font-weight: 600;
    color: var(--gray);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0 0 10px 0;
}

.crs-tag-category code {
    display: inline-block;
    background: var(--light-gray);
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    color: var(--primary);
    margin: 0 4px 4px 0;
    cursor: pointer;
    transition: all 0.2s;
}

.crs-tag-category code:hover {
    background: var(--primary);
    color: white;
}

.crs-tags-note {
    color: var(--gray);
    font-size: 12px;
    font-style: italic;
    margin: 10px 0 0 0;
}

/* Test Email */
.crs-test-container {
    max-width: 600px;
}

.crs-test-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 30px;
}

@media (max-width: 600px) {
    .crs-test-grid {
        grid-template-columns: 1fr;
    }
}

.crs-test-actions {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

/* Buttons */
.crs-button {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 24px;
    border: none;
    border-radius: var(--radius-sm);
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.crs-button-primary {
    background: var(--primary);
    color: white;
}

.crs-button-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: var(--shadow);
}

.crs-button-save {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    font-size: 16px;
    padding: 16px 32px;
}

.crs-button-save:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.button-icon {
    font-size: 18px;
}

/* Test Result */
.crs-test-result {
    padding: 10px 20px;
    border-radius: var(--radius-sm);
    font-weight: 500;
    min-width: 200px;
}

.crs-test-result.success {
    background: #d1fae5;
    color: #065f46;
}

.crs-test-result.error {
    background: #fee2e2;
    color: #991b1b;
}

/* Info Box */
.crs-test-info {
    margin-top: 30px;
    padding: 20px;
    background: var(--light-gray);
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    gap: 15px;
    border-left: 4px solid var(--primary);
}

.crs-info-icon {
    font-size: 24px;
}

.crs-info-content {
    color: var(--dark);
    font-size: 14px;
    line-height: 1.6;
}

/* Save Section */
.crs-save-section {
    margin-top: 30px;
}

/* Responsive */
@media (max-width: 782px) {
    .crs-email-settings {
        margin: 20px 10px;
    }
    
    .crs-settings-header {
        padding: 30px 20px;
    }
    
    .crs-page-title {
        font-size: 24px;
    }
    
    .crs-title-icon {
        width: 50px;
        height: 50px;
        font-size: 30px;
    }
    
    .crs-tab {
        padding: 12px 16px;
    }
    
    .crs-tab-pane {
        padding: 20px;
    }
    
    .crs-trigger-item {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .crs-trigger-options {
        justify-content: center;
    }
    
    .crs-tags-grid {
        grid-template-columns: 1fr;
    }
}

/* Animation */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.crs-tab-pane.active {
    animation: slideIn 0.3s ease-out;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.crs-tab').on('click', function(e) {
        e.preventDefault();
        var tab = $(this).data('tab');
        
        $('.crs-tab').removeClass('active');
        $(this).addClass('active');
        
        $('.crs-tab-pane').removeClass('active');
        $('#tab-' + tab).addClass('active');
    });
    
    // Template selector
    $('#template-select').on('change', function() {
        var template = $(this).val();
        $('.template-fields').hide();
        $('#template-' + template).show();
    });
    
    // Tags toggle
    $('.crs-tags-toggle').on('click', function() {
        var $content = $(this).closest('.crs-tags-panel').find('.crs-tags-content');
        $content.slideToggle();
        $(this).text($content.is(':visible') ? 'Hide Tags' : 'Show Tags');
    });
    
    // Copy tag to clipboard
    $('.crs-tag-category code').on('click', function() {
        var tag = $(this).text();
        
        // Copy to clipboard
        var $temp = $('<input>');
        $('body').append($temp);
        $temp.val(tag).select();
        document.execCommand('copy');
        $temp.remove();
        
        // Visual feedback
        var $this = $(this);
        $this.css({
            'background': 'var(--primary)',
            'color': 'white'
        });
        
        setTimeout(function() {
            $this.css({
                'background': '',
                'color': ''
            });
        }, 200);
    });
    
    // Test email
    $('#crs-send-test-email').on('click', function() {
        var $btn = $(this);
        var $result = $('#crs-test-result');
        var testEmail = $('#test_email').val();
        var testTemplate = $('#test_template').val();
        
        if (!testEmail || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(testEmail)) {
            $result.html('<span style="color: #ef4444;">✗ Please enter a valid email address</span>');
            return;
        }
        
        $btn.prop('disabled', true).html('<span class="button-icon">⏳</span> Sending...');
        $result.html('');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'crs_send_test_email',
                to_email: testEmail,
                template: testTemplate,
                nonce: '<?php echo wp_create_nonce('crs_test_email_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $result.html('<span style="color: #10b981;">✓ Test email sent successfully to ' + testEmail + '</span>');
                } else {
                    $result.html('<span style="color: #ef4444;">✗ Failed: ' + (response.data || 'Unknown error') + '</span>');
                }
            },
            error: function() {
                $result.html('<span style="color: #ef4444;">✗ AJAX Error</span>');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<span class="button-icon">✈️</span> Send Test Email');
            }
        });
    });
    
    // Auto-resize iframe for editor
    $(window).on('load', function() {
        setTimeout(function() {
            $('iframe').each(function() {
                $(this).css('height', '400px');
            });
        }, 500);
    });
});
</script>