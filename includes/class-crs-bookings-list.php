<?php
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class CRS_Bookings_List_Table extends WP_List_Table {
    
    public function __construct() {
        parent::__construct([
            'singular' => 'booking',
            'plural'   => 'bookings',
            'ajax'     => false
        ]);
    }
    
    public function get_columns() {
        return [
            'cb'              => '<input type="checkbox" />',
            'booking_number'  => 'Booking #',
            'user'            => 'User',
            'congress'        => 'Congress',
            'total_amount'    => 'Amount',
            'booking_status'  => 'Status',
            'actions'         => 'Actions'
        ];
    }
    
    public function get_sortable_columns() {
        return [
            'booking_number' => ['booking_number', false],
            'total_amount'   => ['total_amount', false],
            'created_at'     => ['created_at', true],
            'booking_status' => ['booking_status', false]
        ];
    }
    
    public function get_bulk_actions() {
        return [
            'delete'          => 'Delete Permanently',
            'mark_completed'  => 'Mark as Confirmed',
            'mark_pending'    => 'Mark as Pending',
            'mark_cancelled'  => 'Mark as Cancelled'
        ];
    }
    
    public function prepare_items() {
        global $wpdb;
        
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $table_name = $wpdb->prefix . 'cr_bookings';
        
        // Handle bulk actions
        $this->process_bulk_action();
        
        // Get search query
        $search = isset($_REQUEST['s']) ? wp_unslash(trim($_REQUEST['s'])) : '';
        
        // Build query
        $where = 'WHERE 1=1';
        if (!empty($search)) {
            $where .= $wpdb->prepare(
                " AND (booking_number LIKE '%%%s%%' OR user_id IN (SELECT ID FROM {$wpdb->users} WHERE user_login LIKE '%%%s%%' OR display_name LIKE '%%%s%%' OR user_email LIKE '%%%s%%'))",
                $search, $search, $search, $search
            );
        }
        
        // Get total items
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name $where");
        
        // Get items for current page
        $orderby = isset($_REQUEST['orderby']) ? sanitize_sql_orderby($_REQUEST['orderby']) : 'created_at';
        $order = isset($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'DESC';
        
        $offset = ($current_page - 1) * $per_page;
        
        $this->items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name $where ORDER BY $orderby $order LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );
        
        // Set pagination args
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);
        
        // Set column headers
        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];
    }
    
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'booking_number':
                $view_url = '?page=' . $_REQUEST['page'] . '&action=view&id=' . $item->id;
                return '<strong><a href="' . $view_url . '" style="text-decoration: none; color: #2271b1; font-weight: 600;">' . 
                    esc_html($item->booking_number) . '</a></strong>';
                
            case 'user':
                $user = get_userdata($item->user_id);
                if ($user) {
                    $display_name = esc_html($user->display_name);
                    $user_email = esc_html($user->user_email);
                    $user_edit_link = get_edit_user_link($item->user_id);
                    return '<a href="' . $user_edit_link . '">' . $display_name . '</a><br><small>' . $user_email . '</small>';
                }
                return 'N/A';
                
            case 'congress':
                $title = get_the_title($item->congress_id);
                $edit_link = get_edit_post_link($item->congress_id);
                return $edit_link ? '<a href="' . $edit_link . '">' . esc_html($title) . '</a>' : esc_html($title);
                
            case 'total_amount':
                return wc_price($item->total_amount);
                
            case 'booking_status':
                $status_colors = [
                    'pending' => '#f39c12',
                    'confirmed' => '#27ae60',  // Green color for confirmed
                    'cancelled' => '#e74c3c'
                ];
                $color = isset($status_colors[$item->booking_status]) ? $status_colors[$item->booking_status] : '#95a5a6';
                return '<span style="background-color: ' . $color . '; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500;">' . esc_html(ucfirst($item->booking_status)) . '</span>';
                
            case 'actions':
                $actions = [];
                $actions[] = '<a href="?page=' . $_REQUEST['page'] . '&action=view&id=' . $item->id . '" class="button button-primary" style="margin-right: 5px;">View Details</a>';
                
                if ($item->woocommerce_order_id) {
                    $order = wc_get_order($item->woocommerce_order_id);
                    if ($order) {
                        $order_edit_url = $order->get_edit_order_url();
                        $actions[] = '<a href="' . $order_edit_url . '" class="button button-secondary" target="_blank" style="margin-right: 5px;">Order #' . $item->woocommerce_order_id . '</a>';
                    }
                }
                
                // Add CSS for action column width
                echo '<style>
                    .column-actions {
                        width: 250px !important;
                        min-width: 250px !important;
                    }
                </style>';
                
                return implode(' ', $actions);
                
            default:
                return print_r($item, true);
        }
    }
    
    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="booking[]" value="%s" />',
            $item->id
        );
    }
    
    public function process_bulk_action() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cr_bookings';
        
        // Handle single actions from URL
        if (isset($_GET['action']) && isset($_GET['id'])) {
            $action = $_GET['action'];
            $id = intval($_GET['id']);
            
            switch ($action) {
                case 'view':
                    return;
                    
                case 'edit':
                    return;
                    
                case 'delete':
                    if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_booking_' . $id)) {
                        $wpdb->delete($table_name, ['id' => $id]);
                        echo '<div class="notice notice-success"><p>Booking deleted successfully.</p></div>';
                    }
                    break;
                    
                case 'mark_confirmed':
                    if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'mark_confirmed_' . $id)) {
                        $wpdb->update($table_name, ['booking_status' => 'confirmed'], ['id' => $id]);
                        echo '<div class="notice notice-success"><p>Booking marked as confirmed.</p></div>';
                    }
                    break;
                    
                case 'mark_pending':
                    if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'mark_pending_' . $id)) {
                        $wpdb->update($table_name, ['booking_status' => 'pending'], ['id' => $id]);
                        echo '<div class="notice notice-success"><p>Booking marked as pending.</p></div>';
                    }
                    break;
                    
                case 'mark_cancelled':
                    if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'mark_cancelled_' . $id)) {
                        $wpdb->update($table_name, ['booking_status' => 'cancelled'], ['id' => $id]);
                        echo '<div class="notice notice-success"><p>Booking marked as cancelled.</p></div>';
                    }
                    break;
            }
        }
        
        // Handle bulk actions
        if ('delete' === $this->current_action()) {
            $bookings = isset($_REQUEST['booking']) ? $_REQUEST['booking'] : [];
            if (!empty($bookings)) {
                $ids = implode(',', array_map('intval', $bookings));
                $wpdb->query("DELETE FROM $table_name WHERE id IN ($ids)");
                echo '<div class="notice notice-success"><p>Selected bookings deleted permanently.</p></div>';
            }
        }
        
        if ('mark_completed' === $this->current_action()) {
            $bookings = isset($_REQUEST['booking']) ? $_REQUEST['booking'] : [];
            if (!empty($bookings)) {
                foreach ($bookings as $booking_id) {
                    $wpdb->update($table_name, ['booking_status' => 'confirmed'], ['id' => $booking_id]);
                }
                echo '<div class="notice notice-success"><p>Selected bookings marked as confirmed.</p></div>';
            }
        }
        
        if ('mark_pending' === $this->current_action()) {
            $bookings = isset($_REQUEST['booking']) ? $_REQUEST['booking'] : [];
            if (!empty($bookings)) {
                foreach ($bookings as $booking_id) {
                    $wpdb->update($table_name, ['booking_status' => 'pending'], ['id' => $booking_id]);
                }
                echo '<div class="notice notice-success"><p>Selected bookings marked as pending.</p></div>';
            }
        }
        
        if ('mark_cancelled' === $this->current_action()) {
            $bookings = isset($_REQUEST['booking']) ? $_REQUEST['booking'] : [];
            if (!empty($bookings)) {
                foreach ($bookings as $booking_id) {
                    $wpdb->update($table_name, ['booking_status' => 'cancelled'], ['id' => $booking_id]);
                }
                echo '<div class="notice notice-success"><p>Selected bookings marked as cancelled.</p></div>';
            }
        }
    }
    
    public function display_view_page() {
        if (!isset($_GET['id'])) {
            return;
        }
        
        $id = intval($_GET['id']);
        global $wpdb;
        $table_name = $wpdb->prefix . 'cr_bookings';
        $booking = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
                        
        // var_dump($booking);
        if (!$booking) {
            echo '<div class="notice notice-error"><p>Booking not found.</p></div>';
            return;
        }


        
        // Get additional data
        $additional = json_decode($booking->additional_options, true);
        $user = get_userdata($booking->user_id);
        $congress = get_post($booking->congress_id);
        
        // Get registration type
        $registration_type_name = '';
        $registration_type_price = 0;
        if (!empty($additional['registration_type_id'])) {
            global $wpdb;
            $type = $wpdb->get_row($wpdb->prepare(
                "SELECT name, price FROM {$wpdb->prefix}registration_types WHERE id = %d",
                $additional['registration_type_id']
            ));
            if ($type) {
                $registration_type_name = $type->name;
                $registration_type_price = $type->price;
            }
        }
        
        // Get hotel
        $hotel_name = '';
        $price_per_night = 0;
        if ($booking->selected_hotel_id) {
            $hotel = get_post($booking->selected_hotel_id);
            if ($hotel) {
                $hotel_name = $hotel->post_title;
                $price_per_night = get_post_meta($booking->selected_hotel_id, 'price_per_night', true);
            }
        }
        
        // Calculate nights
        $nights = 0;
        if ($booking->check_in_date && $booking->check_out_date) {
            $nights = ceil((strtotime($booking->check_out_date) - strtotime($booking->check_in_date)) / (60 * 60 * 24));
        }
        $hotel_total = $price_per_night * $nights;
        
        // Get meals
        $meals_list = [];
        $meals_total = 0;
        if ($booking->meal_preferences) {
            $meal_indices = json_decode($booking->meal_preferences, true);
            $meals = get_post_meta($booking->congress_id, 'congress_meals', true);
            if (is_array($meal_indices) && is_array($meals)) {
                foreach ($meal_indices as $index) {
                    if (isset($meals[$index])) {
                        $meals_list[] = $meals[$index];
                        $meals_total += floatval($meals[$index]['meal_price']);
                    }
                }
            }
        }
        
        // Get workshops - FIXED
        $workshops_list = [];
        if ($booking->workshop_ids) {
            $workshop_indices = json_decode($booking->workshop_ids, true);
            $workshops = get_post_meta($booking->congress_id, 'congress_workshop', true);
            
            if (is_array($workshop_indices) && is_array($workshops)) {
                foreach ($workshop_indices as $index) {
                    // Handle both string and numeric indices
                    $search_index = $index;
                    if (is_string($index) && strpos($index, 'item-') === 0) {
                        $search_index = $index;
                    }
                    
                    if (isset($workshops[$search_index])) {
                        $workshops_list[] = $workshops[$search_index];
                    }
                }
            }
        }
        
        // Get SIDI info
        $add_sidi = isset($additional['add_sidi']) ? $additional['add_sidi'] : 0;
        
        // Get invoice request
        $invoice_request = isset($additional['invoice_request']) ? $additional['invoice_request'] : 0;
        $company_details = isset($additional['company_details']) ? $additional['company_details'] : [];
        
        // Get proof file
        $proof_file_id = isset($additional['proof_file_id']) ? $additional['proof_file_id'] : 0;
        $proof_file_url = $proof_file_id ? wp_get_attachment_url($proof_file_id) : '';
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Booking Details: <?php echo esc_html($booking->booking_number); ?></h1>
            <a href="?page=<?php echo $_REQUEST['page']; ?>" class="page-title-action">Back to Bookings</a>
            <hr class="wp-header-end">
            
            <div style="margin-top: 20px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                <!-- Left Column -->
                <div>
                    <!-- Booking Info Card -->
                    <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                        <h2 style="margin-top: 0; padding-bottom: 10px; border-bottom: 2px solid #2271b1; display: flex; align-items: center;">
                            <span class="dashicons dashicons-calendar-alt" style="margin-right: 10px;"></span>
                            Booking Information
                        </h2>
                        
                        <table class="widefat" style="border: none;">
                            <tr>
                                <td style="width: 150px; font-weight: 600;">Booking #:</td>
                                <td><strong><?php echo esc_html($booking->booking_number); ?></strong></td>
                            </tr>
                            <tr>
                                <td style="font-weight: 600;">Status:</td>
                                <td>
                                    <span style="background-color: <?php echo $booking->booking_status == 'confirmed' ? '#27ae60' : ($booking->booking_status == 'pending' ? '#f39c12' : '#e74c3c'); ?>; color: white; padding: 4px 10px; border-radius: 4px;">
                                        <?php echo esc_html(ucfirst($booking->booking_status)); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td style="font-weight: 600;">Payment:</td>
                                <td>
                                    <span style="background-color: <?php echo $booking->payment_status == 'confirmed' ? '#27ae60' : ($booking->payment_status == 'pending' ? '#f39c12' : '#e74c3c'); ?>; color: white; padding: 4px 10px; border-radius: 4px;">
                                        <?php echo esc_html(ucfirst($booking->payment_status)); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td style="font-weight: 600;">Total Amount:</td>
                                <td><strong><?php echo wc_price($booking->total_amount); ?></strong></td>
                            </tr>
                            <tr>
                                <td style="font-weight: 600;">Created:</td>
                                <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($booking->created_at)); ?></td>
                            </tr>
                        </table>
                        
                        <div style="margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
                            <select id="booking-status-change" data-id="<?php echo $booking->id; ?>" style="padding: 5px;">
                                <option value="">Change Status</option>
                                <option value="pending" <?php selected($booking->booking_status, 'pending'); ?>>Pending</option>
                                <option value="confirmed" <?php selected($booking->booking_status, 'confirmed'); ?>>Confirmed</option>
                                <option value="cancelled" <?php selected($booking->booking_status, 'cancelled'); ?>>Cancelled</option>
                            </select>
                            
                            <a href="<?php echo wp_nonce_url('?page=' . $_REQUEST['page'] . '&action=delete&id=' . $booking->id, 'delete_booking_' . $booking->id); ?>" class="button" onclick="return confirm('Are you sure?')">Delete Booking</a>
                            
                            <?php if ($booking->woocommerce_order_id): ?>
                                <a href="<?php echo get_edit_post_link($booking->woocommerce_order_id); ?>" class="button button-primary" target="_blank">View Order</a>
                            <?php endif; ?>
                            
                            <!-- 🔥 INVOICE SEND BUTTON -->
                            <button type="button" id="crs-send-invoice" class="button button-primary" data-booking-id="<?php echo $booking->id; ?>">
                                Send Invoice to Customer
                            </button>
                        </div>
                    </div>
                    
                    <!-- Personal Information Card -->
                    <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                        <h2 style="margin-top: 0; padding-bottom: 10px; border-bottom: 2px solid #2271b1; display: flex; align-items: center;">
                            <span class="dashicons dashicons-admin-users" style="margin-right: 10px;"></span>
                            Personal Information
                        </h2>
                        
                        <?php if (!empty($additional['personal_data'])): ?>
                            <table class="widefat" style="border: none;">
                                <tr>
                                    <td style="width: 150px; font-weight: 600;">Name:</td>
                                    <td><?php echo esc_html($additional['personal_data']['first_name'] . ' ' . $additional['personal_data']['last_name']); ?></td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 600;">ID/Passport:</td>
                                    <td><?php echo esc_html($additional['personal_data']['id_number']); ?></td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 600;">Email:</td>
                                    <td><a href="mailto:<?php echo esc_attr($additional['personal_data']['email']); ?>"><?php echo esc_html($additional['personal_data']['email']); ?></a></td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 600;">Phone:</td>
                                    <td><?php echo esc_html($additional['personal_data']['phone']); ?></td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 600;">Address:</td>
                                    <td><?php 
                                        echo esc_html(
                                            $additional['personal_data']['address'] . ', ' . 
                                            $additional['personal_data']['location'] . ', ' . 
                                            $additional['personal_data']['postal_code'] . ', ' . 
                                            $additional['personal_data']['country']
                                        ); 
                                    ?></td>
                                </tr>
                                <?php if (!empty($additional['personal_data']['work_center'])): ?>
                                <tr>
                                    <td style="font-weight: 600;">Work Center:</td>
                                    <td><?php echo esc_html($additional['personal_data']['work_center']); ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        <?php else: ?>
                            <p>No personal data available</p>
                        <?php endif; ?>
                        
                        <?php if (!empty($additional['third_person_name'])): ?>
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                            <strong>Registered For:</strong> <?php echo esc_html($additional['third_person_name']); ?> (Third Person)
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div>
                    <!-- Congress & Registration Card -->
                    <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                        <h2 style="margin-top: 0; padding-bottom: 10px; border-bottom: 2px solid #2271b1; display: flex; align-items: center;">
                            <span class="dashicons dashicons-calendar" style="margin-right: 10px;"></span>
                            Congress & Registration
                        </h2>
                        
                        <table class="widefat" style="border: none;">
                            <tr>
                                <td style="width: 150px; font-weight: 600;">Congress:</td>
                                <td><strong><?php echo esc_html(get_the_title($booking->congress_id)); ?></strong></td>
                            </tr>
                            <tr>
                                <td style="font-weight: 600;">Registration Type:</td>
                                <td><?php echo esc_html($registration_type_name); ?> (€<?php echo number_format($registration_type_price, 0); ?>)</td>
                            </tr>
                            <?php if ($add_sidi): ?>
                            <tr>
                                <td style="font-weight: 600;">+ SIDI 2026:</td>
                                <td>Yes (€150)</td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                    
                    <!-- Accommodation Card -->
                    <?php if ($booking->selected_hotel_id): ?>
                    <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                        <h2 style="margin-top: 0; padding-bottom: 10px; border-bottom: 2px solid #2271b1; display: flex; align-items: center;">
                            <span class="dashicons dashicons-building" style="margin-right: 10px;"></span>
                            Accommodation
                        </h2>
                        
                        <table class="widefat" style="border: none;">
                            <tr>
                                <td style="width: 150px; font-weight: 600;">Hotel:</td>
                                <td><strong><?php echo esc_html($hotel_name); ?></strong> (€<?php echo $price_per_night; ?>/night)</td>
                            </tr>
                            <tr>
                                <td style="font-weight: 600;">Check-in:</td>
                                <td><?php echo date_i18n('F j, Y', strtotime($booking->check_in_date)); ?></td>
                            </tr>
                            <tr>
                                <td style="font-weight: 600;">Check-out:</td>
                                <td><?php echo date_i18n('F j, Y', strtotime($booking->check_out_date)); ?></td>
                            </tr>
                            <tr>
                                <td style="font-weight: 600;">Nights:</td>
                                <td><?php echo $nights; ?></td>
                            </tr>
                            <tr>
                                <td style="font-weight: 600;">Total:</td>
                                <td><strong><?php echo wc_price($hotel_total); ?></strong></td>
                            </tr>
                        </table>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Meals Card -->
                    <?php if (!empty($meals_list)): ?>
                    <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                        <h2 style="margin-top: 0; padding-bottom: 10px; border-bottom: 2px solid #2271b1; display: flex; align-items: center;">
                            <span class="dashicons dashicons-food" style="margin-right: 10px;"></span>
                            Meals Selected
                        </h2>
                        
                        <ul style="margin: 0; padding-left: 20px;">
                            <?php foreach ($meals_list as $meal): ?>
                            <li>
                                <?php echo esc_html($meal['meal_title']); ?> 
                                (<?php echo isset($meal['meal_type']) && $meal['meal_type'] == 'Gala_Dinner' ? 'Gala Dinner' : 'Meal'; ?>) 
                                - €<?php echo $meal['meal_price']; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <p style="margin-top: 10px; font-weight: 600;">Total Meals: <?php echo wc_price($meals_total); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Workshops Card -->
                    <?php if (!empty($workshops_list)): ?>
                    <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                        <h2 style="margin-top: 0; padding-bottom: 10px; border-bottom: 2px solid #2271b1; display: flex; align-items: center;">
                            <span class="dashicons dashicons-hammer" style="margin-right: 10px;"></span>
                            Workshops Selected
                        </h2>
                        
                        <ul style="margin: 0; padding-left: 20px;">
                            <?php foreach ($workshops_list as $workshop): ?>
                            <li>
                                <strong><?php echo esc_html($workshop['workshop_title']); ?></strong>
                                <?php if (!empty($workshop['workshop_date'])): ?>
                                    <br><small>
                                        <?php 
                                        if (is_numeric($workshop['workshop_date'])) {
                                            echo date_i18n('F j, Y', $workshop['workshop_date']);
                                        } else {
                                            echo date_i18n('F j, Y', strtotime($workshop['workshop_date']));
                                        }
                                        ?>
                                        <?php if (!empty($workshop['start_time'])): ?>
                                            · <?php echo date('H:i', strtotime($workshop['start_time'])); ?>
                                            <?php if (!empty($workshop['end_time'])): ?>
                                                - <?php echo date('H:i', strtotime($workshop['end_time'])); ?>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </small>
                                <?php endif; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Dietary Information Card -->
                    <?php if (!empty($additional['dietary_info'])): ?>
                    <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                        <h2 style="margin-top: 0; padding-bottom: 10px; border-bottom: 2px solid #2271b1; display: flex; align-items: center;">
                            <span class="dashicons dashicons-carrot" style="margin-right: 10px;"></span>
                            Dietary Information
                        </h2>
                        
                        <?php 
                        $dietary = $additional['dietary_info'];
                        
                        // Debug
                        echo '<!-- Dietary data: ' . print_r($dietary, true) . ' -->';
                        
                        // Diet information
                        $diet_values = array();
                        if (!empty($dietary['diet'])) {
                            if (is_array($dietary['diet'])) {
                                $diet_values = $dietary['diet'];
                            } else {
                                $diet_values = array($dietary['diet']);
                            }
                        }
                        
                        if (!empty($diet_values)):
                            $display_diets = array();
                            $has_other = in_array('other', $diet_values);
                            
                            foreach ($diet_values as $diet) {
                                switch ($diet) {
                                    case 'vegetarian':
                                        $display_diets[] = 'Vegetarian';
                                        break;
                                    case 'vegan':
                                        $display_diets[] = 'Vegan';
                                        break;
                                    case 'other':
                                        $display_diets[] = 'Other';
                                        break;
                                    default:
                                        if ($diet !== 'no') {
                                            $display_diets[] = ucfirst($diet);
                                        }
                                }
                            }
                            
                            if (!empty($display_diets)):
                        ?>
                            <p><strong>Diet:</strong> <?php echo implode(', ', $display_diets); ?>
                            <?php if ($has_other && !empty($dietary['diet_other'])): ?>
                                <span style="color: #666; font-style: italic;"> (<?php echo esc_html($dietary['diet_other']); ?>)</span>
                            <?php endif; ?>
                            </p>
                        <?php 
                            endif;
                        endif; 
                        ?>
                        
                        <?php 
                        // Allergy information
                        $allergy_value = isset($dietary['allergy']) ? $dietary['allergy'] : '';
                        $allergy_details = isset($dietary['allergy_details']) ? $dietary['allergy_details'] : '';
                        
                        if ($allergy_value === 'yes'): 
                        ?>
                            <p><strong>Allergies:</strong> 
                                <span style="color: #e74c3c; font-weight: 500;">Yes</span>
                                <?php if (!empty($allergy_details)): ?>
                                    <span style="color: #666;"> - <?php echo esc_html($allergy_details); ?></span>
                                <?php endif; ?>
                            </p>
                        <?php elseif ($allergy_value === 'no'): ?>
                            <p><strong>Allergies:</strong> <span style="color: #27ae60;">No known allergies</span></p>
                        <?php endif; ?>
                        
                        <?php if (empty($diet_values) && empty($allergy_value)): ?>
                            <p>No dietary information provided.</p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
