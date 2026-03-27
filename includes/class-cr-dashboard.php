<?php
class CR_Dashboard {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_bar_menu', array($this, 'add_admin_bar_link'), 100);
    }
    
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('Congress Registration', CR_TEXT_DOMAIN),
            __('Congress Reg', CR_TEXT_DOMAIN),
            'manage_options',
            'congress-registration',
            array($this, 'render_dashboard'),
            'dashicons-calendar-alt',
            25
        );
        
        // Submenu: Dashboard
        add_submenu_page(
            'congress-registration',
            __('Dashboard', CR_TEXT_DOMAIN),
            __('Dashboard', CR_TEXT_DOMAIN),
            'manage_options',
            'congress-registration',
            array($this, 'render_dashboard')
        );
        
        // Submenu: All Bookings
        add_submenu_page(
            'congress-registration',
            __('Bookings', CR_TEXT_DOMAIN),
            __('Bookings', CR_TEXT_DOMAIN),
            'manage_options',
            'cr-bookings',
            array($this, 'render_bookings_page')
        );
        
        // Submenu: Settings
        add_submenu_page(
            'congress-registration',
            __('Settings', CR_TEXT_DOMAIN),
            __('Settings', CR_TEXT_DOMAIN),
            'manage_options',
            'cr-settings',
            array($this, 'render_settings_page')
        );
        
        // Submenu: Shortcodes Info
        add_submenu_page(
            'congress-registration',
            __('Shortcodes', CR_TEXT_DOMAIN),
            __('Shortcodes', CR_TEXT_DOMAIN),
            'manage_options',
            'cr-shortcodes',
            array($this, 'render_shortcodes_page')
        );
    }
    
    public function add_admin_bar_link($wp_admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $wp_admin_bar->add_node(array(
            'id'    => 'cr-dashboard',
            'title' => __('Congress Reg', CR_TEXT_DOMAIN),
            'href'  => admin_url('admin.php?page=congress-registration'),
            'meta'  => array('class' => 'cr-admin-bar-link')
        ));
    }
    
    public function render_dashboard() {
        ?>
        <div class="wrap cr-dashboard">
            <h1><?php _e('Congress Registration Dashboard', CR_TEXT_DOMAIN); ?></h1>
            
            <div class="cr-stats-grid">
                <?php
                $total_congresses = wp_count_posts('congress')->publish;
                $total_hotels = wp_count_posts('hotels')->publish;
                
                global $wpdb;
                $table_name = $wpdb->prefix . 'cr_bookings';
                $total_bookings = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
                $pending_bookings = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE booking_status = 'pending'");
                $completed_bookings = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE booking_status = 'completed'");
                ?>
                
                <div class="cr-stat-card">
                    <div class="cr-stat-icon">📅</div>
                    <div class="cr-stat-content">
                        <h3><?php echo $total_congresses; ?></h3>
                        <p><?php _e('Total Congresses', CR_TEXT_DOMAIN); ?></p>
                    </div>
                </div>
                
                <div class="cr-stat-card">
                    <div class="cr-stat-icon">🏨</div>
                    <div class="cr-stat-content">
                        <h3><?php echo $total_hotels; ?></h3>
                        <p><?php _e('Hotels', CR_TEXT_DOMAIN); ?></p>
                    </div>
                </div>

                
                <div class="cr-stat-card">
                    <div class="cr-stat-icon">📋</div>
                    <div class="cr-stat-content">
                        <h3><?php echo $total_bookings; ?></h3>
                        <p><?php _e('Total Bookings', CR_TEXT_DOMAIN); ?></p>
                    </div>
                </div>
                
                <div class="cr-stat-card">
                    <div class="cr-stat-icon">⏳</div>
                    <div class="cr-stat-content">
                        <h3><?php echo $pending_bookings; ?></h3>
                        <p><?php _e('Pending', CR_TEXT_DOMAIN); ?></p>
                    </div>
                </div>
                
                <div class="cr-stat-card">
                    <div class="cr-stat-icon">✅</div>
                    <div class="cr-stat-content">
                        <h3><?php echo $completed_bookings; ?></h3>
                        <p><?php _e('Completed', CR_TEXT_DOMAIN); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="cr-quick-actions">
                <h2><?php _e('Quick Actions', CR_TEXT_DOMAIN); ?></h2>
                <div class="cr-action-buttons">
                    <a href="<?php echo admin_url('post-new.php?post_type=congress'); ?>" class="button button-primary">
                        <?php _e('Add New Congress', CR_TEXT_DOMAIN); ?>
                    </a>
                    <a href="<?php echo admin_url('post-new.php?post_type=hotels'); ?>" class="button">
                        <?php _e('Add New Hotel', CR_TEXT_DOMAIN); ?>
                    </a>
      
                    <a href="<?php echo admin_url('admin.php?page=cr-settings'); ?>" class="button">
                        <?php _e('Settings', CR_TEXT_DOMAIN); ?>
                    </a>
                </div>
            </div>
            
            <div class="cr-recent-bookings">
                <h2><?php _e('Recent Bookings', CR_TEXT_DOMAIN); ?></h2>
                <?php
                global $wpdb;
                $recent_bookings = $wpdb->get_results(
                    "SELECT * FROM {$wpdb->prefix}cr_bookings 
                    ORDER BY created_at DESC 
                    LIMIT 5"
                );
                
                if ($recent_bookings) {
                    echo '<table class="wp-list-table widefat fixed striped">';
                    echo '<thead><tr>';
                    echo '<th>' . __('Booking #', CR_TEXT_DOMAIN) . '</th>';
                    echo '<th>' . __('User', CR_TEXT_DOMAIN) . '</th>';
                    echo '<th>' . __('Congress', CR_TEXT_DOMAIN) . '</th>';
                    echo '<th>' . __('Amount', CR_TEXT_DOMAIN) . '</th>';
                    echo '<th>' . __('Status', CR_TEXT_DOMAIN) . '</th>';
                    echo '<th>' . __('Date', CR_TEXT_DOMAIN) . '</th>';
                    echo '</tr></thead>';
                    echo '<tbody>';
                    
                    foreach ($recent_bookings as $booking) {
                        $user_info = get_userdata($booking->user_id);
                        $username = $user_info ? $user_info->user_login : 'N/A';
                        $congress_title = get_the_title($booking->congress_id);
                        
                        echo '<tr>';
                        echo '<td>' . esc_html($booking->booking_number) . '</td>';
                        echo '<td>' . esc_html($username) . '</td>';
                        echo '<td>' . esc_html($congress_title) . '</td>';
                        echo '<td>' . wc_price($booking->total_amount) . '</td>';
                        echo '<td><span class="cr-status cr-status-' . esc_attr($booking->booking_status) . '">' . 
                             esc_html($booking->booking_status) . '</span></td>';
                        echo '<td>' . date_i18n(get_option('date_format'), strtotime($booking->created_at)) . '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</tbody>';
                    echo '</table>';
                } else {
                    echo '<p>' . __('No bookings found.', CR_TEXT_DOMAIN) . '</p>';
                }
                ?>
            </div>
        </div>
        
        <style>
            .cr-dashboard {
                padding: 20px;
            }
            .cr-stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin: 30px 0;
            }
            .cr-stat-card {
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 20px;
                display: flex;
                align-items: center;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            .cr-stat-icon {
                font-size: 40px;
                margin-right: 15px;
            }
            .cr-stat-content h3 {
                margin: 0;
                font-size: 28px;
                color: #23282d;
            }
            .cr-stat-content p {
                margin: 5px 0 0;
                color: #666;
            }
            .cr-quick-actions {
                margin: 30px 0;
            }
            .cr-action-buttons {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }
            .cr-action-buttons .button {
                padding: 8px 20px;
                height: auto;
            }
            .cr-recent-bookings {
                margin-top: 30px;
            }
            .cr-status {
                padding: 4px 8px;
                border-radius: 4px;
                font-size: 12px;
                font-weight: 500;
            }
            .cr-status-pending {
                background: #fff3cd;
                color: #856404;
            }
            .cr-status-completed {
                background: #d4edda;
                color: #155724;
            }
            .cr-status-cancelled {
                background: #f8d7da;
                color: #721c24;
            }
        </style>
        <?php
    }
    
    public function render_bookings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Bookings Management', CR_TEXT_DOMAIN); ?></h1>
            <?php
            // Include bookings list table
            if (!class_exists('WP_List_Table')) {
                require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
            }
            
            require_once CR_PLUGIN_DIR . 'includes/class-cr-bookings-list-table.php';
            
            $bookings_table = new CR_Bookings_List_Table();
            $bookings_table->prepare_items();
            $bookings_table->display();
            ?>
        </div>
        <?php
    }
    
    public function render_settings_page() {
        if (isset($_POST['submit'])) {
            check_admin_referer('cr_settings_nonce');
            
            // Save WooCommerce settings
            update_option('cr_enable_woocommerce', isset($_POST['enable_woocommerce']) ? 1 : 0);
            update_option('cr_currency', sanitize_text_field($_POST['currency']));
            update_option('cr_payment_methods', array_map('sanitize_text_field', $_POST['payment_methods']));
            
            echo '<div class="notice notice-success"><p>' . __('Settings saved.', CR_TEXT_DOMAIN) . '</p></div>';
        }
        
        $enable_woocommerce = get_option('cr_enable_woocommerce', 1);
        $currency = get_option('cr_currency', 'USD');
        $payment_methods = get_option('cr_payment_methods', array('woocommerce'));
        ?>
        <div class="wrap">
            <h1><?php _e('Congress Registration Settings', CR_TEXT_DOMAIN); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('cr_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="enable_woocommerce"><?php _e('Enable WooCommerce Integration', CR_TEXT_DOMAIN); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="enable_woocommerce" id="enable_woocommerce" value="1" <?php checked(1, $enable_woocommerce); ?>>
                            <p class="description"><?php _e('Use WooCommerce for payment processing', CR_TEXT_DOMAIN); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="currency"><?php _e('Currency', CR_TEXT_DOMAIN); ?></label>
                        </th>
                        <td>
                            <select name="currency" id="currency">
                                <option value="USD" <?php selected('USD', $currency); ?>>USD</option>
                                <option value="EUR" <?php selected('EUR', $currency); ?>>EUR</option>
                                <option value="GBP" <?php selected('GBP', $currency); ?>>GBP</option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label><?php _e('Payment Methods', CR_TEXT_DOMAIN); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="payment_methods[]" value="woocommerce" <?php checked(in_array('woocommerce', $payment_methods)); ?>>
                                <?php _e('WooCommerce', CR_TEXT_DOMAIN); ?>
                            </label><br>
                            <label>
                                <input type="checkbox" name="payment_methods[]" value="bank_transfer" <?php checked(in_array('bank_transfer', $payment_methods)); ?>>
                                <?php _e('Bank Transfer', CR_TEXT_DOMAIN); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function render_shortcodes_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Available Shortcodes', CR_TEXT_DOMAIN); ?></h1>
            
            <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px;">
                <h2>[congress_dashboard]</h2>
                <p><?php _e('Displays the user dashboard with tabs for Available Conferences, My Registrations, and My Documents.', CR_TEXT_DOMAIN); ?></p>
                <p><strong><?php _e('Usage:', CR_TEXT_DOMAIN); ?></strong> <?php _e('Add this shortcode to any page where you want users to see their congress dashboard.', CR_TEXT_DOMAIN); ?></p>
                <p><strong><?php _e('Example:', CR_TEXT_DOMAIN); ?></strong> [congress_dashboard show_tabs="yes"]</p>
                <hr>
                
                <h2>[congress_registration_form]</h2>
                <p><?php _e('Displays the multi-step registration form for congress booking.', CR_TEXT_DOMAIN); ?></p>
                <p><strong><?php _e('Usage:', CR_TEXT_DOMAIN); ?></strong> <?php _e('Add this shortcode to create the registration page.', CR_TEXT_DOMAIN); ?></p>
                <p><strong><?php _e('Parameters:', CR_TEXT_DOMAIN); ?></strong> congress_id="123" (optional - pre-select a specific congress)</p>
                <hr>
                
                <h2>[congress_list]</h2>
                <p><?php _e('Displays a list of available congresses with registration buttons.', CR_TEXT_DOMAIN); ?></p>
                <p><strong><?php _e('Usage:', CR_TEXT_DOMAIN); ?></strong> [congress_list limit="10" show_past="no"]</p>
                <hr>
                
                <h2>[congress_registration]</h2>
                <p><?php _e('Displays the congress registration form with all available options.', CR_TEXT_DOMAIN); ?></p>
                <p><strong><?php _e('Usage:', CR_TEXT_DOMAIN); ?></strong> [congress_registration limit="10" show_past="no"]</p>
                <hr>
                
                <h2>[user_bookings]</h2>
                <p><?php _e('Shows the current user\'s bookings.', CR_TEXT_DOMAIN); ?></p>
                <p><strong><?php _e('Usage:', CR_TEXT_DOMAIN); ?></strong> [user_bookings]</p>
            </div>
        </div>
        <?php
    }
}