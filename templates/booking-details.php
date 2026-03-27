<?php
/**
 * Shortcode: [crs_booking_details]
 * Professional Booking Details Template
 */
add_shortcode('crs_booking_details', 'crs_booking_details_shortcode');

function crs_booking_details_shortcode($atts) {
    ob_start();
    
    // Get booking ID from URL
    $booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
    
    if (!$booking_id) {
        echo '<div class="crs-error-container">
                <div class="crs-error-icon">🔍</div>
                <h3>Booking ID Required</h3>
                <p>Please provide a valid booking ID.</p>
              </div>';
        return ob_get_clean();
    }
    
    // Show loading state
    ?>
    <div class="crs-loading-screen" id="crs-loading">
        <div class="crs-loading-spinner"></div>
        <p class="crs-loading-text">Loading your booking details...</p>
    </div>
    
    <div class="crs-booking-wrapper" id="crs-booking-container" style="display: none;">
    <?php
    
    global $wpdb;
    
    // Direct SQL query
    $result = $wpdb->get_row($wpdb->prepare("
        SELECT
          cr_bookings.*,
          posts.post_title AS congress_name,
          posts.post_content AS congress_description,
          hotel.post_title AS hotel_name,
          hotel.post_content AS hotel_description,
          postmeta.meta_value AS congress_image_id,
          hotel_meta.meta_value AS hotel_price_per_night,
          hotel_meta_addr.meta_value AS hotel_address
        FROM
          {$wpdb->prefix}cr_bookings AS cr_bookings
          INNER JOIN {$wpdb->posts} AS posts ON cr_bookings.congress_id = posts.ID
          LEFT JOIN {$wpdb->posts} AS hotel ON cr_bookings.selected_hotel_id = hotel.ID
          LEFT JOIN {$wpdb->postmeta} AS postmeta ON (posts.ID = postmeta.post_id AND postmeta.meta_key = 'image')
          LEFT JOIN {$wpdb->postmeta} AS hotel_meta ON (hotel.ID = hotel_meta.post_id AND hotel_meta.meta_key = 'price_per_night')
          LEFT JOIN {$wpdb->postmeta} AS hotel_meta_addr ON (hotel.ID = hotel_meta_addr.post_id AND hotel_meta_addr.meta_key = 'address')
        WHERE
          cr_bookings.id = %d
    ", $booking_id));
    
    if (!$result) {
        echo '<div class="crs-error-container">
                <div class="crs-error-icon">😕</div>
                <h3>Booking Not Found</h3>
                <p>The booking you\'re looking for doesn\'t exist or has been removed.</p>
                <a href="/my-bookings" class="crs-btn crs-btn-primary">View My Bookings</a>
              </div>';
        echo '</div>';
        return ob_get_clean();
    }
    
    // Decode JSON fields
    $meal_preferences = !empty($result->meal_preferences) ? json_decode($result->meal_preferences, true) : [];
    $workshop_ids = !empty($result->workshop_ids) ? json_decode($result->workshop_ids, true) : [];
    $additional_options = !empty($result->additional_options) ? json_decode($result->additional_options, true) : [];
    
    // Get meal details
    $meals_list = [];
    $meals_total = 0;
    if (!empty($meal_preferences) && $result->congress_id) {
        $congress_meals = get_post_meta($result->congress_id, 'congress_meals', true);
        if (is_array($congress_meals)) {
            foreach ($meal_preferences as $meal_index) {
                if (isset($congress_meals[$meal_index])) {
                    $meals_list[] = $congress_meals[$meal_index];
                    $meals_total += floatval($congress_meals[$meal_index]['meal_price']);
                }
            }
        }
    }
    
    // Get workshop details
    $workshops_list = [];
    if (!empty($workshop_ids) && $result->congress_id) {
        $congress_workshops = get_post_meta($result->congress_id, 'congress_workshop', true);
        if (is_array($congress_workshops)) {
            foreach ($workshop_ids as $workshop_index) {
                $search_index = $workshop_index;
                if (is_string($workshop_index) && strpos($workshop_index, 'item-') === 0) {
                    $search_index = $workshop_index;
                }
                if (isset($congress_workshops[$search_index])) {
                    $workshops_list[] = $congress_workshops[$search_index];
                }
            }
        }
    }
    
    // Calculate nights
    $nights = 0;
    if ($result->check_in_date && $result->check_out_date) {
        $datetime1 = new DateTime($result->check_in_date);
        $datetime2 = new DateTime($result->check_out_date);
        $interval = $datetime1->diff($datetime2);
        $nights = $interval->days;
    }
    
    $hotel_total = floatval($result->hotel_price_per_night) * $nights;
    
    // Hide loading and show content
    ?>
    <script>
        document.getElementById('crs-loading').style.display = 'none';
        document.getElementById('crs-booking-container').style.display = 'block';
    </script>

    <!-- Navigation Breadcrumb -->
    <!-- <div class="crs-nav-breadcrumb">
        <a href="/dashboard" class="crs-breadcrumb-link">Dashboard</a>
        <span class="crs-breadcrumb-sep">›</span>
        <a href="/my-bookings" class="crs-breadcrumb-link">My Bookings</a>
        <span class="crs-breadcrumb-sep">›</span>
        <span class="crs-breadcrumb-current">Booking Details</span>
    </div> -->
    
    <!-- Header Section -->
    <div class="crs-page-header">
        <div class="crs-header-content">
            <h1 class="crs-page-title">Booking Overview</h1>
            <p class="crs-page-subtitle">Detailed information about your registration</p>
        </div>
        <div class="crs-header-actions">
            <span class="crs-booking-id">#<?php echo esc_html($result->booking_number); ?></span>
        </div>
    </div>
    
    <!-- Status Cards -->
    <div class="crs-status-grid">
        <div class="crs-status-card crs-status-card-primary">
            <div class="crs-status-icon">📋</div>
            <div class="crs-status-content">
                <span class="crs-status-label">Booking Status</span>
                <span class="crs-status-value crs-status-<?php echo esc_attr($result->booking_status); ?>">
                    <?php echo esc_html(ucfirst($result->booking_status)); ?>
                </span>
            </div>
        </div>
        
        <div class="crs-status-card crs-status-card-secondary">
            <div class="crs-status-icon">💳</div>
            <div class="crs-status-content">
                <span class="crs-status-label">Payment Status</span>
                <span class="crs-status-value crs-payment-<?php echo esc_attr($result->payment_status); ?>">
                    <?php echo esc_html(ucfirst($result->payment_status)); ?>
                </span>
            </div>
        </div>
        
        <div class="crs-status-card crs-status-card-accent">
            <div class="crs-status-icon">💰</div>
            <div class="crs-status-content">
                <span class="crs-status-label">Total Amount</span>
                <span class="crs-status-value crs-amount-large">€<?php echo number_format($result->total_amount, 2); ?></span>
            </div>
        </div>
        
        <div class="crs-status-card crs-status-card-info">
            <div class="crs-status-icon">📅</div>
            <div class="crs-status-content">
                <span class="crs-status-label">Created On</span>
                <span class="crs-status-value"><?php echo date_i18n('M j, Y', strtotime($result->created_at)); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Main Content Grid -->
    <div class="crs-main-grid">
        <!-- Left Column -->
        <div class="crs-left-column">
            
            <!-- Personal Information Card -->
            <div class="crs-info-card">
                <div class="crs-card-header">
                    <span class="crs-card-icon">👤</span>
                    <h2 class="crs-card-title">Personal Information</h2>
                </div>
                
                <div class="crs-card-body">
                    <?php if (!empty($additional_options['personal_data'])): 
                        $personal = $additional_options['personal_data'];
                    ?>
                    <div class="crs-detail-grid">
                        <div class="crs-detail-item">
                            <span class="crs-detail-label">Full Name</span>
                            <span class="crs-detail-value"><?php echo esc_html($personal['first_name'] . ' ' . $personal['last_name']); ?></span>
                        </div>
                        
                        <div class="crs-detail-item">
                            <span class="crs-detail-label">ID/Passport</span>
                            <span class="crs-detail-value"><?php echo esc_html($personal['id_number']); ?></span>
                        </div>
                        
                        <div class="crs-detail-item">
                            <span class="crs-detail-label">Email Address</span>
                            <span class="crs-detail-value"><?php echo esc_html($personal['email']); ?></span>
                        </div>
                        
                        <div class="crs-detail-item">
                            <span class="crs-detail-label">Phone Number</span>
                            <span class="crs-detail-value"><?php echo esc_html($personal['phone']); ?></span>
                        </div>
                        
                        <div class="crs-detail-item crs-detail-full">
                            <span class="crs-detail-label">Address</span>
                            <span class="crs-detail-value"><?php 
                                echo esc_html(
                                    $personal['address'] . ', ' . 
                                    $personal['location'] . ', ' . 
                                    $personal['postal_code'] . ', ' . 
                                    $personal['country']
                                ); 
                            ?></span>
                        </div>
                        
                        <?php if (!empty($personal['work_center'])): ?>
                        <div class="crs-detail-item crs-detail-full">
                            <span class="crs-detail-label">Work Center</span>
                            <span class="crs-detail-value"><?php echo esc_html($personal['work_center']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($additional_options['third_person_name'])): ?>
                    <div class="crs-note-box">
                        <span class="crs-note-icon">👥</span>
                        <div class="crs-note-content">
                            <strong>Registered For:</strong> <?php echo esc_html($additional_options['third_person_name']); ?>
                            <span class="crs-note-badge">Third Person</span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Meals Card -->
            <?php if (!empty($meals_list)): ?>
            <div class="crs-info-card">
                <div class="crs-card-header">
                    <span class="crs-card-icon">🍽️</span>
                    <h2 class="crs-card-title">Dining Selection</h2>
                </div>
                
                <div class="crs-card-body">
                    <div class="crs-items-list">
                        <?php foreach ($meals_list as $meal): 
                            $meal_type = isset($meal['meal_type']) && $meal['meal_type'] == 'Gala_Dinner' ? 'Gala Dinner' : 'Meal';
                        ?>
                        <div class="crs-list-item">
                            <div class="crs-item-info">
                                <span class="crs-item-name"><?php echo esc_html($meal['meal_title']); ?></span>
                                <span class="crs-item-badge"><?php echo $meal_type; ?></span>
                            </div>
                            <span class="crs-item-price">€<?php echo number_format($meal['meal_price'], 2); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="crs-total-box">
                        <span class="crs-total-label">Total Meals</span>
                        <span class="crs-total-amount">€<?php echo number_format($meals_total, 2); ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Workshops Card -->
            <?php if (!empty($workshops_list)): ?>
            <div class="crs-info-card">
                <div class="crs-card-header">
                    <span class="crs-card-icon">🔧</span>
                    <h2 class="crs-card-title">Workshops</h2>
                </div>
                
                <div class="crs-card-body">
                    <div class="crs-workshop-grid">
                        <?php foreach ($workshops_list as $workshop): ?>
                        <div class="crs-workshop-item">
                            <div class="crs-workshop-icon">📌</div>
                            <div class="crs-workshop-details">
                                <strong><?php echo esc_html($workshop['workshop_title']); ?></strong>
                                <?php if (!empty($workshop['workshop_date'])): ?>
                                <span class="crs-workshop-meta">
                                    <?php 
                                    if (is_numeric($workshop['workshop_date'])) {
                                        echo date_i18n('F j, Y', $workshop['workshop_date']);
                                    } else {
                                        echo date_i18n('F j, Y', strtotime($workshop['workshop_date']));
                                    }
                                    ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Right Column -->
        <div class="crs-right-column">
            
            <!-- Congress Card -->
            <div class="crs-info-card crs-featured-card">
                <div class="crs-card-header">
                    <span class="crs-card-icon">🏛️</span>
                    <h2 class="crs-card-title">Congress Details</h2>
                </div>
                
                <div class="crs-card-body">
                    <?php if (!empty($result->congress_image_id)): 
                        $image_url = wp_get_attachment_url($result->congress_image_id);
                    ?>
                    <div class="crs-image-container">
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($result->congress_name); ?>" class="crs-featured-image">
                    </div>
                    <?php endif; ?>
                    
                    <h3 class="crs-congress-name"><?php echo esc_html($result->congress_name); ?></h3>
                    
                    <?php 
                    // Get registration type
                    $reg_type_name = '';
                    $reg_type_price = 0;
                    if (!empty($additional_options['registration_type_id'])) {
                        $type = $wpdb->get_row($wpdb->prepare(
                            "SELECT name, price FROM {$wpdb->prefix}registration_types WHERE id = %d",
                            $additional_options['registration_type_id']
                        ));
                        if ($type) {
                            $reg_type_name = $type->name;
                            $reg_type_price = $type->price;
                        }
                    }
                    ?>
                    
                    <div class="crs-pricing-box">
                        <div class="crs-price-row">
                            <span>Registration Type:</span>
                            <span class="crs-price-highlight"><?php echo esc_html($reg_type_name); ?> (€<?php echo number_format($reg_type_price, 0); ?>)</span>
                        </div>
                        
                        <?php if (!empty($additional_options['add_sidi'])): ?>
                        <div class="crs-price-row">
                            <span>+ SIDI 2026:</span>
                            <span>€150</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Accommodation Card -->
            <?php if (!empty($result->hotel_name)): ?>
            <div class="crs-info-card">
                <div class="crs-card-header">
                    <span class="crs-card-icon">🏨</span>
                    <h2 class="crs-card-title">Accommodation</h2>
                </div>
                
                <div class="crs-card-body">
                    <div class="crs-hotel-header">
                        <h3 class="crs-hotel-name"><?php echo esc_html($result->hotel_name); ?></h3>
                        <span class="crs-price-tag">€<?php echo esc_html($result->hotel_price_per_night); ?>/night</span>
                    </div>
                    
                    <div class="crs-date-range">
                        <div class="crs-date-box">
                            <span class="crs-date-label">Check-in</span>
                            <span class="crs-date-value"><?php echo date_i18n('M j, Y', strtotime($result->check_in_date)); ?></span>
                        </div>
                        <div class="crs-date-arrow">→</div>
                        <div class="crs-date-box">
                            <span class="crs-date-label">Check-out</span>
                            <span class="crs-date-value"><?php echo date_i18n('M j, Y', strtotime($result->check_out_date)); ?></span>
                        </div>
                    </div>
                    
                    <div class="crs-stay-summary">
                        <span class="crs-stay-nights"><?php echo $nights; ?> <?php echo $nights == 1 ? 'night' : 'nights'; ?></span>
                        <span class="crs-stay-total">€<?php echo number_format($hotel_total, 2); ?></span>
                    </div>
                    
                    <?php if (!empty($result->hotel_address)): ?>
                    <div class="crs-address-box">
                        <span class="crs-address-icon">📍</span>
                        <span class="crs-address-text"><?php echo esc_html($result->hotel_address); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Dietary Card -->
            <?php if (!empty($additional_options['dietary_info'])): 
                $dietary = $additional_options['dietary_info'];
            ?>
            <div class="crs-info-card">
                <div class="crs-card-header">
                    <span class="crs-card-icon">🥗</span>
                    <h2 class="crs-card-title">Dietary Preferences</h2>
                </div>
                
                <div class="crs-card-body">
                    <?php 
                    // Diet information
                    $diet_values = [];
                    if (!empty($dietary['diet'])) {
                        if (is_array($dietary['diet'])) {
                            $diet_values = $dietary['diet'];
                        } else {
                            $diet_values = [$dietary['diet']];
                        }
                    }
                    
                    if (!empty($diet_values) && !(count($diet_values) == 1 && in_array('no', $diet_values))):
                        $display_diets = [];
                        foreach ($diet_values as $diet) {
                            if ($diet === 'no') continue;
                            $display_diets[] = ucfirst($diet);
                        }
                    ?>
                    <div class="crs-diet-tags">
                        <?php foreach ($display_diets as $diet): ?>
                        <span class="crs-diet-tag"><?php echo $diet; ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php 
                    $allergy_value = isset($dietary['allergy']) ? $dietary['allergy'] : '';
                    $allergy_details = isset($dietary['allergy_details']) ? $dietary['allergy_details'] : '';
                    
                    if ($allergy_value === 'yes'): 
                    ?>
                    <div class="crs-allergy-warning">
                        <span class="crs-allergy-icon">⚠️</span>
                        <div class="crs-allergy-text">
                            <strong>Allergy Alert:</strong> <?php echo esc_html($allergy_details); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Additional Info Card -->
            <?php if (!empty($additional_options['other_details']) || !empty($additional_options['proof_file_id'])): 
                $other = $additional_options['other_details'];
            ?>
            <div class="crs-info-card">
                <div class="crs-card-header">
                    <span class="crs-card-icon">📌</span>
                    <h2 class="crs-card-title">Additional Information</h2>
                </div>
                
                <div class="crs-card-body">
                    <?php if (!empty($other['free_communication'])): ?>
                    <div class="crs-info-badge">
                        <span class="crs-badge-label">Free Communication:</span>
                        <span class="crs-badge-value"><?php echo ucfirst($other['free_communication']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($other['observations'])): ?>
                    <div class="crs-observations">
                        <p><?php echo nl2br(esc_html($other['observations'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($additional_options['proof_file_id'])): 
                        $proof_url = wp_get_attachment_url($additional_options['proof_file_id']);
                        $proof_type = get_post_mime_type($additional_options['proof_file_id']);
                        $is_image = strpos($proof_type, 'image') !== false;
                    ?>
                    <div class="crs-proof-section">
                        <span class="crs-proof-label">Proof Document:</span>
                        <?php if ($is_image): ?>
                        <a href="<?php echo esc_url($proof_url); ?>" target="_blank" class="crs-proof-button">
                            <span class="crs-proof-icon">🖼️</span>
                            <span>View Document</span>
                        </a>
                        <?php else: ?>
                        <a href="<?php echo esc_url($proof_url); ?>" target="_blank" class="crs-proof-button">
                            <span class="crs-proof-icon">📄</span>
                            <span>Download PDF</span>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    </div> <!-- Close crs-booking-wrapper -->

    <style>
    /* Modern CSS Reset */
    .crs-booking-wrapper * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    /* Loading Screen */
    .crs-loading-screen {
        min-height: 400px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #f5f7fa 0%, #e9ecf2 100%);
        border-radius: 24px;
        padding: 40px;
    }
    
    .crs-loading-spinner {
        width: 60px;
        height: 60px;
        border: 4px solid rgba(34, 113, 177, 0.1);
        border-left-color: #2271b1;
        border-radius: 50%;
        animation: crs-spin 1s linear infinite;
        margin-bottom: 20px;
    }
    
    @keyframes crs-spin {
        to { transform: rotate(360deg); }
    }
    
    .crs-loading-text {
        color: #1e293b;
        font-size: 16px;
        font-weight: 500;
        letter-spacing: 0.3px;
    }
    
    /* Error Container */
    .crs-error-container {
        max-width: 500px;
        margin: 60px auto;
        text-align: center;
        padding: 40px;
        background: #fff;
        border-radius: 24px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.08);
    }
    
    .crs-error-icon {
        font-size: 48px;
        margin-bottom: 20px;
    }
    
    .crs-error-container h3 {
        color: #1e293b;
        font-size: 22px;
        margin-bottom: 10px;
    }
    
    .crs-error-container p {
        color: #64748b;
        margin-bottom: 25px;
    }
    
    /* Main Container */
    .crs-booking-wrapper {
        max-width: 1400px;
        margin: 30px auto;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
        color: #1e293b;
        padding: 0 20px;
    }
    
    /* Navigation Breadcrumb */
    .crs-nav-breadcrumb {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 30px;
        padding: 12px 0;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .crs-breadcrumb-link {
        color: #2271b1;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: color 0.2s;
    }
    
    .crs-breadcrumb-link:hover {
        color: #135e96;
        text-decoration: underline;
    }
    
    .crs-breadcrumb-sep {
        color: #94a3b8;
        font-size: 16px;
    }
    
    .crs-breadcrumb-current {
        color: #475569;
        font-size: 14px;
        font-weight: 600;
    }
    
    /* Page Header */
    .crs-page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .crs-page-title {
        font-size: 32px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 5px 0;
        letter-spacing: -0.5px;
    }
    
    .crs-page-subtitle {
        color: #64748b;
        font-size: 15px;
    }
    
    .crs-booking-id {
        background: #f1f5f9;
        padding: 8px 16px;
        border-radius: 40px;
        font-size: 14px;
        font-weight: 600;
        color: #2271b1;
        letter-spacing: 0.5px;
    }
    
    /* Status Cards Grid */
    .crs-status-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }
    
    .crs-status-card {
        background: #fff;
        border-radius: 20px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        border: 1px solid #eef2f6;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .crs-status-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    }
    
    .crs-status-icon {
        width: 48px;
        height: 48px;
        background: #f8fafc;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }
    
    .crs-status-content {
        flex: 1;
    }
    
    .crs-status-label {
        display: block;
        font-size: 12px;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }
    
    .crs-status-value {
        font-size: 16px;
        font-weight: 600;
    }
    
    .crs-amount-large {
        font-size: 20px;
        color: #059669;
    }
    
    /* Status Colors */
    .crs-status-pending { color: #b45309; }
    .crs-status-confirmed { color: #059669; }
    .crs-status-completed { color: #2563eb; }
    .crs-status-cancelled { color: #dc2626; }
    .crs-payment-pending { color: #b45309; }
    .crs-payment-completed { color: #059669; }
    .crs-payment-failed { color: #dc2626; }
    
    /* Main Grid */
    .crs-main-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 25px;
    }
    
    @media (max-width: 968px) {
        .crs-main-grid {
            grid-template-columns: 1fr;
        }
    }
    
    /* Info Cards */
    .crs-info-card {
        background: #fff;
        border-radius: 24px;
        border: 1px solid #eef2f6;
        overflow: hidden;
        margin-bottom: 25px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        transition: box-shadow 0.2s;
    }
    
    .crs-info-card:hover {
        box-shadow: 0 8px 24px rgba(0,0,0,0.04);
    }
    
    .crs-featured-card {
        border-left: 4px solid #2271b1;
    }
    
    .crs-card-header {
        padding: 20px 24px;
        background: #fafcff;
        border-bottom: 1px solid #eef2f6;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .crs-card-icon {
        width: 32px;
        height: 32px;
        background: #fff;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.02);
    }
    
    .crs-card-title {
        font-size: 18px;
        font-weight: 600;
        color: #0f172a;
        margin: 0;
    }
    
    .crs-card-body {
        padding: 24px;
    }
    
    /* Detail Grid */
    .crs-detail-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    
    .crs-detail-item {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    
    .crs-detail-full {
        grid-column: span 2;
    }
    
    .crs-detail-label {
        font-size: 12px;
        font-weight: 500;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    
    .crs-detail-value {
        font-size: 15px;
        color: #1e293b;
        line-height: 1.5;
    }
    
    /* Note Box */
    .crs-note-box {
        margin-top: 20px;
        padding: 16px;
        background: #f0f9ff;
        border-radius: 16px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .crs-note-icon {
        font-size: 20px;
    }
    
    .crs-note-badge {
        background: #2271b1;
        color: white;
        padding: 4px 10px;
        border-radius: 40px;
        font-size: 11px;
        font-weight: 600;
        margin-left: 10px;
        text-transform: uppercase;
    }
    
    /* Items List */
    .crs-items-list {
        margin-bottom: 20px;
    }
    
    .crs-list-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px dashed #e2e8f0;
    }
    
    .crs-list-item:last-child {
        border-bottom: none;
    }
    
    .crs-item-info {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .crs-item-name {
        font-weight: 500;
        color: #1e293b;
    }
    
    .crs-item-badge {
        background: #f1f5f9;
        padding: 4px 8px;
        border-radius: 40px;
        font-size: 11px;
        color: #475569;
        font-weight: 500;
    }
    
    .crs-item-price {
        font-weight: 600;
        color: #059669;
    }
    
    .crs-total-box {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 0 0;
        margin-top: 10px;
        border-top: 2px solid #e2e8f0;
        font-size: 16px;
    }
    
    .crs-total-amount {
        font-weight: 700;
        color: #059669;
        font-size: 18px;
    }
    
    /* Workshop Grid */
    .crs-workshop-grid {
        display: grid;
        gap: 12px;
    }
    
    .crs-workshop-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        background: #f8fafc;
        border-radius: 12px;
    }
    
    .crs-workshop-icon {
        width: 28px;
        height: 28px;
        background: #fff;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
    }
    
    .crs-workshop-details {
        flex: 1;
    }
    
    .crs-workshop-details strong {
        display: block;
        color: #1e293b;
        margin-bottom: 4px;
    }
    
    .crs-workshop-meta {
        font-size: 13px;
        color: #64748b;
    }
    
    /* Congress Image */
    .crs-image-container {
        margin-bottom: 20px;
        border-radius: 16px;
        overflow: hidden;
        background: #f8fafc;
    }
    
    .crs-featured-image {
        width: 100%;
        max-height: 250px;
        object-fit: cover;
        display: block;
    }
    
    .crs-congress-name {
        font-size: 22px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 15px 0;
    }
    
    /* Pricing Box */
    .crs-pricing-box {
        background: #f8fafc;
        border-radius: 16px;
        padding: 16px;
    }
    
    .crs-price-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        color: #475569;
    }
    
    .crs-price-row:not(:last-child) {
        border-bottom: 1px solid #e2e8f0;
    }
    
    .crs-price-highlight {
        font-weight: 600;
        color: #2271b1;
    }
    
    /* Hotel Section */
    .crs-hotel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .crs-hotel-name {
        font-size: 18px;
        font-weight: 600;
        color: #0f172a;
        margin: 0;
    }
    
    .crs-price-tag {
        background: #e6f7e6;
        color: #059669;
        padding: 6px 12px;
        border-radius: 40px;
        font-size: 13px;
        font-weight: 600;
    }
    
    .crs-date-range {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
        background: #f8fafc;
        padding: 16px;
        border-radius: 16px;
    }
    
    .crs-date-box {
        flex: 1;
        text-align: center;
    }
    
    .crs-date-label {
        display: block;
        font-size: 11px;
        color: #64748b;
        text-transform: uppercase;
        margin-bottom: 4px;
    }
    
    .crs-date-value {
        font-size: 15px;
        font-weight: 500;
        color: #1e293b;
    }
    
    .crs-date-arrow {
        color: #94a3b8;
        font-size: 18px;
    }
    
    .crs-stay-summary {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding: 12px 0;
        border-top: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .crs-stay-nights {
        color: #475569;
        font-weight: 500;
    }
    
    .crs-stay-total {
        font-weight: 700;
        color: #059669;
        font-size: 18px;
    }
    
    .crs-address-box {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px;
        background: #f8fafc;
        border-radius: 12px;
        color: #475569;
    }
    
    /* Dietary Tags */
    .crs-diet-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 15px;
    }
    
    .crs-diet-tag {
        background: #f1f5f9;
        color: #1e293b;
        padding: 6px 14px;
        border-radius: 40px;
        font-size: 13px;
        font-weight: 500;
    }
    
    .crs-allergy-warning {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        background: #fef2f2;
        border-radius: 12px;
        border-left: 4px solid #ef4444;
    }
    
    .crs-allergy-icon {
        font-size: 18px;
    }
    
    .crs-allergy-text {
        color: #7f1d1d;
        font-size: 14px;
    }
    
    /* Additional Info */
    .crs-info-badge {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .crs-badge-label {
        color: #64748b;
        font-size: 14px;
    }
    
    .crs-badge-value {
        font-weight: 500;
        color: #1e293b;
    }
    
    .crs-observations {
        margin: 15px 0;
        padding: 15px;
        background: #f8fafc;
        border-radius: 12px;
        color: #475569;
        font-style: italic;
        line-height: 1.6;
    }
    
    .crs-proof-section {
        margin-top: 15px;
    }
    
    .crs-proof-label {
        display: block;
        margin-bottom: 10px;
        color: #475569;
        font-size: 14px;
    }
    
    .crs-proof-button {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 12px 20px;
        background: #f0f9ff;
        color: #2271b1;
        text-decoration: none;
        border-radius: 12px;
        font-weight: 500;
        transition: background 0.2s;
    }
    
    .crs-proof-button:hover {
        background: #e0f2fe;
    }
    
    .crs-proof-icon {
        font-size: 20px;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .crs-page-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .crs-detail-grid {
            grid-template-columns: 1fr;
        }
        
        .crs-detail-full {
            grid-column: span 1;
        }
        
        .crs-date-range {
            flex-direction: column;
            gap: 10px;
        }
        
        .crs-date-arrow {
            transform: rotate(90deg);
        }
        
        .crs-status-grid {
            grid-template-columns: 1fr;
        }
    }
    
    @media (max-width: 480px) {
        .crs-booking-wrapper {
            padding: 0 12px;
        }
        
        .crs-card-body {
            padding: 16px;
        }
        
        .crs-card-header {
            padding: 16px;
        }
        
        .crs-hotel-header {
            flex-direction: column;
            align-items: flex-start;
        }
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        console.log('Booking details loaded for ID: <?php echo $booking_id; ?>');
        
        // Add smooth scrolling
        $('.crs-booking-wrapper').css('opacity', '0').animate({opacity: 1}, 400);
    });
    </script>
    
    <?php
    return ob_get_clean();
}