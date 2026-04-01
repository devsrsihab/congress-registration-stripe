<?php
if (!defined('ABSPATH')) exit;

// Get booking ID from URL
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

if (!$booking_id) {
    echo '<div class="crs-error-container">
            <div class="crs-error-icon">🔍</div>
            <h3>Booking ID Required</h3>
            <p>Please provide a valid booking ID.</p>
            <a href="' . home_url('/account/') . '" class="crs-btn crs-btn-primary">View My Registrations</a>
          </div>';
    return;
}

global $wpdb;

// Get booking data with all details
$booking = $wpdb->get_row($wpdb->prepare("
    SELECT 
        b.*,
        p.post_title AS congress_name,
        p.post_content AS congress_description,
        hotel.post_title AS hotel_name,
        postmeta.meta_value AS congress_image_id,
        hotel_meta.meta_value AS hotel_price_per_night,
        hotel_meta_addr.meta_value AS hotel_address
    FROM {$wpdb->prefix}cr_bookings b
    LEFT JOIN {$wpdb->posts} p ON b.congress_id = p.ID
    LEFT JOIN {$wpdb->posts} hotel ON b.selected_hotel_id = hotel.ID
    LEFT JOIN {$wpdb->postmeta} postmeta ON (p.ID = postmeta.post_id AND postmeta.meta_key = 'image')
    LEFT JOIN {$wpdb->postmeta} hotel_meta ON (hotel.ID = hotel_meta.post_id AND hotel_meta.meta_key = 'price_per_night')
    LEFT JOIN {$wpdb->postmeta} hotel_meta_addr ON (hotel.ID = hotel_meta_addr.post_id AND hotel_meta_addr.meta_key = 'address')
    WHERE b.id = %d
", $booking_id));

if (!$booking) {
    echo '<div class="crs-error-container">
            <div class="crs-error-icon">😕</div>
            <h3>Booking Not Found</h3>
            <p>The booking you\'re looking for doesn\'t exist or has been removed.</p>
            <a href="' . home_url('/account/') . '" class="crs-btn crs-btn-primary">View My Registrations</a>
          </div>';
    return;
}

// Decode JSON fields
$additional = !empty($booking->additional_options) ? json_decode($booking->additional_options, true) : [];
$meal_preferences = !empty($booking->meal_preferences) ? json_decode($booking->meal_preferences, true) : [];
$workshop_ids = !empty($booking->workshop_ids) ? json_decode($booking->workshop_ids, true) : [];

// Get user data
$user = get_userdata($booking->user_id);

// Get registration type
$registration_type_name = '';
$registration_type_price = 0;
if (!empty($additional['registration_type_id'])) {
    $type = $wpdb->get_row($wpdb->prepare(
        "SELECT name, price FROM {$wpdb->prefix}registration_types WHERE id = %d",
        $additional['registration_type_id']
    ));
    if ($type) {
        $registration_type_name = $type->name;
        $registration_type_price = $type->price;
    }
}

// Get meals details
$meals_list = [];
$meals_total = 0;
if (!empty($meal_preferences) && $booking->congress_id) {
    $congress_meals = get_post_meta($booking->congress_id, 'congress_meals', true);
    if (is_array($congress_meals)) {
        foreach ($meal_preferences as $meal_index) {
            if (isset($congress_meals[$meal_index])) {
                $meals_list[] = $congress_meals[$meal_index];
                $meals_total += floatval($congress_meals[$meal_index]['meal_price']);
            }
        }
    }
}

// Get workshops details
$workshops_list = [];
if (!empty($workshop_ids) && $booking->congress_id) {
    $congress_workshops = get_post_meta($booking->congress_id, 'congress_workshop', true);
    if (is_array($congress_workshops)) {
        foreach ($workshop_ids as $workshop_index) {
            if (isset($congress_workshops[$workshop_index])) {
                $workshops_list[] = $congress_workshops[$workshop_index];
            }
        }
    }
}

// Calculate nights
$nights = 0;
if ($booking->check_in_date && $booking->check_out_date) {
    $datetime1 = new DateTime($booking->check_in_date);
    $datetime2 = new DateTime($booking->check_out_date);
    $interval = $datetime1->diff($datetime2);
    $nights = $interval->days;
}

$hotel_total = floatval($booking->hotel_price_per_night) * $nights;

// Get SIDI info
$add_sidi = isset($additional['add_sidi']) ? $additional['add_sidi'] : 0;

// Get invoice request
$invoice_request = isset($additional['invoice_request']) ? $additional['invoice_request'] : 0;
$company_details = isset($additional['company_details']) ? $additional['company_details'] : [];

// Get proof file
$proof_file_id = isset($additional['proof_file_id']) ? $additional['proof_file_id'] : 0;
$proof_file_url = $proof_file_id ? wp_get_attachment_url($proof_file_id) : '';
$proof_file_name = isset($additional['proof_file_name']) ? $additional['proof_file_name'] : '';

// Get dietary info
$dietary = isset($additional['dietary_info']) ? $additional['dietary_info'] : [];
$other = isset($additional['other_details']) ? $additional['other_details'] : [];

?>

<div class="crs-booking-single">
    <!-- Header -->
    <div class="crs-booking-header">
        <div class="crs-booking-header-content">
            <h1 class="crs-booking-title">Booking Details</h1>
            <p class="crs-booking-subtitle">Complete information about your registration</p>
        </div>
        <div class="crs-booking-badge">
            <span class="crs-booking-number">#<?php echo esc_html($booking->booking_number); ?></span>
            <span class="crs-status-badge crs-status-<?php echo esc_attr($booking->booking_status); ?>">
                <?php echo ucfirst($booking->booking_status); ?>
            </span>
        </div>
    </div>

    <!-- Status Cards -->
    <div class="crs-status-grid">
        <div class="crs-status-card">
            <div class="crs-status-icon">📋</div>
            <div class="crs-status-info">
                <span class="crs-status-label">Booking Status</span>
                <span class="crs-status-value crs-status-<?php echo esc_attr($booking->booking_status); ?>">
                    <?php echo ucfirst($booking->booking_status); ?>
                </span>
            </div>
        </div>
        
        <div class="crs-status-card">
            <div class="crs-status-icon">💳</div>
            <div class="crs-status-info">
                <span class="crs-status-label">Payment Status</span>
                <span class="crs-status-value crs-payment-<?php echo esc_attr($booking->payment_status); ?>">
                    <?php echo ucfirst($booking->payment_status); ?>
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
    <div class="crs-booking-grid">
        <!-- Left Column -->
        <div class="crs-booking-left">
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
                            <span class="crs-info-value"><?php echo esc_html($personal['first_name'] . ' ' . ($personal['last_name'] ?? 'N/A')); ?></span>
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
                        <?php if (!empty($personal['work_center'])): ?>
                        <div class="crs-info-item crs-info-full">
                            <span class="crs-info-label">Work Center</span>
                            <span class="crs-info-value"><?php echo esc_html($personal['work_center']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                        <p class="crs-no-data">No personal data available</p>
                    <?php endif; ?>
                    
                    <?php if (!empty($booking->third_person_name)): ?>
                    <div class="crs-note-box">
                        <span class="crs-note-icon">👥</span>
                        <div class="crs-note-content">
                            <strong>Registered For:</strong> <?php echo esc_html($booking->third_person_name); ?>
                            <span class="crs-note-badge">Third Person</span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Congress & Registration -->
            <div class="crs-info-card">
                <div class="crs-card-header">
                    <span class="crs-card-icon">🏛️</span>
                    <h2 class="crs-card-title">Congress Details</h2>
                </div>
                <div class="crs-card-body">
                    <?php if (!empty($booking->congress_name)): ?>
                        <h3 class="crs-congress-name"><?php echo esc_html($booking->congress_name); ?></h3>
                    <?php endif; ?>
                    
                    <div class="crs-info-grid">
                        <div class="crs-info-item">
                            <span class="crs-info-label">Registration Type</span>
                            <span class="crs-info-value"><?php echo esc_html($registration_type_name ?: 'N/A'); ?></span>
                        </div>
                        <div class="crs-info-item">
                            <span class="crs-info-label">Price</span>
                            <span class="crs-info-value">€<?php echo number_format($registration_type_price, 0); ?></span>
                        </div>
                        <?php if ($add_sidi): ?>
                        <div class="crs-info-item">
                            <span class="crs-info-label">+ SIDI 2026</span>
                            <span class="crs-info-value">€150</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Accommodation -->
            <?php if (!empty($booking->hotel_name)): ?>
            <div class="crs-info-card">
                <div class="crs-card-header">
                    <span class="crs-card-icon">🏨</span>
                    <h2 class="crs-card-title">Accommodation</h2>
                </div>
                <div class="crs-card-body">
                    <div class="crs-accommodation-details">
                        <h3 class="crs-hotel-name"><?php echo esc_html($booking->hotel_name); ?></h3>
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
                        <?php if (!empty($booking->hotel_address)): ?>
                        <div class="crs-hotel-address">
                            <span class="crs-address-icon">📍</span>
                            <span><?php echo esc_html($booking->hotel_address); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right Column -->
        <div class="crs-booking-right">
            <!-- Meals -->
            <?php if (!empty($meals_list)): ?>
            <div class="crs-info-card">
                <div class="crs-card-header">
                    <span class="crs-card-icon">🍽️</span>
                    <h2 class="crs-card-title">Meals</h2>
                </div>
                <div class="crs-card-body">
                    <div class="crs-items-list">
                        <?php foreach ($meals_list as $meal): ?>
                        <div class="crs-list-item">
                            <span class="crs-item-name"><?php echo esc_html($meal['meal_title']); ?></span>
                            <span class="crs-item-price">€<?php echo number_format($meal['meal_price'], 0); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="crs-total-row">
                        <span>Total Meals</span>
                        <span class="crs-total-amount">€<?php echo number_format($meals_total, 0); ?></span>
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
                    <div class="crs-workshop-list">
                        <?php foreach ($workshops_list as $workshop): ?>
                        <div class="crs-workshop-item">
                            <span class="crs-workshop-icon">📌</span>
                            <div class="crs-workshop-info">
                                <strong><?php echo esc_html($workshop['workshop_title']); ?></strong>
                                <?php if (!empty($workshop['workshop_date'])): ?>
                                <span class="crs-workshop-date">
                                    <?php echo date_i18n('F j, Y', strtotime($workshop['workshop_date'])); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
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
                    if (!empty($diet_values) && !(count($diet_values) == 1 && in_array('no', $diet_values))):
                    ?>
                    <div class="crs-diet-tags">
                        <?php foreach ($diet_values as $diet): if ($diet === 'no') continue; ?>
                        <span class="crs-diet-tag"><?php echo ucfirst($diet); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($dietary['diet_other'])): ?>
                    <p><strong>Other diet:</strong> <?php echo esc_html($dietary['diet_other']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($dietary['allergy']) && $dietary['allergy'] === 'yes'): ?>
                    <div class="crs-allergy-warning">
                        <span class="crs-allergy-icon">⚠️</span>
                        <div class="crs-allergy-text">
                            <strong>Allergies:</strong> <?php echo esc_html($dietary['allergy_details'] ?? 'Yes'); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Invoice Details -->
            <?php if ($invoice_request && !empty($company_details['company_name'])): ?>
            <div class="crs-info-card">
                <div class="crs-card-header">
                    <span class="crs-card-icon">📄</span>
                    <h2 class="crs-card-title">Invoice Details</h2>
                </div>
                <div class="crs-card-body">
                    <div class="crs-info-grid">
                        <div class="crs-info-item">
                            <span class="crs-info-label">Company</span>
                            <span class="crs-info-value"><?php echo esc_html($company_details['company_name']); ?></span>
                        </div>
                        <div class="crs-info-item">
                            <span class="crs-info-label">CIF</span>
                            <span class="crs-info-value"><?php echo esc_html($company_details['cif'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="crs-info-item crs-info-full">
                            <span class="crs-info-label">Tax Address</span>
                            <span class="crs-info-value"><?php echo esc_html($company_details['tax_address'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Proof Document -->
            <?php if (!empty($proof_file_url)): ?>
            <div class="crs-info-card">
                <div class="crs-card-header">
                    <span class="crs-card-icon">📎</span>
                    <h2 class="crs-card-title">Proof Document</h2>
                </div>
                <div class="crs-card-body">
                    <a href="<?php echo esc_url($proof_file_url); ?>" target="_blank" class="crs-download-btn">
                        <span class="crs-download-icon">📄</span>
                        <?php echo esc_html($proof_file_name ?: 'Download Document'); ?>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Observations -->
    <?php if (!empty($other['observations'])): ?>
    <div class="crs-observations-card">
        <div class="crs-card-header">
            <span class="crs-card-icon">💬</span>
            <h2 class="crs-card-title">Additional Observations</h2>
        </div>
        <div class="crs-card-body">
            <p><?php echo nl2br(esc_html($other['observations'])); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Action Buttons -->
    <div class="crs-action-buttons">
        <a href="<?php echo home_url('/account/'); ?>" class="crs-btn crs-btn-secondary">
            ← Back to My Registrations
        </a>
        <?php if ($booking->woocommerce_order_id): ?>
        <a href="<?php echo wc_get_order($booking->woocommerce_order_id)->get_view_order_url(); ?>" class="crs-btn crs-btn-primary" target="_blank">
            View Order Details
        </a>
        <?php endif; ?>
    </div>
</div>

<style>
/* Modern CSS Variables */
:root {
    --crs-primary: #2271b1;
    --crs-primary-dark: #135e96;
    --crs-success: #10b981;
    --crs-warning: #f59e0b;
    --crs-danger: #ef4444;
    --crs-gray-50: #f9fafb;
    --crs-gray-100: #f3f4f6;
    --crs-gray-200: #e5e7eb;
    --crs-gray-300: #d1d5db;
    --crs-gray-400: #9ca3af;
    --crs-gray-500: #6b7280;
    --crs-gray-600: #4b5563;
    --crs-gray-700: #374151;
    --crs-gray-800: #1f2937;
    --crs-gray-900: #111827;
    --crs-radius: 12px;
    --crs-radius-sm: 8px;
    --crs-shadow: 0 1px 3px rgba(0,0,0,0.1);
    --crs-shadow-lg: 0 10px 25px -5px rgba(0,0,0,0.1);
}

/* Main Container */
.crs-booking-single {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
}

/* Header */
.crs-booking-header {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border-radius: var(--crs-radius);
    padding: 30px 35px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
    color: white;
    box-shadow: var(--crs-shadow-lg);
}

.crs-booking-title {
    font-size: 28px;
    font-weight: 700;
    margin: 0 0 5px 0;
}

.crs-booking-subtitle {
    color: #94a3b8;
    margin: 0;
    font-size: 14px;
}

.crs-booking-badge {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.crs-booking-number {
    background: rgba(255,255,255,0.15);
    padding: 8px 16px;
    border-radius: 40px;
    font-size: 14px;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.crs-status-badge {
    padding: 8px 20px;
    border-radius: 40px;
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
}

.crs-status-pending { background: #f59e0b; color: white; }
.crs-status-confirmed { background: #10b981; color: white; }
.crs-status-confirmed { background: #2271b1; color: white; }
.crs-status-cancelled { background: #ef4444; color: white; }

/* Status Cards Grid */
.crs-status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.crs-status-card {
    background: white;
    border-radius: var(--crs-radius);
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    border: 1px solid var(--crs-gray-200);
    box-shadow: var(--crs-shadow);
    transition: all 0.2s;
}

.crs-status-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--crs-shadow-lg);
}

.crs-status-icon {
    font-size: 32px;
    width: 50px;
    height: 50px;
    background: var(--crs-gray-100);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.crs-status-info {
    flex: 1;
}

.crs-status-label {
    display: block;
    font-size: 12px;
    color: var(--crs-gray-500);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}

.crs-status-value {
    font-size: 18px;
    font-weight: 700;
}

.crs-status-pending { color: #f59e0b; }
.crs-status-confirmed { color: #2271b1; }
.crs-payment-pending { color: #f59e0b; }
.crs-payment-confirmed { color: #10b981; }
.crs-amount { color: #2271b1; }

/* Main Grid */
.crs-booking-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
    margin-bottom: 25px;
}

@media (max-width: 768px) {
    .crs-booking-grid {
        grid-template-columns: 1fr;
    }
}

/* Info Cards */
.crs-info-card {
    background: white;
    border-radius: var(--crs-radius);
    border: 1px solid var(--crs-gray-200);
    overflow: hidden;
    margin-bottom: 25px;
    box-shadow: var(--crs-shadow);
}

.crs-card-header {
    padding: 18px 22px;
    background: var(--crs-gray-50);
    border-bottom: 1px solid var(--crs-gray-200);
    display: flex;
    align-items: center;
    gap: 12px;
}

.crs-card-icon {
    font-size: 22px;
}

.crs-card-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--crs-gray-800);
    margin: 0;
}

.crs-card-body {
    padding: 22px;
}

/* Info Grid */
.crs-info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.crs-info-full {
    grid-column: span 2;
}

.crs-info-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.crs-info-label {
    font-size: 11px;
    font-weight: 500;
    color: var(--crs-gray-500);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.crs-info-value {
    font-size: 15px;
    font-weight: 500;
    color: var(--crs-gray-800);
    word-break: break-word;
}

.crs-no-data {
    color: var(--crs-gray-400);
    font-style: italic;
    margin: 0;
}

/* Note Box */
.crs-note-box {
    margin-top: 20px;
    padding: 15px;
    background: #fef3c7;
    border-radius: 10px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.crs-note-icon {
    font-size: 20px;
}

.crs-note-badge {
    background: #f59e0b;
    color: white;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 600;
    margin-left: 8px;
}

/* Congress Name */
.crs-congress-name {
    font-size: 18px;
    font-weight: 700;
    color: var(--crs-gray-800);
    margin: 0 0 15px 0;
    padding-bottom: 12px;
    border-bottom: 2px solid var(--crs-primary);
}

/* Accommodation */
.crs-accommodation-details {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.crs-hotel-name {
    font-size: 16px;
    font-weight: 600;
    color: var(--crs-gray-800);
    margin: 0;
}

.crs-date-range {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--crs-gray-50);
    padding: 15px;
    border-radius: var(--crs-radius-sm);
    text-align: center;
}

.crs-date-box {
    flex: 1;
}

.crs-date-label {
    display: block;
    font-size: 11px;
    color: var(--crs-gray-500);
    text-transform: uppercase;
    margin-bottom: 4px;
}

.crs-date-value {
    font-size: 14px;
    font-weight: 500;
}

.crs-date-arrow {
    color: var(--crs-gray-400);
    font-size: 18px;
    padding: 0 10px;
}

.crs-price-summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-top: 1px solid var(--crs-gray-200);
    border-bottom: 1px solid var(--crs-gray-200);
}

.crs-price-total {
    font-weight: 700;
    font-size: 18px;
    color: var(--crs-primary);
}

.crs-hotel-address {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--crs-gray-600);
    font-size: 13px;
    background: var(--crs-gray-50);
    padding: 12px;
    border-radius: var(--crs-radius-sm);
}

/* Meals List */
.crs-items-list {
    margin-bottom: 15px;
}

.crs-list-item {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px dashed var(--crs-gray-200);
}

.crs-list-item:last-child {
    border-bottom: none;
}

.crs-item-name {
    font-weight: 500;
}

.crs-item-price {
    font-weight: 600;
    color: var(--crs-primary);
}

.crs-total-row {
    display: flex;
    justify-content: space-between;
    padding-top: 12px;
    margin-top: 10px;
    border-top: 2px solid var(--crs-gray-200);
    font-weight: 700;
}

.crs-total-amount {
    color: var(--crs-primary);
    font-size: 16px;
}

/* Workshops */
.crs-workshop-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.crs-workshop-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: var(--crs-gray-50);
    border-radius: var(--crs-radius-sm);
}

.crs-workshop-icon {
    font-size: 18px;
}

.crs-workshop-info {
    flex: 1;
}

.crs-workshop-info strong {
    display: block;
    font-size: 14px;
}

.crs-workshop-date {
    font-size: 12px;
    color: var(--crs-gray-500);
}

/* Dietary Tags */
.crs-diet-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 15px;
}

.crs-diet-tag {
    background: #e0e7ff;
    color: #4338ca;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

.crs-allergy-warning {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px;
    background: #fef2f2;
    border-radius: var(--crs-radius-sm);
    border-left: 4px solid #ef4444;
}

.crs-allergy-icon {
    font-size: 16px;
}

.crs-allergy-text {
    font-size: 13px;
    color: #991b1b;
}

/* Download Button */
.crs-download-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 20px;
    background: #f0f9ff;
    color: var(--crs-primary);
    text-decoration: none;
    border-radius: var(--crs-radius-sm);
    font-weight: 500;
    transition: all 0.2s;
}

.crs-download-btn:hover {
    background: #e0f2fe;
    transform: translateY(-1px);
}

/* Observations Card */
.crs-observations-card {
    background: white;
    border-radius: var(--crs-radius);
    border: 1px solid var(--crs-gray-200);
    margin-bottom: 25px;
    overflow: hidden;
}

.crs-observations-card .crs-card-body {
    background: #fef9e8;
}

.crs-observations-card p {
    margin: 0;
    line-height: 1.6;
    color: var(--crs-gray-700);
}

/* Action Buttons */
.crs-action-buttons {
    display: flex;
    justify-content: flex-end;
    gap: 15px;
    flex-wrap: wrap;
}

.crs-btn {
    padding: 12px 28px;
    border-radius: var(--crs-radius-sm);
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
    display: inline-block;
    cursor: pointer;
    border: none;
}

.crs-btn-primary {
    background: var(--crs-primary);
    color: white;
}

.crs-btn-primary:hover {
    background: var(--crs-primary-dark);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(34,113,177,0.2);
}

.crs-btn-secondary {
    background: white;
    color: var(--crs-gray-700);
    border: 1px solid var(--crs-gray-300);
}

.crs-btn-secondary:hover {
    background: var(--crs-gray-50);
    border-color: var(--crs-gray-400);
}

/* Error Container */
.crs-error-container {
    max-width: 500px;
    margin: 80px auto;
    text-align: center;
    padding: 40px;
    background: white;
    border-radius: var(--crs-radius);
    box-shadow: var(--crs-shadow-lg);
}

.crs-error-icon {
    font-size: 48px;
    margin-bottom: 20px;
}

.crs-error-container h3 {
    margin-bottom: 10px;
    color: var(--crs-gray-800);
}

.crs-error-container p {
    color: var(--crs-gray-500);
    margin-bottom: 25px;
}

/* Responsive */
@media (max-width: 640px) {
    .crs-booking-single {
        margin: 20px auto;
        padding: 0 15px;
    }
    
    .crs-booking-header {
        padding: 20px;
        flex-direction: column;
        text-align: center;
    }
    
    .crs-booking-title {
        font-size: 22px;
    }
    
    .crs-info-grid {
        grid-template-columns: 1fr;
    }
    
    .crs-info-full {
        grid-column: span 1;
    }
    
    .crs-date-range {
        flex-direction: column;
        gap: 10px;
    }
    
    .crs-date-arrow {
        transform: rotate(90deg);
    }
    
    .crs-action-buttons {
        justify-content: center;
    }
    
    .crs-btn {
        width: 100%;
        text-align: center;
    }
    
    .crs-status-grid {
        grid-template-columns: 1fr;
    }
}
</style>