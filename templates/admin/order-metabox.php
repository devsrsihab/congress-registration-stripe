<?php if (!defined('ABSPATH')) exit; ?>
<style>
    .crs-order-metabox { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
    .crs-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px solid #e2e8f0; }
    .crs-header h3 { margin: 0; font-size: 20px; font-weight: 600; color: #1a202c; display: flex; align-items: center; gap: 8px; }
    .crs-badge { background: #10b981; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
    .crs-badge.pending { background: #f59e0b; }
    .crs-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px; }
    @media (max-width: 768px) { .crs-grid { grid-template-columns: 1fr; } }
    .crs-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; }
    .crs-card-title { font-size: 16px; font-weight: 600; color: #1e293b; margin: 0 0 16px 0; padding-bottom: 8px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; gap: 8px; }
    .crs-info-row { display: flex; margin-bottom: 10px; font-size: 14px; }
    .crs-info-label { width: 110px; font-weight: 500; color: #64748b; }
    .crs-info-value { flex: 1; color: #1e293b; font-weight: 400; }
    .crs-tag { display: inline-block; background: #e2e8f0; padding: 2px 8px; border-radius: 12px; font-size: 12px; color: #475569; margin-right: 4px; margin-bottom: 4px; }
    .crs-tag.meal { background: #dbeafe; color: #1e40af; }
    .crs-tag.workshop { background: #fef3c7; color: #92400e; }
    .crs-tag.sidi { background: #10b981; color: white; }
    .crs-total-section { margin-top: 20px; padding: 16px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px; color: white; display: flex; justify-content: space-between; align-items: center; }
    .crs-total-label { font-size: 18px; font-weight: 500; }
    .crs-total-amount { font-size: 24px; font-weight: 700; }
    .crs-empty { color: #94a3b8; font-style: italic; font-size: 13px; }
    .crs-actions { margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end; }
    .crs-button { padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 500; text-decoration: none; cursor: pointer; transition: all 0.2s; }
    .crs-button-primary { background: #3b82f6; color: white; border: none; }
    .crs-button-primary:hover { background: #2563eb; }
    .crs-button-secondary { background: white; color: #4b5563; border: 1px solid #d1d5db; }
</style>

<div class="crs-order-metabox">
    <div class="crs-header">
        <h3>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            Congress Registration Details
        </h3>
        <div class="crs-badge <?php echo $order->get_status(); ?>">
            Booking #: <?php echo esc_html($booking_number); ?>
        </div>
    </div>
    
    <div class="crs-grid">
        <!-- Personal Information Card -->
        <div class="crs-card">
            <div class="crs-card-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                Personal Information
            </div>
            <?php if (!empty($data['personal_data'])): ?>
                <div class="crs-info-row"><span class="crs-info-label">Full Name:</span><span class="crs-info-value"><?php echo esc_html($data['personal_data']['first_name'] . ' ' . $data['personal_data']['last_name']); ?></span></div>
                <div class="crs-info-row"><span class="crs-info-label">ID/Passport:</span><span class="crs-info-value"><?php echo esc_html($data['personal_data']['id_number']); ?></span></div>
                <div class="crs-info-row"><span class="crs-info-label">Email:</span><span class="crs-info-value"><?php echo esc_html($data['personal_data']['email']); ?></span></div>
                <div class="crs-info-row"><span class="crs-info-label">Phone:</span><span class="crs-info-value"><?php echo esc_html($data['personal_data']['phone']); ?></span></div>
                <div class="crs-info-row"><span class="crs-info-label">Address:</span><span class="crs-info-value"><?php echo esc_html($data['personal_data']['address'] . ', ' . $data['personal_data']['location'] . ', ' . $data['personal_data']['postal_code'] . ', ' . $data['personal_data']['country']); ?></span></div>
                <?php if (!empty($data['personal_data']['work_center'])): ?>
                <div class="crs-info-row"><span class="crs-info-label">Work Center:</span><span class="crs-info-value"><?php echo esc_html($data['personal_data']['work_center']); ?></span></div>
                <?php endif; ?>
            <?php else: ?>
                <span class="crs-empty">No personal data available</span>
            <?php endif; ?>
        </div>
        
        <!-- Congress & Registration Card -->
        <div class="crs-card">
            <div class="crs-card-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                Congress & Registration
            </div>
            <div class="crs-info-row"><span class="crs-info-label">Congress:</span><span class="crs-info-value"><strong><?php echo esc_html($congress_title); ?></strong></span></div>
            <?php if (!empty($congress_dates)): ?>
            <div class="crs-info-row"><span class="crs-info-label">Dates:</span><span class="crs-info-value"><?php echo esc_html($congress_dates); ?></span></div>
            <?php endif; ?>
            <div class="crs-info-row"><span class="crs-info-label">Registration Type:</span><span class="crs-info-value"><?php echo esc_html($registration_type_name ?: 'N/A'); ?></span></div>
            <?php if ($add_sidi): ?>
            <div class="crs-info-row"><span class="crs-info-label"></span><span class="crs-info-value"><span class="crs-tag sidi">+ SIDI Congress 2026</span></span></div>
            <?php endif; ?>
        </div>
        
        <!-- Accommodation Card -->
        <div class="crs-card">
            <div class="crs-card-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                Accommodation
            </div>
            <?php if ($hotel_name): ?>
                <div class="crs-info-row"><span class="crs-info-label">Hotel:</span><span class="crs-info-value"><strong><?php echo esc_html($hotel_name); ?></strong> (€<?php echo $price_per_night; ?>/night)</span></div>
                <div class="crs-info-row"><span class="crs-info-label">Check-in:</span><span class="crs-info-value"><?php echo date_i18n('M j, Y', strtotime($check_in)); ?></span></div>
                <div class="crs-info-row"><span class="crs-info-label">Check-out:</span><span class="crs-info-value"><?php echo date_i18n('M j, Y', strtotime($check_out)); ?></span></div>
                <div class="crs-info-row"><span class="crs-info-label">Nights:</span><span class="crs-info-value"><?php echo $nights; ?> <?php echo $nights == 1 ? 'night' : 'nights'; ?></span></div>
            <?php else: ?>
                <span class="crs-empty">No accommodation selected</span>
            <?php endif; ?>
        </div>
        
        <!-- Meals & Workshops Card -->
        <div class="crs-card">
            <div class="crs-card-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
                    <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
                </svg>
                Meals & Workshops
            </div>
            <?php if (!empty($meals_details)): ?>
                <div class="crs-info-row">
                    <span class="crs-info-label">Meals:</span>
                    <span class="crs-info-value"><?php foreach ($meals_details as $meal): ?><span class="crs-tag meal"><?php echo esc_html($meal['meal_title']); ?></span><?php endforeach; ?></span>
                </div>
            <?php else: ?>
                <div class="crs-info-row"><span class="crs-info-label">Meals:</span><span class="crs-info-value crs-empty">No meals selected</span></div>
            <?php endif; ?>
            <?php if (!empty($workshops_details)): ?>
                <div class="crs-info-row">
                    <span class="crs-info-label">Workshops:</span>
                    <span class="crs-info-value"><?php foreach ($workshops_details as $workshop): ?><span class="crs-tag workshop"><?php echo esc_html($workshop['workshop_title']); ?></span><?php endforeach; ?></span>
                </div>
            <?php else: ?>
                <div class="crs-info-row"><span class="crs-info-label">Workshops:</span><span class="crs-info-value crs-empty">No workshops selected</span></div>
            <?php endif; ?>
            <?php if (!empty($free_communication)): ?>
            <div class="crs-info-row"><span class="crs-info-label">Communication:</span><span class="crs-info-value"><?php echo ucfirst($free_communication); ?></span></div>
            <?php endif; ?>
        </div>
        
        <!-- Dietary Information Card -->
        <div class="crs-card">
            <div class="crs-card-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
                    <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
                </svg>
                Dietary Information
            </div>
            <?php if (!empty($diet) && $diet != 'no'): ?>
            <div class="crs-info-row"><span class="crs-info-label">Diet:</span><span class="crs-info-value"><span class="crs-dietary-badge <?php echo $diet; ?>"><?php echo ucfirst($diet); ?></span><?php if ($diet_other): ?><span>(<?php echo esc_html($diet_other); ?>)</span><?php endif; ?></span></div>
            <?php endif; ?>
            <?php if (!empty($allergy) && $allergy == 'yes'): ?>
            <div class="crs-info-row"><span class="crs-info-label">Allergies:</span><span class="crs-info-value"><span class="crs-dietary-badge allergy">Yes</span><?php if ($allergy_details): ?><span><?php echo esc_html($allergy_details); ?></span><?php endif; ?></span></div>
            <?php endif; ?>
            <?php if ((empty($diet) || $diet == 'no') && (empty($allergy) || $allergy == 'no')): ?>
            <span class="crs-empty">No dietary restrictions specified</span>
            <?php endif; ?>
        </div>
        
        <!-- Invoice & Additional Info Card -->
        <div class="crs-card">
            <div class="crs-card-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                Invoice & Additional Info
            </div>
            <div class="crs-info-row"><span class="crs-info-label">Invoice Request:</span><span class="crs-info-value"><span class="crs-status-indicator <?php echo $request_invoice ? 'crs-status-yes' : 'crs-status-no'; ?>"></span><?php echo $request_invoice ? 'Yes' : 'No'; ?></span></div>
            <?php if ($request_invoice && !empty($company_name)): ?>
                <div class="crs-info-row"><span class="crs-info-label">Company:</span><span class="crs-info-value"><?php echo esc_html($company_name); ?></span></div>
                <div class="crs-info-row"><span class="crs-info-label">CIF:</span><span class="crs-info-value"><?php echo esc_html($cif); ?></span></div>
                <div class="crs-info-row"><span class="crs-info-label">Tax Address:</span><span class="crs-info-value"><?php echo esc_html($tax_address); ?></span></div>
            <?php endif; ?>
            <div class="crs-info-row"><span class="crs-info-label">Image Release:</span><span class="crs-info-value"><span class="crs-status-indicator <?php echo $image_release ? 'crs-status-yes' : 'crs-status-no'; ?>"></span><?php echo $image_release ? 'Authorized' : 'Not Authorized'; ?></span></div>
            <?php if (!empty($observations)): ?>
            <div class="crs-info-row"><span class="crs-info-label">Observations:</span><span class="crs-info-value"><?php echo esc_html($observations); ?></span></div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($order_total = $order->get_total()): ?>
    <div class="crs-total-section">
        <span class="crs-total-label">Total Registration Amount</span>
        <span class="crs-total-amount"><?php echo wc_price($order_total); ?></span>
    </div>
    <?php endif; ?>
    
    <div class="crs-actions">
        <?php if ($booking_id): ?>
            <a href="<?php echo admin_url('admin.php?page=crs-bookings&action=view&id=' . $booking_id); ?>" class="crs-button crs-button-secondary" target="_blank">View Full Registration</a>
        <?php endif; ?>
        <?php if ($invoice_url = $order->get_meta('_crs_invoice_url')): ?>
            <a href="<?php echo esc_url($invoice_url); ?>" class="crs-button crs-button-primary" target="_blank">Download Invoice</a>
        <?php endif; ?>
    </div>
</div>