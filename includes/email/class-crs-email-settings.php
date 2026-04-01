<?php
if (!defined('ABSPATH')) exit;

class CRS_Email_Settings {
    
    public static function renderPage() {
        if (isset($_POST['save_email_settings'])) {
            check_admin_referer('crs_email_settings_nonce');
            self::saveSettings();
            echo '<div class="notice notice-success"><p>Email settings saved successfully.</p></div>';
        }
        
        $from_name = get_option('crs_email_from_name', get_option('blogname'));
        $from_email = get_option('crs_email_from_email', get_option('admin_email'));
        $reply_to = get_option('crs_email_reply_to', get_option('admin_email'));
        $admin_email = get_option('crs_admin_email', get_option('admin_email'));
        
        $enable_registration_user = get_option('crs_enable_registration_user', 1);
        $enable_registration_admin = get_option('crs_enable_registration_admin', 1);
        $enable_pending_user = get_option('crs_enable_pending_user', 1);
        $enable_pending_admin = get_option('crs_enable_pending_admin', 1);
        $enable_invoice = get_option('crs_enable_invoice', 1);
        $enable_payment_failed = get_option('crs_enable_payment_failed', 1);
        
        $subject_registration_user = get_option('crs_subject_registration_user', 'Registration Confirmed: {congress_name}');
        $subject_registration_admin = get_option('crs_subject_registration_admin', 'New Registration: {user_name} - {congress_name}');
        $subject_pending_user = get_option('crs_subject_pending_user', 'Registration Saved: {congress_name} (Pending Payment)');
        $subject_pending_admin = get_option('crs_subject_pending_admin', 'Pending Registration: {user_name} - {congress_name}');
        $subject_invoice = get_option('crs_subject_invoice', 'Invoice #{invoice_number} for {congress_name}');
        $subject_payment_failed = get_option('crs_subject_payment_failed', 'Payment Failed: {congress_name}');
        
        $body_registration_user = get_option('crs_body_registration_user', self::getDefaultTemplate('registration_user'));
        $body_registration_admin = get_option('crs_body_registration_admin', self::getDefaultTemplate('registration_admin'));
        $body_pending_user = get_option('crs_body_pending_user', self::getDefaultTemplate('pending_user'));
        $body_pending_admin = get_option('crs_body_pending_admin', self::getDefaultTemplate('pending_admin'));
        $body_invoice = get_option('crs_body_invoice', self::getDefaultTemplate('invoice'));
        $body_payment_failed = get_option('crs_body_payment_failed', self::getDefaultTemplate('payment_failed'));
        
        include CRS_PLUGIN_DIR . 'templates/admin/email-settings.php';
    }
    
    private static function saveSettings() {
        // General Settings
        update_option('crs_email_from_name', sanitize_text_field($_POST['from_name']));
        update_option('crs_email_from_email', sanitize_email($_POST['from_email']));
        update_option('crs_email_reply_to', sanitize_email($_POST['reply_to']));
        update_option('crs_admin_email', sanitize_email($_POST['admin_email']));
        
        // Enable/Disable
        update_option('crs_enable_registration_user', isset($_POST['enable_registration_user']) ? 1 : 0);
        update_option('crs_enable_registration_admin', isset($_POST['enable_registration_admin']) ? 1 : 0);
        update_option('crs_enable_pending_user', isset($_POST['enable_pending_user']) ? 1 : 0);
        update_option('crs_enable_pending_admin', isset($_POST['enable_pending_admin']) ? 1 : 0);
        update_option('crs_enable_invoice', isset($_POST['enable_invoice']) ? 1 : 0);
        update_option('crs_enable_payment_failed', isset($_POST['enable_payment_failed']) ? 1 : 0);
        
        // Subjects
        update_option('crs_subject_registration_user', sanitize_text_field($_POST['subject_registration_user']));
        update_option('crs_subject_registration_admin', sanitize_text_field($_POST['subject_registration_admin']));
        update_option('crs_subject_pending_user', sanitize_text_field($_POST['subject_pending_user']));
        update_option('crs_subject_pending_admin', sanitize_text_field($_POST['subject_pending_admin']));
        update_option('crs_subject_invoice', sanitize_text_field($_POST['subject_invoice']));
        update_option('crs_subject_payment_failed', sanitize_text_field($_POST['subject_payment_failed']));
        
        // Bodies
        update_option('crs_body_registration_user', wp_kses_post($_POST['body_registration_user']));
        update_option('crs_body_registration_admin', wp_kses_post($_POST['body_registration_admin']));
        update_option('crs_body_pending_user', wp_kses_post($_POST['body_pending_user']));
        update_option('crs_body_pending_admin', wp_kses_post($_POST['body_pending_admin']));
        update_option('crs_body_invoice', wp_kses_post($_POST['body_invoice']));
        update_option('crs_body_payment_failed', wp_kses_post($_POST['body_payment_failed']));
    }
    
    private static function getDefaultTemplate($type) {
        $templates = [
            'registration_user' => '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0; padding:0; background-color:#f5f7fa; font-family: sans-serif;"><table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center"><table width="560" cellpadding="0" cellspacing="0" style="background:#fff; border-radius:12px;"><tr><td style="padding:30px;"><h1 style="color:#1e2f3f;">Thank You, {user_name}!</h1><p>Your registration for <strong>{congress_name}</strong> is confirmed.</p><p>Booking #: {booking_number}<br>Total: {total_amount}</p><p><a href="{site_url}/account/" style="background:#1e2f3f; color:#fff; padding:10px 20px; text-decoration:none; border-radius:5px;">View Registrations</a></p></td></tr><tr><td style="padding:20px; border-top:1px solid #eee; text-align:center; color:#8fa5b8;">© {current_year} {site_name}</td></tr></table></td></tr></table></body></html>',
            'registration_admin' => '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0; padding:0; background-color:#f5f7fa; font-family: sans-serif;"><table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center"><table width="560" cellpadding="0" cellspacing="0" style="background:#fff; border-radius:12px;"><tr><td style="padding:30px;"><h1 style="color:#1e2f3f;">New Registration</h1><p><strong>{user_name}</strong> ({user_email}) registered for <strong>{congress_name}</strong>.</p><p>Booking #: {booking_number}<br>Total: {total_amount}</p><p><a href="{site_url}/wp-admin/admin.php?page=crs-bookings&action=view&id={congress_id}" style="background:#1e2f3f; color:#fff; padding:10px 20px; text-decoration:none; border-radius:5px;">View in Admin</a></p></td></tr></table></td></tr></table></body></html>'
        ];
        
        return $templates[$type] ?? '';
    }
}