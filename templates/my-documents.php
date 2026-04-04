<?php
/**
 * Shortcode: [crs_my_documents]
 * Displays all documents from user's booked congresses (Unique per congress)
 */
add_shortcode('crs_my_documents', 'crs_my_documents_shortcode');

function crs_my_documents_shortcode($atts) {
    ob_start();
    
    if (!is_user_logged_in()) {
        echo '<div class="crs-docs-error">Please login to view your documents.</div>';
        return ob_get_clean();
    }
    
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    
    // Pagination settings
    $atts = shortcode_atts(array(
        'posts_per_page' => 100,
        'paged' => get_query_var('paged') ? get_query_var('paged') : 1
    ), $atts);
    
    global $wpdb;
    $bookings_table = $wpdb->prefix . 'cr_bookings';
    $documents_table = $wpdb->prefix . 'cr_documents';
    
    // Get all confirmed bookings for this user
    $bookings = $wpdb->get_results($wpdb->prepare(
        "SELECT 
            b.*,
            p.post_title AS congress_title,
            p.post_content AS congress_description
         FROM $bookings_table b
         INNER JOIN {$wpdb->posts} p ON b.congress_id = p.ID
         WHERE b.user_id = %d AND b.booking_status = 'confirmed'
         ORDER BY b.created_at DESC",
        $user_id
    ));
    
    // ========== FIX: Group by unique congress to avoid duplicate documents ==========
    $unique_congresses = array();
    
    if (!empty($bookings)) {
        foreach ($bookings as $booking) {
            $congress_id = $booking->congress_id;
            
            // Only process each congress once
            if (!isset($unique_congresses[$congress_id])) {
                $unique_congresses[$congress_id] = array(
                    'congress_id' => $congress_id,
                    'congress_title' => $booking->congress_title,
                    'congress_description' => $booking->congress_description,
                    'bookings' => array()
                );
            }
            // Store all bookings for reference
            $unique_congresses[$congress_id]['bookings'][] = $booking;
        }
    }
    
    // Collect all documents (unique per congress)
    $all_documents = array();
    
    foreach ($unique_congresses as $congress_id => $congress_data) {
        $congress_title = $congress_data['congress_title'];
        $has_proof_document = false;
        
        // Check all bookings for this congress to find proof file (only add once)
        foreach ($congress_data['bookings'] as $booking) {
            $additional = json_decode($booking->additional_options, true);
            
            // ===== ADD PROOF FILE DOCUMENT (only once per congress) =====
            if (!$has_proof_document) {
                $proof_file_id = isset($additional['proof_file_id']) ? $additional['proof_file_id'] : 0;
                $proof_file_url = isset($additional['proof_file_url']) ? $additional['proof_file_url'] : '';
                $proof_file_name = isset($additional['proof_file_name']) ? $additional['proof_file_name'] : '';
                
                if (!empty($proof_file_url) || !empty($proof_file_id)) {
                    // Get URL if only ID exists
                    if (empty($proof_file_url) && !empty($proof_file_id)) {
                        $proof_file_url = wp_get_attachment_url($proof_file_id);
                    }
                    
                    if (!empty($proof_file_url)) {
                        $all_documents[] = array(
                            'congress_id' => $congress_id,
                            'congress_title' => $congress_title,
                            'document_name' => !empty($proof_file_name) ? $proof_file_name : 'Proof Document',
                            'document_type' => 'Proof',
                            'document_url' => $proof_file_url,
                            'document_icon' => crs_get_document_icon($proof_file_url),
                            'is_proof' => true
                        );
                        $has_proof_document = true;
                    }
                }
            }
        }
        
        // ===== ADD CONGRESS DOCUMENTS FROM POST META (only once per congress) =====
        $congress_documents = get_post_meta($congress_id, 'congress_documents', true);
        
        if (!empty($congress_documents) && is_array($congress_documents)) {
            foreach ($congress_documents as $document) {
                if (!empty($document['document_name']) && !empty($document['type'])) {
                    // Get document URL based on type
                    $document_url = '';
                    if ($document['type'] === 'Download' && !empty($document['download_file'])) {
                        $document_url = wp_get_attachment_url($document['download_file']);
                    } elseif ($document['type'] === 'External_link' && !empty($document['url_or_file_path'])) {
                        $document_url = $document['url_or_file_path'];
                    } elseif ($document['type'] === 'Upload_file' && !empty($document['url_or_file_path'])) {
                        $document_url = $document['url_or_file_path'];
                    }
                    
                    if (!empty($document_url)) {
                        $all_documents[] = array(
                            'congress_id' => $congress_id,
                            'congress_title' => $congress_title,
                            'document_name' => $document['document_name'],
                            'document_type' => $document['type'],
                            'document_url' => $document_url,
                            'document_icon' => crs_get_document_icon($document_url),
                            'is_proof' => false
                        );
                    }
                }
            }
        }
        
        // ===== GET DOCUMENTS FROM cr_documents TABLE (only once per congress) =====
        $first_booking = $congress_data['bookings'][0];
        $saved_docs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $documents_table WHERE booking_id = %d ORDER BY created_at DESC",
            $first_booking->id
        ));
        
        if (!empty($saved_docs)) {
            foreach ($saved_docs as $doc) {
                // Check if document already added (avoid duplicates)
                $already_added = false;
                foreach ($all_documents as $existing_doc) {
                    if ($existing_doc['document_url'] === $doc->document_url) {
                        $already_added = true;
                        break;
                    }
                }
                
                if (!$already_added) {
                    $all_documents[] = array(
                        'congress_id' => $congress_id,
                        'congress_title' => $congress_title,
                        'document_name' => ucfirst($doc->document_type) . ' Document',
                        'document_type' => ucfirst($doc->document_type),
                        'document_url' => $doc->document_url,
                        'document_icon' => crs_get_document_icon($doc->document_url),
                        'is_proof' => ($doc->document_type === 'proof')
                    );
                }
            }
        }
    }
    
    // Pagination
    $total_documents = count($all_documents);
    $total_pages = ceil($total_documents / $atts['posts_per_page']);
    $current_page = isset($_POST['crs_page']) ? intval($_POST['crs_page']) : $atts['paged'];
    $offset = ($current_page - 1) * $atts['posts_per_page'];
    $paged_documents = array_slice($all_documents, $offset, $atts['posts_per_page']);
    
    ?>
    
    <div class="crs-docs-container" id="crs-docs-container">
        <!-- Header with User Name -->
        <div class="crs-docs-header">
            <h1 class="crs-docs-title">My Documents</h1>
            <div class="crs-user-badge">
                <span class="crs-user-name"><?php echo esc_html($current_user->display_name); ?></span>
            </div>
        </div>
        
        <!-- Documents List Container -->
        <div id="crs-documents-list">
            <?php if (!empty($paged_documents)): ?>
                <?php 
                $current_congress = '';
                foreach ($paged_documents as $index => $document): 
                    
                    if ($current_congress !== $document['congress_id']):
                        $current_congress = $document['congress_id'];
                ?>
                    <!-- Congress Title -->
                    <div class="crs-congress-section">
                        <h2 class="crs-congress-heading"><?php echo esc_html($document['congress_title']); ?></h2>
                    </div>
                <?php endif; ?>
                
                <!-- Document Item -->
                <div class="crs-doc-row <?php echo $document['is_proof'] ? 'crs-proof-doc' : ''; ?>">
                    <div class="crs-doc-icon">
                        <?php echo $document['document_icon']; ?>
                    </div>
                    <div class="crs-doc-name">
                        <?php echo esc_html($document['document_name']); ?>
                        <?php if ($document['is_proof']): ?>
                            <span class="crs-doc-badge">Proof</span>
                        <?php endif; ?>
                    </div>
                    <div class="crs-doc-action">
                        <?php if (!empty($document['document_url'])): ?>
                            <a href="<?php echo esc_url($document['document_url']); ?>" 
                               class="crs-doc-link" 
                               target="_blank" 
                               rel="noopener noreferrer"
                               <?php echo $document['is_proof'] ? 'download' : ''; ?>>
                                <?php echo $document['is_proof'] ? 'Download' : 'View'; ?>
                            </a>
                        <?php else: ?>
                            <span class="crs-doc-link disabled">Unavailable</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php endforeach; ?>
                
                <!-- AJAX Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="crs-pagination" id="crs-pagination">
                    <button class="crs-page-btn <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>" 
                            data-page="<?php echo ($current_page - 1); ?>" 
                            <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>>
                        ← Previous
                    </button>
                    
                    <span class="crs-page-info">
                        Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>
                    </span>
                    
                    <button class="crs-page-btn <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>" 
                            data-page="<?php echo ($current_page + 1); ?>" 
                            <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>>
                        Next →
                    </button>
                </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="crs-docs-empty">
                    <p>No documents available for your registrations.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Loading Spinner -->
    <div id="crs-docs-loading" style="display: none; text-align: center; padding: 40px;">
        <div class="crs-spinner"></div>
        <p>Loading documents...</p>
    </div>
    
    <style>
    /* ========================================
       PROFESSIONAL MY DOCUMENTS DESIGN
       ======================================== */

    /* Main Container */
    .crs-docs-container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 24px;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
        color: #1e293b;
    }

    /* Header Section */
    .crs-docs-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 40px;
        padding-bottom: 24px;
        border-bottom: 2px solid #eef2ff;
    }

    .crs-docs-title {
        font-size: 32px;
        font-weight: 700;
        background: linear-gradient(135deg, #1e293b 0%, #2271b1 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin: 0;
        letter-spacing: -0.5px;
    }

    .crs-user-badge {
        display: flex;
        align-items: center;
        gap: 12px;
        background: #f8fafc;
        padding: 8px 20px 8px 12px;
        border-radius: 60px;
        border: 1px solid #e2e8f0;
        transition: all 0.2s;
    }

    .crs-user-badge:hover {
        border-color: #2271b1;
        box-shadow: 0 2px 8px rgba(34,113,177,0.1);
    }

    .crs-user-name {
        font-size: 15px;
        font-weight: 600;
        color: #1e293b;
        text-transform: uppercase;
    }

    /* Congress Section */
    .crs-congress-section {
        margin: 32px 0 20px 0;
        padding: 0 0 12px 0;
        border-bottom: 3px solid #2271b1;
        display: inline-block;
    }

    .crs-congress-heading {
        font-size: 20px;
        font-weight: 600;
        color: #0f172a;
        margin: 0;
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }

    .crs-congress-heading::before {
        content: '📁';
        font-size: 22px;
    }

    /* Documents Card */
    .crs-doc-row {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 18px 20px;
        margin-bottom: 12px;
        background: #ffffff;
        border: 1px solid #eef2ff;
        border-radius: 16px;
        transition: all 0.25s ease;
        position: relative;
        overflow: hidden;
    }

    .crs-doc-row::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: linear-gradient(135deg, #2271b1 0%, #135e96 100%);
        opacity: 0;
        transition: opacity 0.25s ease;
    }

    .crs-doc-row:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 24px -12px rgba(0, 0, 0, 0.12);
        border-color: #cbd5e1;
    }

    .crs-doc-row:hover::before {
        opacity: 1;
    }

    /* Proof Document Highlight */
    .crs-proof-doc {
        background: linear-gradient(135deg, #fef9e8 0%, #fff8f0 100%);
        border-color: #ffe4b5;
    }

    .crs-proof-doc::before {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        opacity: 1;
        width: 4px;
    }

    /* Document Icon */
    .crs-doc-icon {
        width: 52px;
        height: 52px;
        background: #f1f5f9;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        flex-shrink: 0;
        transition: all 0.2s;
    }

    .crs-proof-doc .crs-doc-icon {
        background: #fef3c7;
    }

    .crs-doc-row:hover .crs-doc-icon {
        transform: scale(1.05);
    }

    /* Document Name */
    .crs-doc-name {
        flex: 1;
        font-size: 15px;
        font-weight: 500;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        text-transform: capitalize;
    }

    /* Document Badge */
    .crs-doc-badge {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
        padding: 4px 12px;
        border-radius: 30px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 6px rgba(245,158,11,0.2);
    }

    /* Action Button */
    .crs-doc-action {
        flex-shrink: 0;
    }

    .crs-doc-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 24px;
        background: linear-gradient(135deg, #2271b1 0%, #135e96 100%);
        color: white;
        text-decoration: none;
        border-radius: 40px;
        font-size: 13px;
        font-weight: 600;
        transition: all 0.2s;
        border: none;
        cursor: pointer;
        letter-spacing: 0.3px;
    }

    .crs-doc-link:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 14px rgba(34,113,177,0.3);
        background: linear-gradient(135deg, #135e96 0%, #0f4a75 100%);
    }

    .crs-doc-link:active {
        transform: translateY(0);
    }

    /* Download button style for proof docs */
    .crs-proof-doc .crs-doc-link {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }

    .crs-proof-doc .crs-doc-link:hover {
        background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
        box-shadow: 0 6px 14px rgba(245,158,11,0.3);
    }

    /* Disabled link */
    .crs-doc-link.disabled {
        background: #e2e8f0;
        color: #94a3b8;
        cursor: not-allowed;
        pointer-events: none;
        box-shadow: none;
    }

    /* Pagination */
    .crs-pagination {
        margin-top: 48px;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 16px;
        padding: 20px 0;
        border-top: 1px solid #eef2ff;
    }

    .crs-page-btn {
        padding: 10px 24px;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 40px;
        color: #475569;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .crs-page-btn:hover:not(:disabled) {
        background: #2271b1;
        border-color: #2271b1;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(34,113,177,0.2);
    }

    .crs-page-btn.disabled,
    .crs-page-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
        background: #f1f5f9;
    }

    .crs-page-info {
        font-size: 14px;
        color: #64748b;
        font-weight: 500;
        background: #f8fafc;
        padding: 8px 20px;
        border-radius: 40px;
    }

    /* Empty State */
    .crs-docs-empty {
        text-align: center;
        padding: 80px 40px;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-radius: 24px;
        border: 1px solid #eef2ff;
    }

    .crs-docs-empty p {
        color: #64748b;
        font-size: 16px;
        margin: 0;
    }

    .crs-docs-empty::before {
        content: '📭';
        font-size: 64px;
        display: block;
        margin-bottom: 20px;
        opacity: 0.6;
    }

    /* Loading Spinner */
    #crs-docs-loading {
        text-align: center;
        padding: 60px;
    }

    .crs-spinner {
        display: inline-block;
        width: 48px;
        height: 48px;
        border: 3px solid #eef2ff;
        border-top-color: #2271b1;
        border-radius: 50%;
        animation: crs-spin 0.8s linear infinite;
        margin-bottom: 16px;
    }

    @keyframes crs-spin {
        to { transform: rotate(360deg); }
    }

    #crs-docs-loading p {
        color: #64748b;
        font-size: 14px;
        margin: 0;
    }

    /* Error State */
    .crs-docs-error {
        text-align: center;
        padding: 60px 40px;
        background: #fff5f5;
        border-radius: 24px;
        border: 1px solid #fed7d7;
        color: #c53030;
        font-size: 16px;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .crs-docs-container {
            padding: 0 16px;
            margin: 24px auto;
        }
        
        .crs-docs-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 32px;
        }
        
        .crs-docs-title {
            font-size: 26px;
        }
        
        .crs-doc-row {
            flex-wrap: wrap;
            padding: 16px;
            gap: 12px;
        }
        
        .crs-doc-icon {
            width: 44px;
            height: 44px;
            font-size: 22px;
        }
        
        .crs-doc-name {
            width: calc(100% - 56px);
            font-size: 14px;
        }
        
        .crs-doc-action {
            width: 100%;
            margin-left: 56px;
        }
        
        .crs-doc-link {
            width: 100%;
            justify-content: center;
            padding: 10px 20px;
        }
        
        .crs-pagination {
            flex-direction: column;
            gap: 12px;
        }
        
        .crs-page-btn {
            width: 100%;
            justify-content: center;
        }
        
        .crs-congress-heading {
            font-size: 18px;
        }
    }

    @media (max-width: 480px) {
        .crs-docs-container {
            padding: 0 12px;
        }
        
        .crs-docs-title {
            font-size: 22px;
        }
        
        .crs-user-badge {
            width: 100%;
            justify-content: center;
        }
        
        .crs-doc-row {
            flex-direction: column;
            align-items: flex-start;
            text-align: left;
        }
        
        .crs-doc-icon {
            width: 48px;
            height: 48px;
        }
        
        .crs-doc-name {
            width: 100%;
            margin-left: 0;
        }
        
        .crs-doc-action {
            width: 100%;
            margin-left: 0;
        }
        
        .crs-congress-heading {
            font-size: 16px;
        }
        
        .crs-pagination {
            gap: 10px;
        }
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // AJAX Pagination
        $('.crs-page-btn').on('click', function(e) {
            e.preventDefault();
            
            var page = $(this).data('page');
            
            if (!page || $(this).hasClass('disabled') || $(this).prop('disabled')) {
                return;
            }
            
            // Show loading spinner
            $('#crs-documents-list').hide();
            $('#crs-docs-loading').show();
            
            // Load new page via AJAX
            $.ajax({
                url: crs_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'crs_load_documents_page',
                    page: page,
                    per_page: <?php echo $atts['posts_per_page']; ?>,
                    nonce: crs_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#crs-docs-loading').hide();
                        $('#crs-documents-list').html(response.data.html).fadeIn();
                        
                        // Update URL (optional)
                        var url = new URL(window.location.href);
                        url.searchParams.set('paged', page);
                        window.history.pushState({}, '', url);
                    } else {
                        $('#crs-docs-loading').hide();
                        $('#crs-documents-list').show();
                        alert('Failed to load documents');
                    }
                },
                error: function() {
                    $('#crs-docs-loading').hide();
                    $('#crs-documents-list').show();
                    alert('Failed to load documents');
                }
            });
        });
    });
    </script>
    
    <?php
    return ob_get_clean();
}

/**
 * Helper function to get document icon based on file type
 */
function crs_get_document_icon($url) {
    $extension = pathinfo($url, PATHINFO_EXTENSION);
    $extension = strtolower($extension);
    
    $icons = array(
        'pdf' => '📄',
        'doc' => '📝',
        'docx' => '📝',
        'xls' => '📊',
        'xlsx' => '📊',
        'ppt' => '📽️',
        'pptx' => '📽️',
        'jpg' => '🖼️',
        'jpeg' => '🖼️',
        'png' => '🖼️',
        'gif' => '🖼️',
        'txt' => '📃',
        'zip' => '🗜️',
        'rar' => '🗜️',
        'mp3' => '🎵',
        'mp4' => '🎬'
    );
    
    return isset($icons[$extension]) ? $icons[$extension] : '📎';
}