<!-- Other Details Card -->
<?php 
// Get proof file from root level (as shown in your debug output)
$proof_file_id = isset($additional['proof_file_id']) ? $additional['proof_file_id'] : 0;
$proof_file_url = isset($additional['proof_file_url']) ? $additional['proof_file_url'] : '';
$proof_file_name = isset($additional['proof_file_name']) ? $additional['proof_file_name'] : '';

// If URL is empty but ID exists, try to get URL from ID
if (empty($proof_file_url) && !empty($proof_file_id)) {
    $proof_file_url = wp_get_attachment_url($proof_file_id);
}
?>

<?php if (!empty($additional['other_details'])): ?>
<div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
    <h2 style="margin-top: 0; padding-bottom: 10px; border-bottom: 2px solid #2271b1; display: flex; align-items: center;">
        <span class="dashicons dashicons-info" style="margin-right: 10px;"></span>
        Other Details
    </h2>
    
    <?php 
    $other = $additional['other_details'];
    ?>
    <p><strong>Image Release:</strong> <?php echo !empty($other['image_release']) ? '✅ Authorized' : '❌ Not Authorized'; ?></p>
    
    <?php if (!empty($other['free_communication'])): ?>
    <p><strong>Free Communication:</strong> <?php echo ucfirst($other['free_communication']); ?></p>
    <?php endif; ?>
    
    <?php if (!empty($other['observations'])): ?>
    <p><strong>Observations:</strong><br><?php echo nl2br(esc_html($other['observations'])); ?></p>
    <?php endif; ?>
    
    <?php if ($invoice_request): ?>
    <p><strong>Invoice Requested:</strong> Yes</p>
    <?php if (!empty($company_details['company_name'])): ?>
        <div style="background: #f9f9f9; padding: 10px; margin-top: 10px; border-left: 4px solid #2271b1;">
            <p><strong>Company:</strong> <?php echo esc_html($company_details['company_name']); ?></p>
            <p><strong>CIF:</strong> <?php echo esc_html($company_details['cif']); ?></p>
            <p><strong>Tax Address:</strong> <?php echo esc_html($company_details['tax_address']); ?></p>
        </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Proof Document Card (Separate card using root level data) -->
