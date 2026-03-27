<?php
if (!defined('ABSPATH')) exit;

class CRS_Email_Core {
    
    private static $instance = null;
    private $settings;
    
    public static function init() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->settings = get_option('crs_email_settings', []);
        $this->registerHooks();
    }
    
    private function registerHooks() {
        add_action('crs_after_registration_complete', [$this, 'sendRegistrationCompleteEmails'], 10, 2);
        add_action('crs_after_pending_registration', [$this, 'sendPendingEmails'], 10, 2);
        add_action('crs_payment_failed', [$this, 'sendPaymentFailedEmail'], 10, 2);
        add_action('crs_manual_invoice', [$this, 'sendInvoiceEmail'], 10, 2);
        add_action('crs_payment_success', [$this, 'sendInvoiceEmail'], 10, 2);
    }
    
    public function sendRegistrationCompleteEmails($booking_id, $booking) {
        if (get_option('crs_enable_registration_user', 1)) {
            $this->sendEmail('registration_user', $booking_id, $booking);
        }
        if (get_option('crs_enable_registration_admin', 1)) {
            $this->sendEmail('registration_admin', $booking_id, $booking);
        }
    }
    
    public function sendPendingEmails($booking_id, $booking) {
        if (get_option('crs_enable_pending_user', 1)) {
            $this->sendEmail('pending_user', $booking_id, $booking);
        }
        if (get_option('crs_enable_pending_admin', 1)) {
            $this->sendEmail('pending_admin', $booking_id, $booking);
        }
    }
    
    public function sendPaymentFailedEmail($booking_id, $booking) {
        if (get_option('crs_enable_payment_failed', 1)) {
            $this->sendEmail('payment_failed', $booking_id, $booking);
        }
    }
    
    public function sendInvoiceEmail($booking_id, $booking) {
        if (get_option('crs_enable_invoice', 1)) {
            $invoice_number = get_post_meta($booking_id, '_crs_invoice_number', true);
            if (!$invoice_number) {
                $invoice_number = 'INV-' . $booking->booking_number;
                update_post_meta($booking_id, '_crs_invoice_number', $invoice_number);
            }
            $this->sendEmail('invoice', $booking_id, $booking, $invoice_number);
        }
    }
    
    private function sendEmail($type, $booking_id, $booking, $invoice_number = '') {
        $user = get_userdata($booking->user_id);
        if (!$user) return false;
        
        $to = $this->getRecipientEmail($type, $user->user_email);
        if (!$to) return false;
        
        $subject = get_option('crs_subject_' . $type, '');
        $body = get_option('crs_body_' . $type, '');
        
        if (empty($subject) || empty($body)) return false;
        
        $placeholders = $this->getPlaceholders($booking_id, $booking, $user, $invoice_number);
        
        $subject = str_replace(array_keys($placeholders), array_values($placeholders), $subject);
        $body = str_replace(array_keys($placeholders), array_values($placeholders), $body);
        
        $from_name = get_option('crs_email_from_name', get_option('blogname'));
        $from_email = get_option('crs_email_from_email', get_option('admin_email'));
        $reply_to = get_option('crs_email_reply_to', get_option('admin_email'));
        
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            "From: $from_name <$from_email>",
            "Reply-To: $reply_to"
        ];
        
        return wp_mail($to, $subject, $body, $headers);
    }
    
    private function getRecipientEmail($type, $user_email) {
        if (strpos($type, 'admin') !== false) {
            return get_option('crs_admin_email', get_option('admin_email'));
        }
        return $user_email;
    }
    
    private function getPlaceholders($booking_id, $booking, $user, $invoice_number) {
        $additional_options = json_decode($booking->additional_options, true);
        $congress = get_post($booking->congress_id);
        $congress_start = get_post_meta($booking->congress_id, 'start_date', true);
        $congress_end = get_post_meta($booking->congress_id, 'end_date', true);
        $congress_location = get_post_meta($booking->congress_id, 'location', true);
        
        $hotel_name = '';
        if ($booking->selected_hotel_id) {
            $hotel = get_post($booking->selected_hotel_id);
            $hotel_name = $hotel ? $hotel->post_title : '';
        }
        
        return [
            '{user_name}' => $user->display_name,
            '{user_first_name}' => $user->first_name,
            '{user_last_name}' => $user->last_name,
            '{user_email}' => $user->user_email,
            '{user_id}' => $user->ID,
            '{user_phone}' => $additional_options['personal_data']['phone'] ?? '',
            '{congress_name}' => $congress ? $congress->post_title : '',
            '{congress_start}' => $congress_start ? date_i18n(get_option('date_format'), strtotime($congress_start)) : '',
            '{congress_end}' => $congress_end ? date_i18n(get_option('date_format'), strtotime($congress_end)) : '',
            '{congress_location}' => $congress_location ?: '',
            '{congress_id}' => $booking->congress_id,
            '{booking_number}' => $booking->booking_number,
            '{booking_date}' => date_i18n(get_option('date_format'), strtotime($booking->created_at)),
            '{payment_status}' => $booking->payment_status,
            '{booking_status}' => $booking->booking_status,
            '{total_amount}' => wc_price($booking->total_amount),
            '{invoice_number}' => $invoice_number,
            '{invoice_amount}' => wc_price($booking->total_amount),
            '{invoice_date}' => date_i18n(get_option('date_format')),
            '{registration_type}' => $additional_options['registration_type_id'] ?? '',
            '{selected_hotel}' => $hotel_name,
            '{check_in_date}' => $booking->check_in_date ? date_i18n(get_option('date_format'), strtotime($booking->check_in_date)) : '',
            '{check_out_date}' => $booking->check_out_date ? date_i18n(get_option('date_format'), strtotime($booking->check_out_date)) : '',
            '{nights}' => $this->calculateNights($booking->check_in_date, $booking->check_out_date),
            '{site_name}' => get_option('blogname'),
            '{site_url}' => home_url(),
            '{current_year}' => date('Y'),
            '{admin_url}' => admin_url('admin.php?page=crs-bookings&action=view&id=' . $booking_id),
            '{admin_single_booking_btn}' => '<a href="' . admin_url('admin.php?page=crs-bookings&action=view&id=' . $booking_id) . '" style="background:#1e2f3f; color:#fff; padding:10px 20px; text-decoration:none; border-radius:5px;">View Booking</a>',
            '{invoice_url}' => $this->getInvoiceUrl($booking_id)
        ];
    }
    
    private function calculateNights($check_in, $check_out) {
        if (empty($check_in) || empty($check_out)) return 0;
        return ceil((strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24));
    }
    
    private function getInvoiceUrl($booking_id) {
        global $wpdb;
        $doc = $wpdb->get_var($wpdb->prepare(
            "SELECT document_url FROM {$wpdb->prefix}cr_documents WHERE booking_id = %d AND document_type = 'invoice' LIMIT 1",
            $booking_id
        ));
        return $doc ?: '';
    }
    
    public static function sendTestEmail($to_email, $template) {
        $subject = get_option('crs_subject_' . $template, 'Test Email');
        $body = get_option('crs_body_' . $template, '');
        
        $sample_data = [
            '{user_name}' => 'John Doe',
            '{user_first_name}' => 'John',
            '{user_last_name}' => 'Doe',
            '{user_email}' => 'john@example.com',
            '{congress_name}' => 'SIDI Congress 2026',
            '{congress_start}' => 'March 1, 2026',
            '{congress_end}' => 'March 14, 2026',
            '{congress_location}' => 'Santa Cruz',
            '{booking_number}' => 'CR-123456789',
            '{booking_date}' => date_i18n(get_option('date_format')),
            '{total_amount}' => wc_price(375),
            '{invoice_number}' => 'INV-2026-001',
            '{site_name}' => get_option('blogname'),
            '{site_url}' => home_url(),
            '{current_year}' => date('Y'),
        ];
        
        $subject = str_replace(array_keys($sample_data), array_values($sample_data), $subject);
        $body = str_replace(array_keys($sample_data), array_values($sample_data), $body);
        
        $from_name = get_option('crs_email_from_name', get_option('blogname'));
        $from_email = get_option('crs_email_from_email', get_option('admin_email'));
        $reply_to = get_option('crs_email_reply_to', get_option('admin_email'));
        
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            "From: $from_name <$from_email>",
            "Reply-To: $reply_to"
        ];
        
        return wp_mail($to_email, $subject, $body, $headers);
    }
}