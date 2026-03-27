<?php
/**
 * CRS Email System
 * Complete email system for Congress Registration
 */

class CRS_Email {

    private static $instance = null;
    private $settings;
    private $available_tags;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_settings();
        $this->init_tags();
        $this->init_hooks();
    }

    /**
     * Load email settings from database
     */
    private function load_settings() {
        $this->settings = [
            'from_name' => get_option('crs_email_from_name', get_option('blogname')),
            'from_email' => get_option('crs_email_from_email', get_option('admin_email')),
            'reply_to' => get_option('crs_email_reply_to', get_option('admin_email')),
            'admin_email' => get_option('crs_admin_email', get_option('admin_email')),
            
            // Email Enable/Disable
            'enable_registration_user' => get_option('crs_enable_registration_user', 1),
            'enable_registration_admin' => get_option('crs_enable_registration_admin', 1),
            'enable_invoice' => get_option('crs_enable_invoice', 1),
            'enable_payment_failed' => get_option('crs_enable_payment_failed', 1),
            
            // Email Subjects
            'subject_registration_user' => get_option('crs_subject_registration_user', 'Registration Confirmed: {congress_name}'),
            'subject_registration_admin' => get_option('crs_subject_registration_admin', 'New Registration: {user_name} - {congress_name}'),
            'subject_invoice' => get_option('crs_subject_invoice', 'Invoice #{invoice_number} for {congress_name}'),
            'subject_payment_failed' => get_option('crs_subject_payment_failed', 'Payment Failed: {congress_name}'),
            
            // Email Bodies
            'body_registration_user' => get_option('crs_body_registration_user', $this->get_default_template('registration_user')),
            'body_registration_admin' => get_option('crs_body_registration_admin', $this->get_default_template('registration_admin')),
            'body_invoice' => get_option('crs_body_invoice', $this->get_default_template('invoice')),
            'body_payment_failed' => get_option('crs_body_payment_failed', $this->get_default_template('payment_failed')),
        ];
        

        }

    /**
     * Save all settings to database 
     */
    public function save_settings($settings) {
        foreach ($settings as $key => $value) {
            update_option('crs_' . $key, $value);
        }
        $this->settings = $settings;
    }

    /**
     * Get admin single booking button HTML
     */
    private function get_admin_booking_btn($booking_id) {
        $button = '<table cellpadding="0" cellspacing="0" border="0" style="margin:0 auto;">
            <tr>
                <td style="border-radius: 50px; background: #1763cf;">
                    <a href="' . admin_url('admin.php?page=crs-bookings&action=view&id=' . $booking_id) . '" 
                    style="display: inline-block; background-color: #1763cf; color: #ffffff; text-decoration: none; padding: 14px 38px; border-radius: 50px; font-size: 15px; font-weight: 460; letter-spacing: 0.25px; border: 1px solid #4080dd; box-shadow: 0 6px 14px rgba(23,99,207,0.28);">
                        ↗︎ open admin panel
                    </a>
                </td>
            </tr>
        </table>';
        
        return $button;
    }

    /**
     * Initialize dynamic tags
     */
    private function init_tags() {
        $this->available_tags = [
            '{user_name}' => 'User Display Name',
            '{user_first_name}' => 'User First Name',
            '{user_last_name}' => 'User Last Name',
            '{user_email}' => 'User Email',
            '{user_phone}' => 'User Phone',
            
            '{congress_name}' => 'Congress Title',
            '{congress_start}' => 'Start Date',
            '{congress_end}' => 'End Date',
            '{congress_location}' => 'Location',
            
            '{booking_number}' => 'Booking Number',
            '{booking_date}' => 'Booking Date',
            '{payment_status}' => 'Payment Status',
            '{total_amount}' => 'Total Amount',
            
            '{invoice_number}' => 'Invoice Number',
            '{invoice_amount}' => 'Invoice Amount',
            '{invoice_date}' => 'Invoice Date',
            '{invoice_url}' => 'Invoice URL',
            
            '{registration_type}' => 'Registration Type',
            '{selected_hotel}' => 'Selected Hotel',
            '{check_in_date}' => 'Check-in Date',
            '{check_out_date}' => 'Check-out Date',
            '{nights}' => 'Number of Nights',
            
            '{admin_url}' => 'Admin URL',
            '{site_name}' => 'Site Name',
            '{site_url}' => 'Site URL',
            '{current_year}' => 'Current Year',
            '{admin_single_booking_btn}' => 'Admin Single Booking Button (with design)',

        ];
    }

    /**
     * Register hooks
     */
    private function init_hooks() {
        // Registration Events
        add_action('crs_after_registration_complete', [$this, 'send_registration_emails'], 10, 2);
        add_action('crs_payment_success', [$this, 'send_invoice_email'], 10, 2);
        add_action('crs_payment_failed', [$this, 'send_payment_failed_email'], 10, 2);
        
        // Admin AJAX
        add_action('wp_ajax_crs_send_invoice', [$this, 'ajax_send_invoice']);
        add_action('wp_ajax_crs_send_test_email', [$this, 'ajax_send_test_email']);



    }



    /**
     * Send registration emails (user and admin)
     */
    public function send_registration_emails($booking_id, $booking) {
        error_log('Sending registration emails - Booking ID: ' . $booking_id);
        
        $user = get_userdata($booking->user_id);
        if (!$user) {
            error_log('User not found');
            return;
        }
        
        $data = $this->prepare_email_data($booking_id, $booking, $user);
        
        // Send to user
        if (!empty($this->settings['enable_registration_user'])) {
            $this->send(
                $user->user_email,
                $this->settings['subject_registration_user'],
                $this->settings['body_registration_user'],
                $data
            );
        }
        
        // Send to admin
        if (!empty($this->settings['enable_registration_admin'])) {
            $this->send(
                $this->settings['admin_email'],
                $this->settings['subject_registration_admin'],
                $this->settings['body_registration_admin'],
                $data
            );
        }
    }

    /**
     * Send invoice email on payment success - AUTOMATIC
     */
    public function send_invoice_email($booking_id, $booking) {
        error_log('=== AUTO INVOICE TRIGGERED ===');
        error_log('Booking ID: ' . $booking_id);
        error_log('Payment Status: ' . ($booking->payment_status ?? 'unknown'));
        
        // Check if invoice emails are enabled
        if (empty($this->settings['enable_invoice'])) {
            return;
        }
        
        // Get user data
        $user = get_userdata($booking->user_id);
        if (!$user) {
            error_log('User not found for booking ID: ' . $booking_id);
            return;
        }
        
        error_log('User found: ' . $user->user_email);
        
        // Prepare email data
        $data = $this->prepare_email_data($booking_id, $booking, $user);
        
        // Add invoice-specific data
        $data['invoice_number'] = 'INV-' . $booking->booking_number . '-' . date('Ymd');
        $data['invoice_date'] = date_i18n(get_option('date_format'));
        $data['invoice_url'] = home_url('/my-documents/?invoice=' . $booking->booking_number);
        
        // Check if invoice already sent (avoid duplicates)
        $already_sent = get_post_meta($booking_id, '_crs_invoice_sent', true);
        if ($already_sent) {
            error_log('Invoice already sent for booking ID: ' . $booking_id . ' at ' . $already_sent);
            return;
        }
        
        // Send email
        $sent = $this->send(
            $user->user_email,
            $this->settings['subject_invoice'],
            $this->settings['body_invoice'],
            $data
        );
        
        if ($sent) {
            // Mark as sent
            update_post_meta($booking_id, '_crs_invoice_sent', current_time('mysql'));
            update_post_meta($booking_id, '_crs_invoice_number', $data['invoice_number']);
            error_log('✓ Invoice sent successfully to: ' . $user->user_email);
        } else {
            error_log('✗ Failed to send invoice to: ' . $user->user_email);
        }
    }

    /**
     * Send payment failed email
     */
    public function send_payment_failed_email($booking_id, $booking) {
        error_log('Sending payment failed email - Booking ID: ' . $booking_id);
        
        if (empty($this->settings['enable_payment_failed'])) {
            return;
        }
        
        $user = get_userdata($booking->user_id);
        if (!$user) return;
        
        $data = $this->prepare_email_data($booking_id, $booking, $user);
        
        $this->send(
            $user->user_email,
            $this->settings['subject_payment_failed'],
            $this->settings['body_payment_failed'],
            $data
        );
    }

    /**
     * AJAX - Send invoice manually
     */
    public function ajax_send_invoice() {
        check_ajax_referer('crs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $booking_id = intval($_POST['booking_id']);

        
        global $wpdb;
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cr_bookings WHERE id = %d",
            $booking_id
        ));
        
        if (!$booking) {
            wp_send_json_error('Booking not found');
        }
        
        $user = get_userdata($booking->user_id);
        $data = $this->prepare_email_data($booking_id, $booking, $user);
        $data['invoice_number'] = 'INV-' . $booking->booking_number;
        $data['invoice_date'] = date_i18n(get_option('date_format'));
        $data['invoice_url'] = home_url('/my-documents/');
        
        $sent = $this->send(
            $user->user_email,
            $this->settings['subject_invoice'],
            $this->settings['body_invoice'],
            $data
        );
        
        if ($sent) {
            update_post_meta($booking_id, '_crs_invoice_sent', current_time('mysql'));
            wp_send_json_success(['message' => 'Invoice sent successfully']);
        } else {
            wp_send_json_error('Failed to send invoice');
        }
    }

    /**
     * AJAX - Send test email
     */
    public function ajax_send_test_email() {
        check_ajax_referer('crs_test_email_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $to_email = sanitize_email($_POST['to_email']);
        $template = sanitize_text_field($_POST['template']);
        
        if (!is_email($to_email)) {
            wp_send_json_error('Invalid email address');
        }
        
        // Get template data
        $subject = $this->settings['subject_' . $template] ?? 'Test Email';
        $body = $this->settings['body_' . $template] ?? $this->get_default_template($template);
        
        // Sample data for testing
        $sample_data = [
            'user_name' => 'John Doe',
            'user_first_name' => 'John',
            'user_last_name' => 'Doe',
            'user_email' => 'john@example.com',
            'user_phone' => '+1234567890',
            
            'congress_name' => 'SIDI Congress 2026',
            'congress_start' => 'March 1, 2026',
            'congress_end' => 'March 14, 2026',
            'congress_location' => 'Santa Cruz de Tenerife',
            
            'booking_number' => 'CR-123456789',
            'booking_date' => date_i18n(get_option('date_format')),
            'payment_status' => 'completed',
            'total_amount' => wc_price(375),
            
            'invoice_number' => 'INV-2026-001',
            'invoice_amount' => wc_price(375),
            'invoice_date' => date_i18n(get_option('date_format')),
            'invoice_url' => home_url('/my-documents/'),
            
            'registration_type' => 'Standard',
            'selected_hotel' => 'Hotel Example',
            'check_in_date' => '2026-03-01',
            'check_out_date' => '2026-03-05',
            'nights' => 4,
            
            'admin_url' => admin_url('admin.php?page=crs-bookings'),
            'site_name' => get_option('blogname'),
            'site_url' => home_url(),
            'current_year' => date('Y'),
        ];
        
        $sent = $this->send($to_email, $subject, $body, $sample_data);
        
        if ($sent) {
            wp_send_json_success('Test email sent');
        } else {
            wp_send_json_error('Failed to send email');
        }
    }

    /**
     * ========== EMAIL SENDING UTILITY ==========
     */

    /**
     * Main email sending function
     */
    private function send($to, $subject, $body, $data) {
        error_log('Sending email to: ' . $to);
        
        // Replace tags
        $subject = $this->replace_tags($subject, $data);
        $body = $this->replace_tags($body, $data);
        
        // Get full HTML email
        $full_html = $this->get_email_html($body, $data);
        
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->settings['from_name'] . ' <' . $this->settings['from_email'] . '>',
            'Reply-To: ' . $this->settings['reply_to'],
        ];
        
        $result = wp_mail($to, $subject, $full_html, $headers);
        error_log('Email send result: ' . ($result ? 'Success' : 'Failed'));
        
        return $result;
    }

    /**
     * Get full HTML email with template
     */
    private function get_email_html($content, $data) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body { margin: 0; padding: 0; background-color: #f4f6f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
                .email-wrapper { margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
                .email-header { background: linear-gradient(135deg, #1a2b3c 0%, #2c3e50 100%); padding: 25px; text-align: center; }
                .email-header h1 { color: #ffffff; margin: 0; font-size: 24px; font-weight: 500; }
                .email-body { padding: 40px; }
                .email-body h2 { color: #1a2b3c; font-size: 22px; margin-top: 0; }
                .email-body p { color: #4a5568; line-height: 1.6; }
                .email-footer { background-color: #f8fafc; padding: 20px 40px; text-align: center; border-top: 1px solid #e2e8f0; }
                .email-footer p { margin: 0; color: #64748b; font-size: 14px; }
                .button { display: inline-block; padding: 12px 30px; background-color: #1a2b3c; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 500; }
                .button:hover { background-color: #2c3e50; }
                .info-box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin: 20px 0; }
                .info-box p { margin: 5px 0; }
            </style>
        </head>
        <body>
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f4f6f9; padding: 40px 20px;">
                <tr>
                    <td align="center">
                        <div class="email-wrapper">
   
                            <div class="email-body">
                                <?php echo $content; ?>
                            </div>
            
                        </div>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Prepare email data from booking
     */
    private function prepare_email_data($booking_id, $booking, $user) {
        $additional = json_decode($booking->additional_options ?? '', true);
        
        // Get registration type
        $registration_type = '';
        if (!empty($additional['registration_type_id'])) {
            global $wpdb;
            $type = $wpdb->get_var($wpdb->prepare(
                "SELECT name FROM {$wpdb->prefix}registration_types WHERE id = %d",
                $additional['registration_type_id']
            ));
            $registration_type = $type ?: '';
        }
        
        // Get hotel details
        $selected_hotel = '';
        $nights = 0;
        if (!empty($booking->selected_hotel_id) && $booking->selected_hotel_id != 0) {
            $hotel = get_post($booking->selected_hotel_id);
            $selected_hotel = $hotel ? $hotel->post_title : '';
            
            if ($booking->check_in_date && $booking->check_out_date) {
                $nights = ceil((strtotime($booking->check_out_date) - strtotime($booking->check_in_date)) / DAY_IN_SECONDS);
            }
        }


                
        $data =  [
            'user_name' => $user->display_name,
            'user_first_name' => $user->first_name,
            'user_last_name' => $user->last_name,
            'user_email' => $user->user_email,
            'user_phone' => $additional['personal_data']['phone'] ?? '',
            
            'congress_name' => get_the_title($booking->congress_id),
            'congress_start' => get_post_meta($booking->congress_id, 'start_date', true),
            'congress_end' => get_post_meta($booking->congress_id, 'end_date', true),
            'congress_location' => get_post_meta($booking->congress_id, 'address', true),
            
            'booking_number' => $booking->booking_number,
            'booking_date' => date_i18n(get_option('date_format'), strtotime($booking->created_at)),
            'payment_status' => $booking->payment_status,
            'total_amount' => $booking->total_amount,
            
            'registration_type' => $registration_type,
            'selected_hotel' => $selected_hotel,
            'check_in_date' => $booking->check_in_date,
            'check_out_date' => $booking->check_out_date,
            'nights' => $nights,
            
            'admin_url' => admin_url('admin.php?page=crs-bookings&action=view&id=' . $booking_id),
            'site_name' => get_option('blogname'),
            'site_url' => home_url(),
            'current_year' => date('Y'),
            'booking_id' => $booking_id,
            'admin_single_booking_btn' => $this->get_admin_booking_btn($booking_id),
        ];

        return $data;

    }

    /**
     * Replace tags in content
     */
    private function replace_tags($content, $data) {
        foreach ($this->available_tags as $tag => $label) {
            $key = str_replace(['{', '}'], '', $tag);
            $value = $data[$key] ?? '';
            $content = str_replace($tag, $value, $content);
        }
        return $content;
    }

    /**
     * ========== DEFAULT TEMPLATES ==========
     */

    private function get_default_template($type) {
        $templates = [
            'registration_user' => '<h2>Default Thank You, {user_name}!</h2>
                <p>Your registration for <strong>{congress_name}</strong> is confirmed.</p>
                
                <div class="info-box">
                    <p><strong>Booking Number:</strong> {booking_number}</p>
                    <p><strong>Total Amount:</strong> {total_amount}</p>
                    <p><strong>Dates:</strong> {congress_start} - {congress_end}</p>
                    <p><strong>Location:</strong> {congress_location}</p>
                    <p><strong>Registration Type:</strong> {registration_type}</p>
                </div>
                
                <p>You can view your registration details in your account dashboard.</p>
                
                <p style="margin-top: 30px; text-align: center;">
                    <a href="{site_url}/my-registrations/" class="button">View My Registrations</a>
                </p>',
                
            'registration_admin' => '<h2>New Registration</h2>
                <p><strong>{user_name}</strong> ({user_email}) has registered for <strong>{congress_name}</strong>.</p>
                
                <div class="info-box">
                    <p><strong>Booking Number:</strong> {booking_number}</p>
                    <p><strong>Total Amount:</strong> {total_amount}</p>
                    <p><strong>Registration Type:</strong> {registration_type}</p>
                    <p><strong>Hotel:</strong> {selected_hotel}</p>
                    <p><strong>Check-in/out:</strong> {check_in_date} to {check_out_date}</p>
                </div>
                
                <p style="margin-top: 30px; text-align: center;">
                    <a href="{admin_url}" class="button">View in Admin</a>
                </p>',
                
            'invoice' => '<h2>Invoice #{invoice_number}</h2>
                <p>Your invoice for <strong>{congress_name}</strong> is ready.</p>
                
                <div class="info-box">
                    <p><strong>Invoice Number:</strong> {invoice_number}</p>
                    <p><strong>Amount:</strong> {total_amount}</p>
                    <p><strong>Date:</strong> {invoice_date}</p>
                    <p><strong>Booking Reference:</strong> {booking_number}</p>
                </div>
                
                <p>You can download your invoice from your documents section.</p>
                
                <p style="margin-top: 30px; text-align: center;">
                    <a href="{invoice_url}" class="button">View Invoice</a>
                </p>',
                
            'payment_failed' => '<h2>Payment Failed</h2>
                <p>Your payment for <strong>{congress_name}</strong> was not successful.</p>
                
                <div class="info-box">
                    <p><strong>Booking Number:</strong> {booking_number}</p>
                    <p><strong>Amount:</strong> {total_amount}</p>
                </div>
                
                <p>Please try again or contact support if you need assistance.</p>
                
                <p style="margin-top: 30px; text-align: center;">
                    <a href="{site_url}/my-registrations/" class="button">Try Again</a>
                </p>',
        ];
        
        return $templates[$type] ?? '';
    }

    /**
     * ========== ADMIN SETTINGS PAGE ==========
     */

    public function add_settings_page() {
        add_submenu_page(
            'congress-registration',
            'Email Settings',
            'Email',
            'manage_options',
            'crs-email-settings',
            [$this, 'render_settings_page']
        );
    }

    public function render_settings_page() {
        // Save settings
        if (isset($_POST['save_email_settings'])) {
            check_admin_referer('crs_email_settings');
            
            $settings = [
                'from_name' => sanitize_text_field($_POST['from_name']),
                'from_email' => sanitize_email($_POST['from_email']),
                'reply_to' => sanitize_email($_POST['reply_to']),
                'admin_email' => sanitize_email($_POST['admin_email']),
                
                'enable_registration_user' => isset($_POST['enable_registration_user']) ? 1 : 0,
                'enable_registration_admin' => isset($_POST['enable_registration_admin']) ? 1 : 0,
                'enable_invoice' => isset($_POST['enable_invoice']) ? 1 : 0,
                'enable_payment_failed' => isset($_POST['enable_payment_failed']) ? 1 : 0,
                
                'subject_registration_user' => sanitize_text_field($_POST['subject_registration_user']),
                'subject_registration_admin' => sanitize_text_field($_POST['subject_registration_admin']),
                'subject_invoice' => sanitize_text_field($_POST['subject_invoice']),
                'subject_payment_failed' => sanitize_text_field($_POST['subject_payment_failed']),
                
                'body_registration_user' => wp_kses_post($_POST['body_registration_user']),
                'body_registration_admin' => wp_kses_post($_POST['body_registration_admin']),
                'body_invoice' => wp_kses_post($_POST['body_invoice']),
                'body_payment_failed' => wp_kses_post($_POST['body_payment_failed']),
            ];
            
            update_option('crs_email_settings', $settings);
            $this->settings = $settings;
            
            echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully.</p></div>';
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Email Settings', 'crs'); ?></h1>
            
            <div class="nav-tab-wrapper">
                <a href="#general" class="nav-tab nav-tab-active" id="tab-general-btn">General</a>
                <a href="#templates" class="nav-tab" id="tab-templates-btn">Templates</a>
                <a href="#tags" class="nav-tab" id="tab-tags-btn">Available Tags</a>
                <a href="#test" class="nav-tab" id="tab-test-btn">Test Email</a>
            </div>
            
            <form method="post" action="">
                <?php wp_nonce_field('crs_email_settings'); ?>
                
                <!-- General Tab -->
                <div id="tab-general" class="tab-content" style="display: block; background: #fff; padding: 20px; border: 1px solid #c3c4c7; border-top: none;">
                    <h2>General Settings</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="from_name">From Name</label></th>
                            <td>
                                <input type="text" name="from_name" id="from_name" 
                                       value="<?php echo esc_attr($this->settings['from_name']); ?>" class="regular-text">
                                <p class="description">The name that emails will come from</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="from_email">From Email</label></th>
                            <td>
                                <input type="email" name="from_email" id="from_email" 
                                       value="<?php echo esc_attr($this->settings['from_email']); ?>" class="regular-text">
                                <p class="description">The email address that emails will come from</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="reply_to">Reply-To Email</label></th>
                            <td>
                                <input type="email" name="reply_to" id="reply_to" 
                                       value="<?php echo esc_attr($this->settings['reply_to']); ?>" class="regular-text">
                                <p class="description">The email address for replies</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="admin_email">Admin Email</label></th>
                            <td>
                                <input type="email" name="admin_email" id="admin_email" 
                                       value="<?php echo esc_attr($this->settings['admin_email']); ?>" class="regular-text">
                                <p class="description">Where admin notifications will be sent</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Enable Emails</th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="enable_registration_user" value="1" <?php checked($this->settings['enable_registration_user'], 1); ?>>
                                        Registration - User
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="enable_registration_admin" value="1" <?php checked($this->settings['enable_registration_admin'], 1); ?>>
                                        Registration - Admin
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="enable_invoice" value="1" <?php checked($this->settings['enable_invoice'], 1); ?>>
                                        Invoice Email
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="enable_payment_failed" value="1" <?php checked($this->settings['enable_payment_failed'], 1); ?>>
                                        Payment Failed
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Templates Tab -->
                <div id="tab-templates" class="tab-content" style="display: none; background: #fff; padding: 20px; border: 1px solid #c3c4c7; border-top: none;">
                    <h2>Email Templates</h2>
                    
                    <div style="margin-bottom: 20px; padding: 15px; background: #f0f6fc; border-left: 4px solid #2271b1;">
                        <label for="template_selector">Select Template: </label>
                        <select id="template_selector" class="regular-text">
                            <option value="registration_user">Registration - User</option>
                            <option value="registration_admin">Registration - Admin</option>
                            <option value="invoice">Invoice</option>
                            <option value="payment_failed">Payment Failed</option>
                        </select>
                    </div>
                    
                    <!-- Registration User Template -->
                    <div id="template-registration_user" class="template-field">
                        <h3>Registration User Email</h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="subject_registration_user">Subject</label></th>
                                <td>
                                    <input type="text" name="subject_registration_user" id="subject_registration_user" 
                                           value="<?php echo esc_attr($this->settings['subject_registration_user']); ?>" class="regular-text" style="width: 100%;">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="body_registration_user">Body</label></th>
                                <td>
                                    <?php
                                    wp_editor($this->settings['body_registration_user'], 'body_registration_user', [
                                        'textarea_name' => 'body_registration_user',
                                        'textarea_rows' => 15,
                                        'media_buttons' => true,
                                        'teeny' => false,
                                        'quicktags' => true,
                                    ]);
                                    ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Registration Admin Template -->
                    <div id="template-registration_admin" class="template-field" style="display: none;">
                        <h3>Registration Admin Email</h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="subject_registration_admin">Subject</label></th>
                                <td>
                                    <input type="text" name="subject_registration_admin" id="subject_registration_admin" 
                                           value="<?php echo esc_attr($this->settings['subject_registration_admin']); ?>" class="regular-text" style="width: 100%;">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="body_registration_admin">Body</label></th>
                                <td>
                                    <?php
                                    wp_editor($this->settings['body_registration_admin'], 'body_registration_admin', [
                                        'textarea_name' => 'body_registration_admin',
                                        'textarea_rows' => 15,
                                        'media_buttons' => true,
                                        'teeny' => false,
                                        'quicktags' => true,
                                    ]);
                                    ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Invoice Template -->
                    <div id="template-invoice" class="template-field" style="display: none;">
                        <h3>Invoice Email</h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="subject_invoice">Subject</label></th>
                                <td>
                                    <input type="text" name="subject_invoice" id="subject_invoice" 
                                           value="<?php echo esc_attr($this->settings['subject_invoice']); ?>" class="regular-text" style="width: 100%;">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="body_invoice">Body</label></th>
                                <td>
                                    <?php
                                    wp_editor($this->settings['body_invoice'], 'body_invoice', [
                                        'textarea_name' => 'body_invoice',
                                        'textarea_rows' => 15,
                                        'media_buttons' => true,
                                        'teeny' => false,
                                        'quicktags' => true,
                                    ]);
                                    ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Payment Failed Template -->
                    <div id="template-payment_failed" class="template-field" style="display: none;">
                        <h3>Payment Failed Email</h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="subject_payment_failed">Subject</label></th>
                                <td>
                                    <input type="text" name="subject_payment_failed" id="subject_payment_failed" 
                                           value="<?php echo esc_attr($this->settings['subject_payment_failed']); ?>" class="regular-text" style="width: 100%;">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="body_payment_failed">Body</label></th>
                                <td>
                                    <?php
                                    wp_editor($this->settings['body_payment_failed'], 'body_payment_failed', [
                                        'textarea_name' => 'body_payment_failed',
                                        'textarea_rows' => 15,
                                        'media_buttons' => true,
                                        'teeny' => false,
                                        'quicktags' => true,
                                    ]);
                                    ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- Tags Tab -->
                <div id="tab-tags" class="tab-content" style="display: none; background: #fff; padding: 20px; border: 1px solid #c3c4c7; border-top: none;">
                    <h2>Available Dynamic Tags</h2>
                    <p>Use these tags in your email subjects and bodies. They will be replaced with actual data.</p>
                    
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th>Tag</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->available_tags as $tag => $desc): ?>
                            <tr>
                                <td><code><?php echo $tag; ?></code></td>
                                <td><?php echo $desc; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Test Tab -->
                <div id="tab-test" class="tab-content" style="display: none; background: #fff; padding: 20px; border: 1px solid #c3c4c7; border-top: none;">
                    <h2>Test Email</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="test_email_to">Send To</label></th>
                            <td>
                                <input type="email" id="test_email_to" class="regular-text" value="<?php echo esc_attr($this->settings['admin_email']); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="test_template_select">Template</label></th>
                            <td>
                                <select id="test_template_select" class="regular-text">
                                    <option value="registration_user">Registration - User</option>
                                    <option value="registration_admin">Registration - Admin</option>
                                    <option value="invoice">Invoice</option>
                                    <option value="payment_failed">Payment Failed</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <p>
                        <button type="button" id="send_test_email" class="button button-primary">Send Test Email</button>
                        <span id="test_result" style="margin-left: 10px;"></span>
                    </p>
                </div>
                
                <p class="submit">
                    <input type="submit" name="save_email_settings" class="button button-primary" value="Save Email Settings">
                </p>
            </form>
        </div>
        
        <style>
        .tab-content { background: #fff; padding: 20px; border: 1px solid #c3c4c7; border-top: none; }
        .nav-tab-wrapper { margin-bottom: 0; }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Tab switching
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                var tab = $(this).attr('href').substring(1);
                
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                $('.tab-content').hide();
                $('#tab-' + tab).show();
            });
            
            // Template selector
            $('#template_selector').on('change', function() {
                var template = $(this).val();
                $('.template-field').hide();
                $('#template-' + template).show();
            });
            
            // Send test email
            $('#send_test_email').on('click', function() {
                var $btn = $(this);
                var $result = $('#test_result');
                
                $btn.prop('disabled', true).text('Sending...');
                $result.html('');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'crs_send_test_email',
                        to_email: $('#test_email_to').val(),
                        template: $('#test_template_select').val(),
                        nonce: '<?php echo wp_create_nonce('crs_test_email_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $result.html('<span style="color: green;">✓ Test email sent successfully!</span>');
                        } else {
                            $result.html('<span style="color: red;">✗ Failed: ' + response.data + '</span>');
                        }
                    },
                    error: function() {
                        $result.html('<span style="color: red;">✗ Failed to send test email</span>');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('Send Test Email');
                    }
                });
            });
        });
        </script>
        <?php
    }
}

// Initialize
CRS_Email::get_instance();