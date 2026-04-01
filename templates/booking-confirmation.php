<?php
/**
 * Template: Booking Confirmation
 * Displays after successful payment
 * Shortcode: [booking_confirmation]
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

add_shortcode('booking_confirmation', 'crs_booking_confirmation_shortcode');

function crs_booking_confirmation_shortcode($atts) {
    ob_start();
    
    // Get booking ID from URL
    $booking_id = isset($_GET['booking']) ? intval($_GET['booking']) : 0;
    
    if (!$booking_id) {
        return '<div class="crs-error">No booking information found.</div>';
    }
    
    global $wpdb;
    $bookings_table = $wpdb->prefix . 'cr_bookings';
    
    // Get booking details
    $booking = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $bookings_table WHERE id = %d",
        $booking_id
    ));
    
    if (!$booking) {
        return '<div class="crs-error">Booking not found.</div>';
    }
    
    // Get additional data
    $additional = json_decode($booking->additional_options, true);
    $congress = get_post($booking->congress_id);
    $user = get_userdata($booking->user_id);
    
    // Get registration type
    $registration_type = '';
    if (!empty($additional['registration_type_id'])) {
        $type = $wpdb->get_row($wpdb->prepare(
            "SELECT name FROM {$wpdb->prefix}registration_types WHERE id = %d",
            $additional['registration_type_id']
        ));
        $registration_type = $type ? $type->name : '';
    }
    
    // Get hotel
    $hotel_name = '';
    if ($booking->selected_hotel_id) {
        $hotel = get_post($booking->selected_hotel_id);
        $hotel_name = $hotel ? $hotel->post_title : '';
    }
    
    // Calculate nights
    $nights = 0;
    if ($booking->check_in_date && $booking->check_out_date) {
        $nights = ceil((strtotime($booking->check_out_date) - strtotime($booking->check_in_date)) / (60 * 60 * 24));
    }
    
    // Get meals
    $meals_list = [];
    if ($booking->meal_preferences) {
        $meal_indices = json_decode($booking->meal_preferences, true);
        $meals = get_post_meta($booking->congress_id, 'congress_meals', true);
        if (is_array($meal_indices) && is_array($meals)) {
            foreach ($meal_indices as $index) {
                if (isset($meals[$index])) {
                    $meals_list[] = $meals[$index];
                }
            }
        }
    }
    
    // Get workshops
    $workshops_list = [];
    if ($booking->workshop_ids) {
        $workshop_indices = json_decode($booking->workshop_ids, true);
        $workshops = get_post_meta($booking->congress_id, 'congress_workshop', true);
        if (is_array($workshop_indices) && is_array($workshops)) {
            foreach ($workshop_indices as $index) {
                if (isset($workshops[$index])) {
                    $workshops_list[] = $workshops[$index];
                }
            }
        }
    }
    
    // Get dates
    $congress_start = get_post_meta($booking->congress_id, 'start_date', true);
    $congress_end = get_post_meta($booking->congress_id, 'end_date', true);
    $location = get_post_meta($booking->congress_id, 'location', true);
    
    ?>
    
    <div class="crs-confirmation-container">
        <!-- Success Header -->
        <div class="crs-confirmation-header">
            <div class="crs-success-animation">
                <svg class="crs-checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                    <circle class="crs-checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
                    <path class="crs-checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                </svg>
            </div>
            <h1 class="crs-confirmation-title">Booking Confirmed!</h1>
            <p class="crs-confirmation-subtitle">Thank you for your registration. Your booking has been successfully confirmed.</p>
        </div>
        
        <!-- Booking Info Cards -->
        <div class="crs-booking-grid">
            
            <!-- Main Info Card -->
            <div class="crs-info-card crs-card-primary">
                <div class="crs-card-header">
                    <span class="crs-card-icon">📋</span>
                    <h2>Booking Information</h2>
                </div>
                <div class="crs-card-body">
                    <div class="crs-info-row">
                        <span class="crs-info-label">Booking Number:</span>
                        <span class="crs-info-value crs-highlight"><?php echo esc_html($booking->booking_number); ?></span>
                    </div>
                    <div class="crs-info-row">
                        <span class="crs-info-label">Status:</span>
                        <span class="crs-info-value crs-status-badge crs-status-confirmed">Confirmed</span>
                    </div>
                    <div class="crs-info-row">
                        <span class="crs-info-label">Date:</span>
                        <span class="crs-info-value"><?php echo date_i18n('F j, Y', strtotime($booking->created_at)); ?></span>
                    </div>
                    <div class="crs-info-row">
                        <span class="crs-info-label">Total Amount:</span>
                        <span class="crs-info-value crs-amount">€<?php echo number_format($booking->total_amount, 2); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Personal Info Card -->
            <div class="crs-info-card">
                <div class="crs-card-header">
                    <span class="crs-card-icon">👤</span>
                    <h2>Personal Details</h2>
                </div>
                <div class="crs-card-body">
                    <div class="crs-info-row">
                        <span class="crs-info-label">Name:</span>
                        <span class="crs-info-value"><?php echo esc_html($additional['personal_data']['first_name'] . ' ' . $additional['personal_data']['last_name']); ?></span>
                    </div>
                    <div class="crs-info-row">
                        <span class="crs-info-label">Email:</span>
                        <span class="crs-info-value"><?php echo esc_html($additional['personal_data']['email']); ?></span>
                    </div>
                    <div class="crs-info-row">
                        <span class="crs-info-label">Phone:</span>
                        <span class="crs-info-value"><?php echo esc_html($additional['personal_data']['phone']); ?></span>
                    </div>
                    <div class="crs-info-row">
                        <span class="crs-info-label">ID/Passport:</span>
                        <span class="crs-info-value"><?php echo esc_html($additional['personal_data']['id_number']); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Congress Info Card -->
            <div class="crs-info-card">
                <div class="crs-card-header">
                    <span class="crs-card-icon">🏛️</span>
                    <h2>Congress Details</h2>
                </div>
                <div class="crs-card-body">
                    <div class="crs-info-row">
                        <span class="crs-info-label">Congress:</span>
                        <span class="crs-info-value crs-highlight"><?php echo esc_html(get_the_title($booking->congress_id)); ?></span>
                    </div>
                    <?php if ($congress_start && $congress_end): ?>
                    <div class="crs-info-row">
                        <span class="crs-info-label">Dates:</span>
                        <span class="crs-info-value"><?php echo date_i18n('M j', strtotime($congress_start)); ?> - <?php echo date_i18n('M j, Y', strtotime($congress_end)); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($location): ?>
                    <div class="crs-info-row">
                        <span class="crs-info-label">Location:</span>
                        <span class="crs-info-value"><?php echo esc_html($location); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($registration_type): ?>
                    <div class="crs-info-row">
                        <span class="crs-info-label">Registration:</span>
                        <span class="crs-info-value crs-badge"><?php echo esc_html($registration_type); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Accommodation Card -->
            <?php if ($booking->selected_hotel_id && $booking->selected_hotel_id != 0): ?>
            <div class="crs-info-card">
                <div class="crs-card-header">
                    <span class="crs-card-icon">🏨</span>
                    <h2>Accommodation</h2>
                </div>
                <div class="crs-card-body">
                    <div class="crs-info-row">
                        <span class="crs-info-label">Hotel:</span>
                        <span class="crs-info-value"><?php echo esc_html($hotel_name); ?></span>
                    </div>
                    <div class="crs-info-row">
                        <span class="crs-info-label">Check-in:</span>
                        <span class="crs-info-value"><?php echo date_i18n('M j, Y', strtotime($booking->check_in_date)); ?></span>
                    </div>
                    <div class="crs-info-row">
                        <span class="crs-info-label">Check-out:</span>
                        <span class="crs-info-value"><?php echo date_i18n('M j, Y', strtotime($booking->check_out_date)); ?></span>
                    </div>
                    <div class="crs-info-row">
                        <span class="crs-info-label">Nights:</span>
                        <span class="crs-info-value"><?php echo $nights; ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
        
        <!-- Extras Section -->
        <?php if (!empty($meals_list) || !empty($workshops_list)): ?>
        <div class="crs-extras-section">
            <h3 class="crs-section-title">Additional Selections</h3>
            
            <div class="crs-extras-grid">
                <?php if (!empty($meals_list)): ?>
                <div class="crs-extras-card">
                    <div class="crs-extras-header">
                        <span class="crs-extras-icon">🍽️</span>
                        <h4>Meals</h4>
                    </div>
                    <ul class="crs-extras-list">
                        <?php foreach ($meals_list as $meal): ?>
                        <li>
                            <span><?php echo esc_html($meal['meal_title']); ?></span>
                            <span class="crs-extras-price">€<?php echo $meal['meal_price']; ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($workshops_list)): ?>
                <div class="crs-extras-card">
                    <div class="crs-extras-header">
                        <span class="crs-extras-icon">🔧</span>
                        <h4>Workshops</h4>
                    </div>
                    <ul class="crs-extras-list">
                        <?php foreach ($workshops_list as $workshop): ?>
                        <li>
                            <span><?php echo esc_html($workshop['workshop_title']); ?></span>
                            <span class="crs-extras-badge">Selected</span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        

        

    </div>
    
    <style>
    /* Confirmation Container */
    .crs-confirmation-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 20px;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
    }
    
    /* Success Animation */
    .crs-confirmation-header {
        text-align: center;
        margin-bottom: 50px;
    }
    
    .crs-success-animation {
        margin-bottom: 25px;
    }
    
    .crs-checkmark {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: block;
        margin: 0 auto;
    }
    
    .crs-checkmark-circle {
        stroke: #10b981;
        stroke-width: 2;
        stroke-dasharray: 166;
        stroke-dashoffset: 166;
        animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
    }
    
    .crs-checkmark-check {
        stroke: #10b981;
        stroke-width: 2;
        stroke-dasharray: 48;
        stroke-dashoffset: 48;
        animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
    }
    
    @keyframes stroke {
        100% { stroke-dashoffset: 0; }
    }
    
    .crs-confirmation-title {
        font-size: 36px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 10px;
        letter-spacing: -0.5px;
    }
    
    .crs-confirmation-subtitle {
        font-size: 18px;
        color: #64748b;
        margin: 0;
        line-height: 1.6;
    }
    
    /* Booking Grid */
    .crs-booking-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 25px;
        margin-bottom: 40px;
    }
    
    /* Info Cards */
    .crs-info-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    
    .crs-info-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    
    .crs-card-primary {
        border-top: 4px solid #10b981;
    }
    
    .crs-card-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 20px 24px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .crs-card-header h2 {
        font-size: 18px;
        font-weight: 600;
        color: #0f172a;
        margin: 0;
    }
    
    .crs-card-icon {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #ffffff;
        border-radius: 10px;
        font-size: 18px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
    
    .crs-card-body {
        padding: 20px 24px;
    }
    
    .crs-info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px dashed #e2e8f0;
    }
    
    .crs-info-row:last-child {
        border-bottom: none;
    }
    
    .crs-info-label {
        font-size: 14px;
        font-weight: 500;
        color: #64748b;
    }
    
    .crs-info-value {
        font-size: 15px;
        font-weight: 500;
        color: #0f172a;
    }
    
    .crs-highlight {
        color: #10b981;
        font-weight: 600;
    }
    
    .crs-amount {
        font-size: 18px;
        font-weight: 700;
        color: #10b981;
    }
    
    .crs-status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
    }
    
    .crs-status-confirmed {
        background: #d1fae5;
        color: #065f46;
    }
    
    .crs-badge {
        background: #e2e8f0;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 13px;
    }
    
    /* Extras Section */
    .crs-extras-section {
        margin-bottom: 40px;
    }
    
    .crs-section-title {
        font-size: 22px;
        font-weight: 600;
        color: #0f172a;
        margin: 0 0 20px;
    }
    
    .crs-extras-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 25px;
    }
    
    .crs-extras-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 20px;
    }
    
    .crs-extras-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 15px;
    }
    
    .crs-extras-icon {
        font-size: 24px;
    }
    
    .crs-extras-header h4 {
        font-size: 16px;
        font-weight: 600;
        color: #0f172a;
        margin: 0;
    }
    
    .crs-extras-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .crs-extras-list li {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .crs-extras-list li:last-child {
        border-bottom: none;
    }
    
    .crs-extras-price {
        font-weight: 600;
        color: #10b981;
    }
    
    .crs-extras-badge {
        background: #e2e8f0;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    
    /* Action Buttons */
    .crs-action-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
        margin-bottom: 30px;
    }
    
    .crs-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 28px;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
        cursor: pointer;
        border: none;
    }
    
    .crs-btn-primary {
        background: #10b981;
        color: white;
        box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.2);
    }
    
    .crs-btn-primary:hover {
        background: #059669;
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);
    }
    
    .crs-btn-secondary {
        background: #f1f5f9;
        color: #0f172a;
    }
    
    .crs-btn-secondary:hover {
        background: #e2e8f0;
        transform: translateY(-2px);
    }
    
    .crs-btn-outline {
        background: transparent;
        color: #0f172a;
        border: 1px solid #e2e8f0;
    }
    
    .crs-btn-outline:hover {
        background: #f8fafc;
        border-color: #10b981;
        transform: translateY(-2px);
    }
    
    .crs-btn-icon {
        font-size: 18px;
    }
    
    /* Email Note */
    .crs-email-note {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        padding: 20px;
        background: #f0f9ff;
        border-radius: 12px;
        color: #0369a1;
        font-size: 15px;
        border: 1px solid #bae6fd;
    }
    
    .crs-email-icon {
        font-size: 20px;
    }
    
    .crs-email-note p {
        margin: 0;
    }
    
    .crs-email-note strong {
        font-weight: 600;
        color: #0284c7;
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .crs-confirmation-container {
            padding: 30px 15px;
        }
        
        .crs-confirmation-title {
            font-size: 28px;
        }
        
        .crs-confirmation-subtitle {
            font-size: 16px;
        }
        
        .crs-booking-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .crs-info-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }
        
        .crs-action-buttons {
            flex-direction: column;
        }
        
        .crs-btn {
            width: 100%;
            justify-content: center;
        }
        
        .crs-email-note {
            flex-direction: column;
            text-align: center;
        }
    }
    
    @media (max-width: 480px) {
        .crs-confirmation-title {
            font-size: 24px;
        }
        
        .crs-card-header {
            padding: 16px 20px;
        }
        
        .crs-card-body {
            padding: 16px 20px;
        }
        
        .crs-extras-grid {
            grid-template-columns: 1fr;
        }
    }
    
    /* Error State */
    .crs-error {
        max-width: 500px;
        margin: 50px auto;
        padding: 30px;
        background: #fee2e2;
        border: 1px solid #fecaca;
        border-radius: 12px;
        color: #991b1b;
        text-align: center;
        font-size: 16px;
    }
    </style>
    
    <?php
    return ob_get_clean();
}