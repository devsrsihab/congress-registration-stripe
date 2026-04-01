<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap crs-dashboard">
    <h1><?php _e('Congress Registration Dashboard', 'crscngres'); ?></h1>

    <div class="crs-stats-grid">
        <div class="crs-stat-card">
            <div class="crs-stat-icon">📅</div>
            <div class="crs-stat-content">
                <h3><?php echo $total_congresses; ?></h3>
                <p><?php _e('Total Congresses', 'crscngres'); ?></p>
            </div>
        </div>
        
        <div class="crs-stat-card">
            <div class="crs-stat-icon">🏨</div>
            <div class="crs-stat-content">
                <h3><?php echo $total_hotels; ?></h3>
                <p><?php _e('Hotels', 'crscngres'); ?></p>
            </div>
        </div>
        
        <div class="crs-stat-card">
            <div class="crs-stat-icon">📋</div>
            <div class="crs-stat-content">
                <h3><?php echo $total_bookings; ?></h3>
                <p><?php _e('Total Bookings', 'crscngres'); ?></p>
            </div>
        </div>
        
        <div class="crs-stat-card">
            <div class="crs-stat-icon">⏳</div>
            <div class="crs-stat-content">
                <h3><?php echo $pending_bookings; ?></h3>
                <p><?php _e('Pending', 'crscngres'); ?></p>
            </div>
        </div>
        
        <div class="crs-stat-card">
            <div class="crs-stat-icon">✅</div>
            <div class="crs-stat-content">
                <h3><?php echo $completed_bookings; ?></h3>
                <p><?php _e('Confirmed', 'crscngres'); ?></p>
            </div>
        </div>
        
        <div class="crs-stat-card">
            <div class="crs-stat-icon">💰</div>
            <div class="crs-stat-content">
                <h3><?php echo wc_price($total_revenue ?: 0); ?></h3>
                <p><?php _e('Total Revenue', 'crscngres'); ?></p>
            </div>
        </div>
    </div>
    
    <div class="crs-quick-actions">
        <h2><?php _e('Quick Actions', 'crscngres'); ?></h2>
        <div class="crs-action-buttons">
            <a href="<?php echo admin_url('post-new.php?post_type=congress'); ?>" class="button button-primary"><?php _e('Add New Congress', 'crscngres'); ?></a>
            <a href="<?php echo admin_url('post-new.php?post_type=hotels'); ?>" class="button button-primary"><?php _e('Add New Hotel', 'crscngres'); ?></a>
            <a href="<?php echo admin_url('admin.php?page=crs-registration-types'); ?>" class="button"><?php _e('Manage Registration Types', 'crscngres'); ?></a>
            <a href="<?php echo admin_url('admin.php?page=crs-bookings'); ?>" class="button"><?php _e('View All Bookings', 'crscngres'); ?></a>
        </div>
    </div>
    
    <div class="crs-recent-bookings">
        <h2><?php _e('Recent Bookings', 'crscngres'); ?></h2>
        <?php
        $recent_bookings = $wpdb->get_results(
            "SELECT * FROM $bookings_table ORDER BY created_at DESC LIMIT 5"
        );
        
        if ($recent_bookings) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>Booking #</th>';
            echo '<th>User</th>';
            echo '<th>Congress</th>';
            echo '<th>Amount</th>';
            echo '<th>Status</th>';
            echo '<th>Date</th>';
            echo '</tr></thead>';
            echo '<tbody>';
            
            foreach ($recent_bookings as $booking) {
                $user = get_userdata($booking->user_id);
                $username = $user ? $user->display_name : 'N/A';
                $congress_title = get_the_title($booking->congress_id);
                
                echo '<tr>';
                echo '<td>' . esc_html($booking->booking_number) . '</td>';
                echo '<td>' . esc_html($username) . '</td>';
                echo '<td>' . esc_html($congress_title) . '</td>';
                echo '<td>' . wc_price($booking->total_amount) . '</td>';
                echo '<td><span class="crs-status crs-status-' . esc_attr($booking->booking_status) . '">' . esc_html($booking->booking_status) . '</span></td>';
                echo '<td>' . date_i18n(get_option('date_format'), strtotime($booking->created_at)) . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
        } else {
            echo '<p>No bookings found.</p>';
        }
        ?>
    </div>
</div>

<style>
    .crs-dashboard { padding: 20px; }
    .crs-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 30px 0; }
    .crs-stat-card { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; display: flex; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .crs-stat-icon { font-size: 40px; margin-right: 15px; }
    .crs-stat-content h3 { margin: 0; font-size: 28px; color: #23282d; }
    .crs-stat-content p { margin: 5px 0 0; color: #666; }
    .crs-quick-actions { margin: 30px 0; }
    .crs-action-buttons { display: flex; gap: 10px; flex-wrap: wrap; }
    .crs-action-buttons .button { padding: 8px 20px; height: auto; }
    .crs-recent-bookings { margin-top: 30px; }
    .crs-status { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; }
    .crs-status-pending { background: #fff3cd; color: #856404; }
    .crs-status-confirmed { background: #d4edda; color: #155724; }
    .crs-status-cancelled { background: #f8d7da; color: #721c24; }
</style>