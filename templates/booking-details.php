<?php
if (!defined('ABSPATH')) exit;

// This file expects $booking, $additional, $meals_list, etc. to be set
// এখানে শুধু HTML Template থাকবে, কোন function থাকবে না

$booking_id = isset($booking) ? $booking->id : 0;
var_dump($booking)
?>

<div class="crs-loading-screen" id="crs-loading">
    <div class="crs-loading-spinner"></div>
    <p class="crs-loading-text">Loading your booking details...</p>
</div>

<div class="crs-booking-wrapper" id="crs-booking-container" style="display: none;">
    
    <!-- Header Section -->
    <div class="crs-page-header">
        <div class="crs-header-content">
            <h1 class="crs-page-title">Booking Overview</h1>
            <p class="crs-page-subtitle">Detailed information about your registration</p>
        </div>
        <div class="crs-header-actions">
            <span class="crs-booking-id">#<?php echo esc_html($booking->booking_number); ?></span>
        </div>
    </div>
    
    <!-- Status Cards -->
    <div class="crs-status-grid">
        <div class="crs-status-card">
            <div class="crs-status-icon">📋</div>
            <div class="crs-status-info">
                <span class="crs-status-label">Booking Status</span>
                <span class="crs-status-value crs-status-<?php echo esc_attr($booking->booking_status); ?>">
                    <?php echo esc_html(ucfirst($booking->booking_status)); ?>
                </span>
            </div>
        </div>
        
        <div class="crs-status-card">
            <div class="crs-status-icon">💳</div>
            <div class="crs-status-info">
                <span class="crs-status-label">Payment Status</span>
                <span class="crs-status-value crs-payment-<?php echo esc_attr($booking->payment_status); ?>">
                    <?php echo esc_html(ucfirst($booking->payment_status)); ?>
                </span>
            </div>
        </div>
        
        <div class="crs-status-card">
            <div class="crs-status-icon">💰</div>
            <div class="crs-status-info">
                <span class="crs-status-label">Total Amount</span>
                <span class="crs-status-value crs-amount">€<?php echo number_format($booking->total_amount, 2); ?></span>
            </div>
        </div>
        
        <div class="crs-status-card">
            <div class="crs-status-icon">📅</div>
            <div class="crs-status-info">
                <span class="crs-status-label">Created On</span>
                <span class="crs-status-value"><?php echo date_i18n('M j, Y', strtotime($booking->created_at)); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Main Content Grid -->
    <div class="crs-main-grid">
        <!-- Left Column -->
        <div class="crs-left-column">
            
            <!-- Personal Information -->
            <div class="crs-info-card">
                <div class="crs-card-header">
                    <span class="crs-card-icon">👤</span>
                    <h2 class="crs-card-title">Personal Information</h2>
                </div>
                <div class="crs-card-body">
                    <?php if (!empty($additional['personal_data'])): 
                        $personal = $additional['personal_data'];
                    ?>
                    <div class="crs-info-grid">
                        <div class="crs-info-item">
                            <span class="crs-info-label">Full Name</span>
                            <span class="crs-info-value"><?php echo esc_html($personal['first_name'] . ' ' . ($personal['last_name'] ?? '')); ?></span>
                        </div>
                        <div class="crs-info-item">
                            <span class="crs-info-label">ID/Passport</span>
                            <span class="crs-info-value"><?php echo esc_html($personal['id_number'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="crs-info-item">
                            <span class="crs-info-label">Email</span>
                            <span class="crs-info-value"><?php echo esc_html($personal['email'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="crs-info-item">
                            <span class="crs-info-label">Phone</span>
                            <span class="crs-info-value"><?php echo esc_html($personal['phone'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="crs-info-item crs-info-full">
                            <span class="crs-info-label">Address</span>
                            <span class="crs-info-value"><?php 
                                $addr = [];
                                if (!empty($personal['address'])) $addr[] = $personal['address'];
                                if (!empty($personal['location'])) $addr[] = $personal['location'];
                                if (!empty($personal['postal_code'])) $addr[] = $personal['postal_code'];
                                if (!empty($personal['country'])) $addr[] = $personal['country'];
                                echo !empty($addr) ? esc_html(implode(', ', $addr)) : 'N/A';
                            ?></span>
                        </div>
                    </div>
                    <?php else: ?>
                        <p class="crs-no-data">No personal data available</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Congress Details -->
            <div class="crs-info-card">
                <div class="crs-card-header">
                    <span class="crs-card-icon">🏛️</span>
                    <h2 class="crs-card-title">Congress Details</h2>
                </div>
                <div class="crs-card-body">
                    <h3 class="crs-congress-name"><?php echo esc_html($booking->congress_name); ?></h3>
                    <div class="crs-info-grid">
                        <div class="crs-info-item">
                            <span class="crs-info-label">Registration Type</span>
                            <span class="crs-info-value"><?php echo esc_html($reg_type_name ?: 'N/A'); ?></span>
                        </div>
                        <div class="crs-info-item">
                            <span class="crs-info-label">Price</span>
                            <span class="crs-info-value">€<?php echo number_format($reg_type_price, 0); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Accommodation -->
            <?php if (!empty($hotel_name)): ?>
            <div class="crs-info-card">
                <div class="crs-card-header">
                    <span class="crs-card-icon">🏨</span>
                    <h2 class="crs-card-title">Accommodation</h2>
                </div>
                <div class="crs-card-body">
                    <h3 class="crs-hotel-name"><?php echo esc_html($hotel_name); ?></h3>
                    <div class="crs-date-range">
                        <div class="crs-date-box">
                            <span class="crs-date-label">Check-in</span>
                            <span class="crs-date-value"><?php echo $booking->check_in_date ? date_i18n('M j, Y', strtotime($booking->check_in_date)) : 'N/A'; ?></span>
                        </div>
                        <div class="crs-date-arrow">→</div>
                        <div class="crs-date-box">
                            <span class="crs-date-label">Check-out</span>
                            <span class="crs-date-value"><?php echo $booking->check_out_date ? date_i18n('M j, Y', strtotime($booking->check_out_date)) : 'N/A'; ?></span>
                        </div>
                    </div>
                    <div class="crs-price-summary">
                        <span><?php echo $nights; ?> <?php echo $nights == 1 ? 'night' : 'nights'; ?></span>
                        <span class="crs-price-total">€<?php echo number_format($hotel_total, 0); ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right Column -->
        <div class="crs-right-column">
            
            <!-- Meals -->
            <?php if (!empty($meals_list)): ?>
            <div class="crs-info-card">
                <div class="crs-card-header">
                    <span class="crs-card-icon">🍽️</span>
                    <h2 class="crs-card-title">Meals</h2>
                </div>
                <div class="crs-card-body">
                    <?php foreach ($meals_list as $meal): ?>
                    <div class="crs-list-item">
                        <span><?php echo esc_html($meal['meal_title']); ?></span>
                        <span>€<?php echo number_format($meal['meal_price'], 0); ?></span>
                    </div>
                    <?php endforeach; ?>
                    <div class="crs-total-row">
                        <strong>Total Meals</strong>
                        <strong>€<?php echo number_format($meals_total, 0); ?></strong>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Workshops -->
            <?php if (!empty($workshops_list)): ?>
            <div class="crs-info-card">
                <div class="crs-card-header">
                    <span class="crs-card-icon">🔧</span>
                    <h2 class="crs-card-title">Workshops</h2>
                </div>
                <div class="crs-card-body">
                    <?php foreach ($workshops_list as $workshop): ?>
                    <div class="crs-workshop-item">
                        <span>📌</span>
                        <span><?php echo esc_html($workshop['workshop_title']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Dietary Information -->
            <?php if (!empty($dietary)): ?>
            <div class="crs-info-card">
                <div class="crs-card-header">
                    <span class="crs-card-icon">🥗</span>
                    <h2 class="crs-card-title">Dietary Information</h2>
                </div>
                <div class="crs-card-body">
                    <?php 
                    $diet_values = [];
                    if (!empty($dietary['diet'])) {
                        $diet_values = is_array($dietary['diet']) ? $dietary['diet'] : [$dietary['diet']];
                    }
                    ?>
                    <?php if (!empty($diet_values) && !(count($diet_values) == 1 && in_array('no', $diet_values))): ?>
                    <div class="crs-diet-tags">
                        <?php foreach ($diet_values as $diet): if ($diet === 'no') continue; ?>
                        <span class="crs-diet-tag"><?php echo ucfirst($diet); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="crs-action-buttons">
        <a href="<?php echo home_url('/account/'); ?>" class="crs-btn crs-btn-secondary">
            ← Back to Account
        </a>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    setTimeout(function() {
        $('#crs-loading').fadeOut(300, function() {
            $('#crs-booking-container').fadeIn(400);
        });
    }, 500);
});
</script>

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
    .crs-status-confirmed { color: #2563eb; }
    .crs-status-cancelled { color: #dc2626; }
    .crs-payment-pending { color: #b45309; }
    .crs-payment-confirmed { color: #059669; }
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
    
