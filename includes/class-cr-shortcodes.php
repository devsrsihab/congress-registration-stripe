<?php
class CR_Shortcodes {
    
    public function __construct() {
        add_shortcode('congress_dashboard', array($this, 'render_dashboard'));
        add_shortcode('congress_registration_form', array($this, 'render_registration_form'));
        add_shortcode('congress_list', array($this, 'render_congress_list'));
        add_shortcode('user_bookings', array($this, 'render_user_bookings'));
    }
    
    public function render_dashboard($atts) {
        if (!is_user_logged_in()) {
            return $this->get_login_message();
        }
        
        ob_start();
        ?>
        <div class="cr-dashboard-frontend">
            <div class="cr-welcome">
                <h2><?php printf(__('Welcome, %s!', CR_TEXT_DOMAIN), wp_get_current_user()->display_name); ?></h2>
            </div>
            
            <div class="cr-tabs">
                <div class="cr-tab-nav">
                    <button class="cr-tab-link active" data-tab="available"><?php _e('Available Conferences', CR_TEXT_DOMAIN); ?></button>
                    <button class="cr-tab-link" data-tab="registrations"><?php _e('My Registrations', CR_TEXT_DOMAIN); ?></button>
                    <button class="cr-tab-link" data-tab="documents"><?php _e('My Documents', CR_TEXT_DOMAIN); ?></button>
                </div>
                
                <div class="cr-tab-content">
                    <div class="cr-tab-pane active" id="tab-available">
                        <?php $this->render_available_conferences(); ?>
                    </div>
                    
                    <div class="cr-tab-pane" id="tab-registrations">
                        <?php $this->render_user_registrations(); ?>
                    </div>
                    
                    <div class="cr-tab-pane" id="tab-documents">
                        <?php $this->render_user_documents(); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
            .cr-dashboard-frontend {
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
            }
            .cr-welcome {
                margin-bottom: 30px;
            }
            .cr-tab-nav {
                display: flex;
                gap: 10px;
                border-bottom: 2px solid #ddd;
                margin-bottom: 20px;
            }
            .cr-tab-link {
                padding: 10px 20px;
                background: none;
                border: none;
                cursor: pointer;
                font-size: 16px;
                color: #666;
                position: relative;
            }
            .cr-tab-link.active {
                color: #007cba;
                font-weight: bold;
            }
            .cr-tab-link.active:after {
                content: '';
                position: absolute;
                bottom: -2px;
                left: 0;
                right: 0;
                height: 2px;
                background: #007cba;
            }
            .cr-tab-pane {
                display: none;
            }
            .cr-tab-pane.active {
                display: block;
            }
            .cr-conference-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 20px;
            }
            .cr-conference-card {
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 20px;
                background: #fff;
            }
            .cr-conference-card h3 {
                margin: 0 0 10px;
            }
            .cr-conference-meta {
                margin: 10px 0;
                color: #666;
            }
            .cr-btn-register {
                display: inline-block;
                padding: 10px 20px;
                background: #007cba;
                color: #fff;
                text-decoration: none;
                border-radius: 4px;
                margin-top: 10px;
            }
            .cr-btn-register:hover {
                background: #005a87;
                color: #fff;
            }
            .cr-bookings-table {
                width: 100%;
                border-collapse: collapse;
            }
            .cr-bookings-table th,
            .cr-bookings-table td {
                padding: 12px;
                text-align: left;
                border-bottom: 1px solid #ddd;
            }
            .cr-bookings-table th {
                background: #f5f5f5;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('.cr-tab-link').on('click', function() {
                var tabId = $(this).data('tab');
                
                $('.cr-tab-link').removeClass('active');
                $(this).addClass('active');
                
                $('.cr-tab-pane').removeClass('active');
                $('#tab-' + tabId).addClass('active');
            });
        });
        </script>
        <?php
        
        return ob_get_clean();
    }
    
    private function render_available_conferences() {
        $args = array(
            'post_type' => 'congress',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => 'registration_deadline',
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE'
                )
            )
        );
        
        $conferences = new WP_Query($args);
        
        if ($conferences->have_posts()) {
            echo '<div class="cr-conference-grid">';
            
            while ($conferences->have_posts()) {
                $conferences->the_post();
                
                $start_date = get_post_meta(get_the_ID(), 'start_date', true);
                $end_date = get_post_meta(get_the_ID(), 'end_date', true);
                $location = get_post_meta(get_the_ID(), 'location', true);
                
                ?>
                <div class="cr-conference-card">
                    <h3><?php the_title(); ?></h3>
                    
                    <?php if ($start_date && $end_date): ?>
                    <div class="cr-conference-meta">
                        <span class="cr-date">📅 <?php echo date_i18n('j F Y', strtotime($start_date)) . ' - ' . date_i18n('j F Y', strtotime($end_date)); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($location): ?>
                    <div class="cr-conference-meta">
                        <span class="cr-location">📍 <?php echo esc_html($location); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="cr-conference-status">
                        <span class="cr-status-badge"><?php _e('Open', CR_TEXT_DOMAIN); ?></span>
                    </div>
                    
                    <a href="<?php echo add_query_arg('congress_id', get_the_ID(), get_permalink(get_page_by_path('congress-registration'))); ?>" class="cr-btn-register">
                        <?php _e('Register Now →', CR_TEXT_DOMAIN); ?>
                    </a>
                </div>
                <?php
            }
            
            echo '</div>';
            wp_reset_postdata();
        } else {
            echo '<p>' . __('No conferences available for registration.', CR_TEXT_DOMAIN) . '</p>';
        }
    }
    
    private function render_user_registrations() {
        global $wpdb;
        $user_id = get_current_user_id();
        $table_name = $wpdb->prefix . 'cr_bookings';
        
        $bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC",
            $user_id
        ));
        
        if ($bookings) {
            echo '<table class="cr-bookings-table">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>' . __('Booking #', CR_TEXT_DOMAIN) . '</th>';
            echo '<th>' . __('Conference', CR_TEXT_DOMAIN) . '</th>';
            echo '<th>' . __('Type', CR_TEXT_DOMAIN) . '</th>';
            echo '<th>' . __('Amount', CR_TEXT_DOMAIN) . '</th>';
            echo '<th>' . __('Status', CR_TEXT_DOMAIN) . '</th>';
            echo '<th>' . __('Date', CR_TEXT_DOMAIN) . '</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            
            foreach ($bookings as $booking) {
                $congress_title = get_the_title($booking->congress_id);
                $status_class = 'cr-status-' . $booking->booking_status;
                
                echo '<tr>';
                echo '<td>' . esc_html($booking->booking_number) . '</td>';
                echo '<td>' . esc_html($congress_title) . '</td>';
                echo '<td>' . esc_html($booking->registration_type) . '</td>';
                echo '<td>' . wc_price($booking->total_amount) . '</td>';
                echo '<td><span class="cr-status ' . $status_class . '">' . esc_html($booking->booking_status) . '</span></td>';
                echo '<td>' . date_i18n(get_option('date_format'), strtotime($booking->created_at)) . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
        } else {
            echo '<p>' . __('You have no registrations yet.', CR_TEXT_DOMAIN) . '</p>';
        }
    }
    
    private function render_user_documents() {
        global $wpdb;
        $user_id = get_current_user_id();
        
        $documents = $wpdb->get_results($wpdb->prepare(
            "SELECT d.*, b.booking_number, b.congress_id 
            FROM {$wpdb->prefix}cr_documents d
            INNER JOIN {$wpdb->prefix}cr_bookings b ON d.booking_id = b.id
            WHERE b.user_id = %d
            ORDER BY d.created_at DESC",
            $user_id
        ));
        
        if ($documents) {
            echo '<div class="cr-documents-list">';
            
            foreach ($documents as $document) {
                $congress_title = get_the_title($document->congress_id);
                ?>
                <div class="cr-document-item">
                    <div class="cr-document-icon">📄</div>
                    <div class="cr-document-info">
                        <h4><?php echo esc_html($congress_title); ?></h4>
                        <p><?php echo sprintf(__('Booking: %s', CR_TEXT_DOMAIN), $document->booking_number); ?></p>
                        <p><?php echo __('Document Type: ', CR_TEXT_DOMAIN) . ucfirst($document->document_type); ?></p>
                        <a href="<?php echo esc_url($document->document_url); ?>" class="cr-btn-download" target="_blank">
                            <?php _e('Download', CR_TEXT_DOMAIN); ?>
                        </a>
                    </div>
                </div>
                <?php
            }
            
            echo '</div>';
            
            // Add some basic styling
            echo '<style>
                .cr-documents-list {
                    display: grid;
                    gap: 15px;
                }
                .cr-document-item {
                    display: flex;
                    gap: 20px;
                    padding: 15px;
                    border: 1px solid #ddd;
                    border-radius: 8px;
                    background: #fff;
                }
                .cr-document-icon {
                    font-size: 40px;
                }
                .cr-document-info h4 {
                    margin: 0 0 5px;
                }
                .cr-document-info p {
                    margin: 5px 0;
                    color: #666;
                }
                .cr-btn-download {
                    display: inline-block;
                    padding: 5px 15px;
                    background: #007cba;
                    color: #fff;
                    text-decoration: none;
                    border-radius: 4px;
                    margin-top: 5px;
                }
            </style>';
        } else {
            echo '<p>' . __('No documents available yet.', CR_TEXT_DOMAIN) . '</p>';
        }
    }
    
    public function render_registration_form($atts) {
        if (!is_user_logged_in()) {
            return $this->get_login_message();
        }
        
        $congress_id = isset($_GET['congress_id']) ? intval($_GET['congress_id']) : 0;
        $step = isset($_GET['step']) ? intval($_GET['step']) : 1;
        
        ob_start();
        ?>
        <div class="cr-registration-wrapper">
            <div class="cr-registration-header">
                <h2><?php _e('Congress Registration', CR_TEXT_DOMAIN); ?></h2>
                
                <?php if ($congress_id): ?>
                    <?php $congress = get_post($congress_id); ?>
                    <?php if ($congress): ?>
                        <div class="cr-congress-info">
                            <h3><?php echo get_the_title($congress_id); ?></h3>
                            <?php
                            $start_date = get_post_meta($congress_id, 'start_date', true);
                            $end_date = get_post_meta($congress_id, 'end_date', true);
                            $location = get_post_meta($congress_id, 'location', true);
                            ?>
                            <p>
                                <?php echo date_i18n('F j, Y', strtotime($start_date)) . ' - ' . date_i18n('F j, Y', strtotime($end_date)); ?><br>
                                <?php echo esc_html($location); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <div class="cr-step-indicator">
                    <?php
                    $steps = array(
                        1 => __('Start', CR_TEXT_DOMAIN),
                        2 => __('Data', CR_TEXT_DOMAIN),
                        3 => __('Type', CR_TEXT_DOMAIN),
                        4 => __('Hotel', CR_TEXT_DOMAIN),
                        5 => __('Food', CR_TEXT_DOMAIN),
                        6 => __('Workshops', CR_TEXT_DOMAIN),
                        7 => __('Others', CR_TEXT_DOMAIN),
                        8 => __('Summary', CR_TEXT_DOMAIN)
                    );
                    
                    foreach ($steps as $step_num => $step_name) {
                        $class = 'cr-step';
                        if ($step_num == $step) {
                            $class .= ' active';
                        } elseif ($step_num < $step) {
                            $class .= ' confirmed';
                        }
                        
                        echo '<div class="' . $class . '">';
                        echo '<span class="cr-step-number">' . $step_num . '</span>';
                        echo '<span class="cr-step-name">' . $step_name . '</span>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
            
            <div class="cr-registration-form">
                <form id="cr-registration-form" method="post" action="">
                    <?php wp_nonce_field('cr_registration_nonce'); ?>
                    <input type="hidden" name="congress_id" value="<?php echo $congress_id; ?>">
                    <input type="hidden" name="step" value="<?php echo $step; ?>">
                    
                    <?php
                    switch ($step) {
                        case 1:
                            $this->render_step_1();
                            break;
                        case 2:
                            $this->render_step_2($congress_id);
                            break;
                        // Add more steps as needed
                        case 8:
                            $this->render_step_8($congress_id);
                            break;
                        default:
                            $this->render_step_1();
                    }
                    ?>
                </form>
            </div>
        </div>
        
        <style>
            .cr-registration-wrapper {
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
            }
            .cr-step-indicator {
                display: flex;
                justify-content: space-between;
                margin: 30px 0;
                position: relative;
            }
            .cr-step-indicator:before {
                content: '';
                position: absolute;
                top: 20px;
                left: 0;
                right: 0;
                height: 2px;
                background: #ddd;
                z-index: 1;
            }
            .cr-step {
                position: relative;
                z-index: 2;
                background: #fff;
                padding: 0 10px;
                text-align: center;
            }
            .cr-step-number {
                display: block;
                width: 40px;
                height: 40px;
                line-height: 40px;
                background: #ddd;
                color: #666;
                border-radius: 50%;
                margin: 0 auto 5px;
            }
            .cr-step.active .cr-step-number {
                background: #007cba;
                color: #fff;
            }
            .cr-step.confirmed .cr-step-number {
                background: #46b450;
                color: #fff;
            }
            .cr-step-name {
                font-size: 12px;
                color: #666;
            }
            .cr-registration-options {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
                margin: 30px 0;
            }
            .cr-option-card {
                border: 2px solid #ddd;
                border-radius: 8px;
                padding: 30px;
                text-align: center;
                cursor: pointer;
                transition: all 0.3s;
            }
            .cr-option-card:hover {
                border-color: #007cba;
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            }
            .cr-option-card.selected {
                border-color: #007cba;
                background: #f0f8ff;
            }
            .cr-option-icon {
                font-size: 48px;
                margin-bottom: 15px;
            }
            .cr-option-title {
                font-size: 18px;
                font-weight: bold;
                margin-bottom: 10px;
            }
            .cr-option-desc {
                color: #666;
            }
            .cr-form-actions {
                margin-top: 30px;
                text-align: right;
            }
            .cr-btn {
                padding: 10px 30px;
                font-size: 16px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
            }
            .cr-btn-primary {
                background: #007cba;
                color: #fff;
            }
            .cr-btn-primary:hover {
                background: #005a87;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('.cr-option-card').on('click', function() {
                $('.cr-option-card').removeClass('selected');
                $(this).addClass('selected');
                $('input[name="registration_type"]').val($(this).data('type'));
            });
        });
        </script>
        <?php
        
        return ob_get_clean();
    }
    
    private function render_step_1() {
        ?>
        <div class="cr-step-content">
            <h3><?php _e('Who do you want to register?', CR_TEXT_DOMAIN); ?></h3>
            <p><?php _e('Please select whether the registration is for you or for someone else.', CR_TEXT_DOMAIN); ?></p>
            
            <div class="cr-registration-options">
                <div class="cr-option-card" data-type="personal">
                    <div class="cr-option-icon">👤</div>
                    <div class="cr-option-title"><?php _e('Personally', CR_TEXT_DOMAIN); ?></div>
                    <div class="cr-option-desc"><?php _e('Registration will be done using my account details', CR_TEXT_DOMAIN); ?></div>
                </div>
                
                <div class="cr-option-card" data-type="third_person">
                    <div class="cr-option-icon">👥</div>
                    <div class="cr-option-title"><?php _e('To a third person', CR_TEXT_DOMAIN); ?></div>
                    <div class="cr-option-desc"><?php _e('I will register someone else in my name', CR_TEXT_DOMAIN); ?></div>
                </div>
            </div>
            
            <input type="hidden" name="registration_type" id="registration_type" value="">
            
            <div class="cr-form-actions">
                <button type="submit" name="next_step" class="cr-btn cr-btn-primary">
                    <?php _e('Start Registration →', CR_TEXT_DOMAIN); ?>
                </button>
            </div>
        </div>
        <?php
    }
    
    private function render_step_2($congress_id) {
        $registration_type = isset($_POST['registration_type']) ? sanitize_text_field($_POST['registration_type']) : 'personal';
        $user = wp_get_current_user();
        ?>
        <div class="cr-step-content">
            <h3><?php _e('Registration Data', CR_TEXT_DOMAIN); ?></h3>
            
            <?php if ($registration_type == 'third_person'): ?>
                <div class="cr-third-person-form">
                    <p>
                        <label><?php _e('Full Name', CR_TEXT_DOMAIN); ?> *</label>
                        <input type="text" name="third_person_name" required class="widefat">
                    </p>
                    <p>
                        <label><?php _e('Email Address', CR_TEXT_DOMAIN); ?> *</label>
                        <input type="email" name="third_person_email" required class="widefat">
                    </p>
                    <p>
                        <label><?php _e('Phone Number', CR_TEXT_DOMAIN); ?></label>
                        <input type="text" name="third_person_phone" class="widefat">
                    </p>
                </div>
            <?php else: ?>
                <div class="cr-personal-data">
                    <p><?php _e('You are registering with your account details:', CR_TEXT_DOMAIN); ?></p>
                    <p><strong><?php _e('Name:', CR_TEXT_DOMAIN); ?></strong> <?php echo $user->display_name; ?></p>
                    <p><strong><?php _e('Email:', CR_TEXT_DOMAIN); ?></strong> <?php echo $user->user_email; ?></p>
                </div>
            <?php endif; ?>
            
            <div class="cr-form-actions">
                <button type="submit" name="previous_step" class="cr-btn">← <?php _e('Back', CR_TEXT_DOMAIN); ?></button>
                <button type="submit" name="next_step" class="cr-btn cr-btn-primary">
                    <?php _e('Next →', CR_TEXT_DOMAIN); ?>
                </button>
            </div>
        </div>
        <?php
    }
    
    private function render_step_8($congress_id) {
        global $wpdb;
        $user_id = get_current_user_id();
        
        // Generate booking number
        $booking_number = 'CR-' . time() . '-' . $user_id;
        
        // Calculate total amount (you would calculate this based on selections)
        $total_amount = $this->calculate_total_amount($congress_id);
        
        // Insert booking
        $table_name = $wpdb->prefix . 'cr_bookings';
        
        $wpdb->insert(
            $table_name,
            array(
                'booking_number' => $booking_number,
                'user_id' => $user_id,
                'congress_id' => $congress_id,
                'registration_type' => isset($_POST['registration_type']) ? sanitize_text_field($_POST['registration_type']) : 'personal',
                'third_person_name' => isset($_POST['third_person_name']) ? sanitize_text_field($_POST['third_person_name']) : '',
                'third_person_email' => isset($_POST['third_person_email']) ? sanitize_email($_POST['third_person_email']) : '',
                'selected_hotel_id' => isset($_POST['hotel_id']) ? intval($_POST['hotel_id']) : 0,
                'meal_preferences' => isset($_POST['meal_preferences']) ? wp_json_encode($_POST['meal_preferences']) : '',
                'workshop_ids' => isset($_POST['workshops']) ? wp_json_encode($_POST['workshops']) : '',
                'total_amount' => $total_amount,
                'booking_status' => 'pending',
                'payment_status' => 'pending'
            ),
            array('%s', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%f', '%s', '%s')
        );
        
        $booking_id = $wpdb->insert_id;
        
        if (get_option('cr_enable_woocommerce', 1) && class_exists('WooCommerce')) {
            // Create WooCommerce order
            $order = wc_create_order();
            
            // Add product (you might want to create a virtual product for congress registration)
            $product_id = $this->get_or_create_congress_product($congress_id);
            $order->add_product(wc_get_product($product_id), 1, array(
                'subtotal' => $total_amount,
                'total' => $total_amount
            ));
            
            // Set customer
            $order->set_customer_id($user_id);
            
            // Calculate totals
            $order->calculate_totals();
            
            // Save order
            $order->save();
            
            // Update booking with order ID
            $wpdb->update(
                $table_name,
                array('woocommerce_order_id' => $order->get_id()),
                array('id' => $booking_id),
                array('%d'),
                array('%d')
            );
            
            // Redirect to checkout
            wp_redirect($order->get_checkout_payment_url());
            exit;
        }
        
        // If WooCommerce not enabled, just show success message
        ?>
        <div class="cr-step-content">
            <h3><?php _e('Registration Summary', CR_TEXT_DOMAIN); ?></h3>
            
            <div class="cr-booking-success">
                <div class="cr-success-icon">✅</div>
                <h4><?php _e('Registration Submitted Successfully!', CR_TEXT_DOMAIN); ?></h4>
                <p><?php _e('Your booking number is:', CR_TEXT_DOMAIN); ?> <strong><?php echo $booking_number; ?></strong></p>
                <p><?php _e('Total Amount:', CR_TEXT_DOMAIN); ?> <strong><?php echo wc_price($total_amount); ?></strong></p>
                <p><?php _e('Please complete your payment to confirm the registration.', CR_TEXT_DOMAIN); ?></p>
                
                <div class="cr-payment-options">
                    <h4><?php _e('Payment Options', CR_TEXT_DOMAIN); ?></h4>
                    <p><?php _e('Bank Transfer Details:', CR_TEXT_DOMAIN); ?></p>
                    <p>
                        <?php _e('Account Name: Your Company Name', CR_TEXT_DOMAIN); ?><br>
                        <?php _e('Account Number: 123456789', CR_TEXT_DOMAIN); ?><br>
                        <?php _e('Bank: Your Bank Name', CR_TEXT_DOMAIN); ?><br>
                        <?php _e('SWIFT: XXXXXX', CR_TEXT_DOMAIN); ?>
                    </p>
                </div>
                
                <a href="<?php echo get_permalink(get_page_by_path('congress-dashboard')); ?>" class="cr-btn cr-btn-primary">
                    <?php _e('Go to Dashboard', CR_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>
        
        <style>
            .cr-booking-success {
                text-align: center;
                padding: 40px 20px;
            }
            .cr-success-icon {
                font-size: 64px;
                margin-bottom: 20px;
            }
            .cr-payment-options {
                margin: 30px 0;
                padding: 20px;
                background: #f5f5f5;
                border-radius: 8px;
                text-align: left;
            }
        </style>
        <?php
    }
    
    public function render_congress_list($atts) {
        $atts = shortcode_atts(array(
            'limit' => -1,
            'show_past' => 'no'
        ), $atts);
        
        $args = array(
            'post_type' => 'congress',
            'posts_per_page' => $atts['limit'],
            'post_status' => 'publish'
        );
        
        if ($atts['show_past'] == 'no') {
            $args['meta_query'] = array(
                array(
                    'key' => 'end_date',
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE'
                )
            );
        }
        
        $conferences = new WP_Query($args);
        
        ob_start();
        
        if ($conferences->have_posts()) {
            echo '<div class="cr-conference-list">';
            
            while ($conferences->have_posts()) {
                $conferences->the_post();
                include CR_PLUGIN_DIR . 'templates/congress-card.php';
            }
            
            echo '</div>';
            wp_reset_postdata();
        } else {
            echo '<p>' . __('No conferences found.', CR_TEXT_DOMAIN) . '</p>';
        }
        
        return ob_get_clean();
    }
    
    public function render_user_bookings($atts) {
        if (!is_user_logged_in()) {
            return $this->get_login_message();
        }
        
        global $wpdb;
        $user_id = get_current_user_id();
        $table_name = $wpdb->prefix . 'cr_bookings';
        
        $bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC",
            $user_id
        ));
        
        ob_start();
        
        if ($bookings) {
            echo '<div class="cr-user-bookings">';
            
            foreach ($bookings as $booking) {
                $congress_title = get_the_title($booking->congress_id);
                ?>
                <div class="cr-booking-card">
                    <div class="cr-booking-header">
                        <h3><?php echo esc_html($congress_title); ?></h3>
                        <span class="cr-status cr-status-<?php echo esc_attr($booking->booking_status); ?>">
                            <?php echo esc_html($booking->booking_status); ?>
                        </span>
                    </div>
                    
                    <div class="cr-booking-details">
                        <p><strong><?php _e('Booking #:', CR_TEXT_DOMAIN); ?></strong> <?php echo esc_html($booking->booking_number); ?></p>
                        <p><strong><?php _e('Type:', CR_TEXT_DOMAIN); ?></strong> <?php echo esc_html($booking->registration_type); ?></p>
                        <p><strong><?php _e('Amount:', CR_TEXT_DOMAIN); ?></strong> <?php echo wc_price($booking->total_amount); ?></p>
                        <p><strong><?php _e('Date:', CR_TEXT_DOMAIN); ?></strong> <?php echo date_i18n(get_option('date_format'), strtotime($booking->created_at)); ?></p>
                    </div>
                    
                    <?php if ($booking->booking_status == 'pending'): ?>
                        <div class="cr-booking-actions">
                            <a href="<?php echo get_permalink(get_page_by_path('congress-registration')) . '?booking_id=' . $booking->id; ?>" class="cr-btn">
                                <?php _e('Complete Payment', CR_TEXT_DOMAIN); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                <?php
            }
            
            echo '</div>';
            
            // Add styling
            echo '<style>
                .cr-user-bookings {
                    display: grid;
                    gap: 20px;
                }
                .cr-booking-card {
                    border: 1px solid #ddd;
                    border-radius: 8px;
                    padding: 20px;
                    background: #fff;
                }
                .cr-booking-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 15px;
                    padding-bottom: 10px;
                    border-bottom: 1px solid #eee;
                }
                .cr-booking-header h3 {
                    margin: 0;
                }
            </style>';
        } else {
            echo '<p>' . __('You have no bookings yet.', CR_TEXT_DOMAIN) . '</p>';
        }
        
        return ob_get_clean();
    }
    
    private function get_login_message() {
        $login_url = wp_login_url(get_permalink());
        return '<p>' . sprintf(__('Please <a href="%s">log in</a> to view this content.', CR_TEXT_DOMAIN), $login_url) . '</p>';
    }
    
    private function calculate_total_amount($congress_id) {
        // You would implement your pricing logic here
        // For now, return a default price
        $base_price = get_post_meta($congress_id, 'base_price', true);
        return $base_price ? floatval($base_price) : 100;
    }
    
    private function get_or_create_congress_product($congress_id) {
        // Check if product exists for this congress
        $product_id = get_post_meta($congress_id, '_cr_product_id', true);
        
        if ($product_id && get_post_status($product_id)) {
            return $product_id;
        }
        
        // Create new product
        $product = new WC_Product_Simple();
        $product->set_name(get_the_title($congress_id) . ' Registration');
        $product->set_status('publish');
        $product->set_catalog_visibility('hidden');
        $product->set_price($this->calculate_total_amount($congress_id));
        $product->set_regular_price($this->calculate_total_amount($congress_id));
        $product->set_virtual(true);
        $product->set_downloadable(true);
        
        $product_id = $product->save();
        
        // Save product ID to congress meta
        update_post_meta($congress_id, '_cr_product_id', $product_id);
        
        return $product_id;
    }
}