<?php if (!empty($proof_file_url)): 
    // Get file info
    $file_type = wp_check_filetype($proof_file_url);
    $is_image = in_array($file_type['ext'], ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    $file_size = $proof_file_id ? size_format(filesize(get_attached_file($proof_file_id))) : '';
    $display_name = !empty($proof_file_name) ? $proof_file_name : basename($proof_file_url);
?>
<div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 20px; margin-top: 20px;">
    <h2 style="margin-top: 0; padding-bottom: 10px; border-bottom: 2px solid #2271b1; display: flex; align-items: center;">
        <span class="dashicons dashicons-media-document" style="margin-right: 10px;"></span>
        Proof Document
    </h2>
    
    <div style="margin-top: 15px;">
        <?php if ($is_image): ?>
            <!-- Image with preview -->
            <div style="text-align: center; margin-bottom: 15px;">
                <a href="<?php echo esc_url($proof_file_url); ?>" target="_blank">
                    <img src="<?php echo esc_url($proof_file_url); ?>" 
                        style="max-width: 100%; max-height: 250px; border: 3px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-radius: 8px; cursor: zoom-in;"
                        alt="Proof Document"
                        title="Click to view full size">
                </a>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; background: #f9f9f9; padding: 10px; border-radius: 6px;">
                <div>
                    <strong style="font-size: 14px;"><?php echo esc_html($display_name); ?></strong>
                    <?php if ($file_size): ?>
                        <span style="color: #666; margin-left: 10px; font-size: 12px;">(<?php echo $file_size; ?>)</span>
                    <?php endif; ?>
                </div>
                <a href="<?php echo esc_url($proof_file_url); ?>" target="_blank" class="button button-primary">
                    <span class="dashicons dashicons-visibility" style="margin-top: 4px;"></span> View Full Size
                </a>
            </div>
        <?php else: ?>
            <!-- PDF or other file -->
            <div style="display: flex; align-items: center; gap: 15px; padding: 15px; background: #f9f9f9; border-radius: 6px;">
                <span class="dashicons dashicons-pdf" style="font-size: 40px; width: 40px; height: 40px; color: #dc3232;"></span>
                <div style="flex: 1;">
                    <strong style="font-size: 15px;"><?php echo esc_html($display_name); ?></strong>
                    <p style="margin: 5px 0 0 0; color: #666;">
                        <?php echo strtoupper($file_type['ext']); ?> Document
                        <?php if ($file_size): ?>
                            · <?php echo $file_size; ?>
                        <?php endif; ?>
                    </p>
                </div>
                <a href="<?php echo esc_url($proof_file_url); ?>" target="_blank" class="button button-primary">
                    <span class="dashicons dashicons-visibility" style="margin-top: 4px;"></span> Open
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php elseif (!empty($proof_file_id)): ?>
    <!-- Debug: File ID exists but URL not generated -->
    <div style="background: #fff3cd; border: 1px solid #ffeeba; border-radius: 8px; padding: 15px; margin-top: 20px; color: #856404;">
        <strong>Notice:</strong> Proof file exists (ID: <?php echo $proof_file_id; ?>) but URL could not be generated.
        <?php 
        $attachment_url = wp_get_attachment_url($proof_file_id);
        if ($attachment_url) {
            echo ' URL found: ' . $attachment_url;
        } else {
            echo ' Please check if file exists in media library.';
        }
        ?>
    </div>
<?php endif; ?>

















                    
                </div>
            </div>
        </div>
        
<script>
jQuery(document).ready(function($) {
    // Status change dropdown
    $('#booking-status-change').on('change', function() {
            var status = $(this).val();
            var bookingId = $(this).data('id');
            
            if (status) {
                // Show loading
                $(this).prop('disabled', true);
                
                // Create dynamic nonce URL
                var url = '?page=<?php echo $_REQUEST['page']; ?>&action=mark_' + status + '&id=' + bookingId;
                
                // Add nonce dynamically based on status
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'crs_get_status_nonce',
                        status: status,
                        booking_id: bookingId,
                        nonce: '<?php echo wp_create_nonce('crs_ajax_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Redirect with nonce
                            window.location.href = url + '&_wpnonce=' + response.data.nonce;
                        } else {
                            alert('Error: Could not get nonce');
                            $('#booking-status-change').prop('disabled', false);
                        }
                    },
                    error: function() {
                        alert('Error: Could not change status');
                        $('#booking-status-change').prop('disabled', false);
                    }
                });
            }
        });

        // 🔥 INVOICE SEND BUTTON HANDLER
        $('#crs-send-invoice').on('click', function() {
            var bookingId = $(this).data('booking-id');
            var $btn = $(this);
            
            $btn.prop('disabled', true).text('Sending...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'crs_send_invoice',
                    booking_id: bookingId,
                    nonce: '<?php echo wp_create_nonce('crs_admin_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Invoice sent successfully to customer!');
                        $btn.text('Invoice Sent').addClass('disabled');
                    } else {
                        alert('Failed to send invoice: ' + response.data);
                        $btn.prop('disabled', false).text('Send Invoice to Customer');
                    }
                },
                error: function() {
                    alert('Failed to send invoice');
                    $btn.prop('disabled', false).text('Send Invoice to Customer');
                }
            });
        });




});
</script>

        
        <?php
    }
}