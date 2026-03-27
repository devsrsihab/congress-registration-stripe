<?php
/**
 * Shortcode: [crs_my_documents]
 * Displays all documents from user's booked congresses
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
    
    // Get all completed bookings for this user
    $bookings = $wpdb->get_results($wpdb->prepare(
        "SELECT 
            b.*,
            p.post_title AS congress_title,
            p.post_content AS congress_description
         FROM $bookings_table b
         INNER JOIN {$wpdb->posts} p ON b.congress_id = p.ID
         WHERE b.user_id = %d AND b.booking_status = 'completed'
         ORDER BY b.created_at DESC",
        $user_id
    ));
    
    // Collect all documents
    $all_documents = array();
    
    if (!empty($bookings)) {
        foreach ($bookings as $booking) {
            $congress_id = $booking->congress_id;
            $congress_title = $booking->congress_title;
            
            // Get booking additional options to find proof file
            $additional = json_decode($booking->additional_options, true);
            
            // ===== ADD PROOF FILE DOCUMENT =====
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
                }
            }
            
            // Get congress documents from post meta
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
            
            // Get documents from cr_documents table
            $saved_docs = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $documents_table WHERE booking_id = %d ORDER BY created_at DESC",
                $booking->id
            ));
            
            if (!empty($saved_docs)) {
                foreach ($saved_docs as $doc) {
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
                <span class="crs-user-avatar">👤</span>
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
    /* Existing styles plus new additions */
    .crs-doc-row {
        display: flex;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #eaeef2;
        transition: background-color 0.2s;
    }
    
    .crs-doc-row:hover {
        background-color: #f9fbfd;
    }
    
    .crs-proof-doc {
        background-color: #f0f9ff;
        border-left: 3px solid #2271b1;
    }
    
    .crs-doc-icon {
        width: 40px;
        font-size: 20px;
        text-align: center;
        flex-shrink: 0;
    }
    
    .crs-doc-name {
        flex: 1;
        font-size: 15px;
        color: #1e293b;
        padding: 0 10px;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    .crs-doc-badge {
        background: #2271b1;
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    
    .crs-doc-action {
        width: 100px;
        flex-shrink: 0;
        text-align: right;
    }
    
    .crs-doc-link {
        display: inline-block;
        padding: 6px 16px;
        background: #f0f7ff;
        color: #2271b1;
        text-decoration: none;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.2s;
        border: 1px solid #d4e4ff;
    }
    
    .crs-doc-link:hover {
        background: #2271b1;
        color: white;
        border-color: #2271b1;
    }
    
    .crs-doc-link.disabled {
        background: #f1f5f9;
        color: #94a3b8;
        border-color: #e2e8f0;
        cursor: not-allowed;
        pointer-events: none;
    }
    
    /* AJAX Pagination Styles */
    .crs-pagination {
        margin-top: 40px;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 20px;
    }
    
    .crs-page-btn {
        padding: 10px 20px;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        color: #1e293b;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .crs-page-btn:hover:not(:disabled) {
        background: #f1f5f9;
        border-color: #2271b1;
        color: #2271b1;
    }
    
    .crs-page-btn.disabled,
    .crs-page-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
    }
    
    .crs-page-info {
        font-size: 14px;
        color: #64748b;
    }
    
    /* Loading Spinner */
    .crs-spinner {
        display: inline-block;
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #2271b1;
        border-radius: 50%;
        animation: crs-spin 1s linear infinite;
        margin-bottom: 10px;
    }
    
    @keyframes crs-spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .crs-doc-row {
            flex-wrap: wrap;
            padding: 15px 0;
        }
        
        .crs-doc-icon {
            width: auto;
            margin-right: 15px;
        }
        
        .crs-doc-name {
            width: calc(100% - 120px);
            padding: 0;
        }
        
        .crs-doc-action {
            width: 100%;
            text-align: left;
            margin-top: 10px;
            margin-left: 55px;
        }
        
        .crs-doc-link {
            width: 100%;
            text-align: center;
        }
        
        .crs-pagination {
            flex-direction: column;
            gap: 10px;
        }
        
        .crs-page-btn {
            width: 100%;
        }
    }
    
    @media (max-width: 480px) {
        .crs-doc-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
        
        .crs-doc-icon {
            margin-right: 0;
        }
        
        .crs-doc-name {
            width: 100%;
        }
        
        .crs-doc-action {
            width: 100%;
            margin-left: 0;
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