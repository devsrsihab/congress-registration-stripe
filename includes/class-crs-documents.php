<?php
if (!defined('ABSPATH')) exit;

class CRS_Documents {
    
    public static function uploadFromBase64($base64_data, $file_name, $user_id) {
        $data = explode(',', $base64_data);
        $encoded_data = $data[1] ?? $base64_data;
        $decoded_data = base64_decode($encoded_data);
        
        if (!$decoded_data) return 0;
        
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . sanitize_file_name($file_name);
        file_put_contents($file_path, $decoded_data);
        
        $file_type = wp_check_filetype($file_name);
        $attachment = [
            'guid' => $upload_dir['url'] . '/' . sanitize_file_name($file_name),
            'post_mime_type' => $file_type['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', sanitize_file_name($file_name)),
            'post_content' => '',
            'post_status' => 'inherit',
            'post_author' => $user_id
        ];
        
        $attach_id = wp_insert_attachment($attachment, $file_path, 0);
        if ($attach_id) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
            wp_update_attachment_metadata($attach_id, $attach_data);
        }
        
        return $attach_id;
    }
    
    public static function generateBookingDocuments($booking_id) {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'cr_bookings';
        $documents_table = $wpdb->prefix . 'cr_documents';
        
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $bookings_table WHERE id = %d",
            $booking_id
        ));
        
        if (!$booking) return;
        
        $additional_options = json_decode($booking->additional_options, true);
        
        $confirmation_url = self::generateConfirmationDocument($booking);
        if ($confirmation_url) {
            $wpdb->insert($documents_table, [
                'booking_id' => $booking_id,
                'user_id' => $booking->user_id,
                'congress_id' => $booking->congress_id,
                'document_type' => 'confirmation',
                'document_url' => $confirmation_url,
                'created_at' => current_time('mysql')
            ]);
        }
        
        if (!empty($additional_options['invoice_request'])) {
            $invoice_url = self::generateInvoiceDocument($booking);
            if ($invoice_url) {
                $wpdb->insert($documents_table, [
                    'booking_id' => $booking_id,
                    'user_id' => $booking->user_id,
                    'congress_id' => $booking->congress_id,
                    'document_type' => 'invoice',
                    'document_url' => $invoice_url,
                    'created_at' => current_time('mysql')
                ]);
            }
        }
    }
    
    private static function generateConfirmationDocument($booking) {
        $upload_dir = wp_upload_dir();
        $doc_dir = $upload_dir['basedir'] . '/crs-documents/';
        $doc_url_base = $upload_dir['baseurl'] . '/crs-documents/';
        
        if (!file_exists($doc_dir)) wp_mkdir_p($doc_dir);
        
        $filename = 'confirmation-' . $booking->booking_number . '.html';
        $filepath = $doc_dir . $filename;
        
        $congress = get_post($booking->congress_id);
        $additional_options = json_decode($booking->additional_options, true);
        
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Registration Confirmation - ' . $booking->booking_number . '</title>';
        $html .= '<style>body{font-family:Arial;margin:40px;}h1{color:#333;}.header{text-align:center;margin-bottom:30px;}.booking-info{background:#f9f9f9;padding:20px;border-radius:8px;}.row{margin:10px 0;}.label{font-weight:bold;color:#555;display:inline-block;width:150px;}</style>';
        $html .= '</head><body><div class="header"><h1>Registration Confirmation</h1><p>Booking #: ' . $booking->booking_number . '</p><p>Date: ' . date_i18n(get_option('date_format'), strtotime($booking->created_at)) . '</p></div>';
        $html .= '<div class="booking-info"><h2>Personal Information</h2>';
        $html .= '<div class="row"><span class="label">Name:</span> ' . esc_html($additional_options['personal_data']['first_name'] . ' ' . $additional_options['personal_data']['last_name']) . '</div>';
        $html .= '<div class="row"><span class="label">Email:</span> ' . esc_html($additional_options['personal_data']['email']) . '</div>';
        $html .= '<div class="row"><span class="label">Phone:</span> ' . esc_html($additional_options['personal_data']['phone']) . '</div>';
        $html .= '<h2>Congress Details</h2>';
        $html .= '<div class="row"><span class="label">Congress:</span> ' . esc_html(get_the_title($booking->congress_id)) . '</div>';
        $html .= '<div class="row"><span class="label">Total Amount:</span> ' . wc_price($booking->total_amount) . '</div>';
        $html .= '<div class="row"><span class="label">Payment Status:</span> Completed</div></div>';
        $html .= '<p>Thank you for your registration!</p></body></html>';
        
        file_put_contents($filepath, $html);
        return $doc_url_base . $filename;
    }
    
    private static function generateInvoiceDocument($booking) {
        $upload_dir = wp_upload_dir();
        $doc_dir = $upload_dir['basedir'] . '/crs-documents/';
        $doc_url_base = $upload_dir['baseurl'] . '/crs-documents/';
        
        if (!file_exists($doc_dir)) wp_mkdir_p($doc_dir);
        
        $filename = 'invoice-' . $booking->booking_number . '.html';
        $filepath = $doc_dir . $filename;
        
        $additional_options = json_decode($booking->additional_options, true);
        $company_details = $additional_options['company_details'] ?? [];
        
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Invoice - ' . $booking->booking_number . '</title>';
        $html .= '<style>body{font-family:Arial;margin:40px;}.invoice-header{text-align:center;margin-bottom:30px;border-bottom:2px solid #333;padding-bottom:20px;}.company-details{background:#f9f9f9;padding:20px;margin-bottom:20px;border-left:4px solid #2271b1;}.row{margin:10px 0;}.label{font-weight:bold;color:#555;display:inline-block;width:150px;}.total{font-size:18px;font-weight:bold;margin-top:20px;padding-top:10px;border-top:2px solid #333;}</style>';
        $html .= '</head><body><div class="invoice-header"><h1>INVOICE</h1><p>Booking #: ' . $booking->booking_number . '</p><p>Date: ' . date_i18n(get_option('date_format'), strtotime($booking->created_at)) . '</p></div>';
        
        if (!empty($company_details['company_name'])) {
            $html .= '<div class="company-details"><h3>Billed To:</h3>';
            $html .= '<div class="row"><span class="label">Company:</span> ' . esc_html($company_details['company_name']) . '</div>';
            $html .= '<div class="row"><span class="label">CIF:</span> ' . esc_html($company_details['cif']) . '</div>';
            $html .= '<div class="row"><span class="label">Address:</span> ' . esc_html($company_details['tax_address']) . '</div></div>';
        }
        
        $html .= '<div class="row"><span class="label">Name:</span> ' . esc_html($additional_options['personal_data']['first_name'] . ' ' . $additional_options['personal_data']['last_name']) . '</div>';
        $html .= '<div class="row"><span class="label">Email:</span> ' . esc_html($additional_options['personal_data']['email']) . '</div>';
        $html .= '<div class="row"><span class="label">Congress:</span> ' . esc_html(get_the_title($booking->congress_id)) . '</div>';
        $html .= '<div class="row total"><span class="label">Total Amount:</span> ' . wc_price($booking->total_amount) . '</div>';
        $html .= '<p style="margin-top:50px;text-align:center;color:#666;">Thank you for your registration!</p></body></html>';
        
        file_put_contents($filepath, $html);
        return $doc_url_base . $filename;
    }
    
    public static function renderDocumentsList($user_id, $page, $per_page) {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'cr_bookings';
        $documents_table = $wpdb->prefix . 'cr_documents';
        
        $bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT b.*, p.post_title AS congress_title
             FROM $bookings_table b
             INNER JOIN {$wpdb->posts} p ON b.congress_id = p.ID
             WHERE b.user_id = %d AND b.booking_status = 'completed'
             ORDER BY b.created_at DESC",
            $user_id
        ));
        
        $all_documents = [];
        foreach ($bookings as $booking) {
            $additional = json_decode($booking->additional_options, true);
            if (!empty($additional['proof_file_url'])) {
                $all_documents[] = [
                    'congress_id' => $booking->congress_id,
                    'congress_title' => $booking->congress_title,
                    'document_name' => $additional['proof_file_name'] ?: 'Proof Document',
                    'document_type' => 'Proof',
                    'document_url' => $additional['proof_file_url'],
                    'document_icon' => self::getDocumentIcon($additional['proof_file_url']),
                    'is_proof' => true
                ];
            }
            
            $congress_documents = get_post_meta($booking->congress_id, 'congress_documents', true);
            if (!empty($congress_documents) && is_array($congress_documents)) {
                foreach ($congress_documents as $document) {
                    if (!empty($document['document_name']) && !empty($document['type'])) {
                        $document_url = '';
                        if ($document['type'] === 'Download' && !empty($document['download_file'])) {
                            $document_url = wp_get_attachment_url($document['download_file']);
                        } elseif (in_array($document['type'], ['External_link', 'Upload_file']) && !empty($document['url_or_file_path'])) {
                            $document_url = $document['url_or_file_path'];
                        }
                        
                        if (!empty($document_url)) {
                            $all_documents[] = [
                                'congress_id' => $booking->congress_id,
                                'congress_title' => $booking->congress_title,
                                'document_name' => $document['document_name'],
                                'document_type' => $document['type'],
                                'document_url' => $document_url,
                                'document_icon' => self::getDocumentIcon($document_url),
                                'is_proof' => false
                            ];
                        }
                    }
                }
            }
        }
        
        $offset = ($page - 1) * $per_page;
        $paged_documents = array_slice($all_documents, $offset, $per_page);
        
        $current_congress = '';
        foreach ($paged_documents as $document): 
            if ($current_congress !== $document['congress_id']):
                $current_congress = $document['congress_id'];
                echo '<div class="crs-congress-section"><h2 class="crs-congress-heading">' . esc_html($document['congress_title']) . '</h2></div>';
            endif;
            echo '<div class="crs-doc-row ' . ($document['is_proof'] ? 'crs-proof-doc' : '') . '">';
            echo '<div class="crs-doc-icon">' . $document['document_icon'] . '</div>';
            echo '<div class="crs-doc-name">' . esc_html($document['document_name']);
            if ($document['is_proof']) echo '<span class="crs-doc-badge">Proof</span>';
            echo '</div>';
            echo '<div class="crs-doc-action"><a href="' . esc_url($document['document_url']) . '" class="crs-doc-link" target="_blank">' . ($document['is_proof'] ? 'Download' : 'View') . '</a></div>';
            echo '</div>';
        endforeach;
    }
    
    public static function getDocumentIcon($url) {
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        $icons = [
            'pdf' => '📄', 'doc' => '📝', 'docx' => '📝', 'xls' => '📊', 'xlsx' => '📊',
            'ppt' => '📽️', 'pptx' => '📽️', 'jpg' => '🖼️', 'jpeg' => '🖼️', 'png' => '🖼️',
            'gif' => '🖼️', 'txt' => '📃', 'zip' => '🗜️', 'rar' => '🗜️', 'mp3' => '🎵', 'mp4' => '🎬'
        ];
        return $icons[$extension] ?? '📎';
    }
}