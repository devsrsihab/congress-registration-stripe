<?php
class CR_Bookings_List_Table extends WP_List_Table {
    
    public function __construct() {
        parent::__construct(array(
            'singular' => 'booking',
            'plural'   => 'bookings',
            'ajax'     => false
        ));
    }
    
    public function get_columns() {
        return array(
            'cb'              => '<input type="checkbox" />',
            'booking_number'  => __('Booking #', CR_TEXT_DOMAIN),
            'user'            => __('User', CR_TEXT_DOMAIN),
            'congress'        => __('Congress', CR_TEXT_DOMAIN),
            'registration_type' => __('Type', CR_TEXT_DOMAIN),
            'total_amount'    => __('Amount', CR_TEXT_DOMAIN),
            'booking_status'  => __('Status', CR_TEXT_DOMAIN),
            'payment_status'  => __('Payment', CR_TEXT_DOMAIN),
            'created_at'      => __('Date', CR_TEXT_DOMAIN),
            'actions'         => __('Actions', CR_TEXT_DOMAIN)
        );
    }
    
    public function get_sortable_columns() {
        return array(
            'booking_number' => array('booking_number', false),
            'total_amount'   => array('total_amount', false),
            'created_at'     => array('created_at', true),
            'booking_status' => array('booking_status', false)
        );
    }
    
    public function get_bulk_actions() {
        return array(
            'delete' => __('Delete', CR_TEXT_DOMAIN),
            'mark_completed' => __('Mark as Completed', CR_TEXT_DOMAIN),
            'mark_cancelled' => __('Mark as Cancelled', CR_TEXT_DOMAIN)
        );
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
                " AND (booking_number LIKE '%%%s%%' OR user_id IN (SELECT ID FROM {$wpdb->users} WHERE user_login LIKE '%%%s%%' OR display_name LIKE '%%%s%%'))",
                $search, $search, $search
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
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
        
        // Set column headers
        $this->_column_headers = array(
            $this->get_columns(),
            array(),
            $this->get_sortable_columns()
        );
    }
    
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'booking_number':
                return '<strong>' . esc_html($item->booking_number) . '</strong>';
                
            case 'user':
                $user = get_userdata($item->user_id);
                return $user ? esc_html($user->display_name) . '<br><small>' . esc_html($user->user_email) . '</small>' : 'N/A';
                
            case 'congress':
                $title = get_the_title($item->congress_id);
                $edit_link = get_edit_post_link($item->congress_id);
                return $edit_link ? '<a href="' . $edit_link . '">' . esc_html($title) . '</a>' : esc_html($title);
                
            case 'registration_type':
                return esc_html(ucfirst(str_replace('_', ' ', $item->registration_type)));
                
            case 'total_amount':
                return wc_price($item->total_amount);
                
            case 'booking_status':
                $status = esc_html($item->booking_status);
                $class = 'cr-status cr-status-' . $item->booking_status;
                return '<span class="' . $class . '">' . $status . '</span>';
                
            case 'payment_status':
                $status = esc_html($item->payment_status);
                $class = 'cr-status cr-status-' . $item->payment_status;
                return '<span class="' . $class . '">' . $status . '</span>';
                
            case 'created_at':
                return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->created_at));
                
            case 'actions':
                $actions = array();
                
                if ($item->woocommerce_order_id) {
                    $order = wc_get_order($item->woocommerce_order_id);
                    if ($order) {
                        $actions[] = '<a href="' . $order->get_edit_order_url() . '" class="button button-small">' . __('View Order', CR_TEXT_DOMAIN) . '</a>';
                    }
                }
                
                $actions[] = '<button class="button button-small cr-edit-booking" data-id="' . $item->id . '">' . __('Edit', CR_TEXT_DOMAIN) . '</button>';
                
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
        
        if ('delete' === $this->current_action()) {
            $bookings = isset($_REQUEST['booking']) ? $_REQUEST['booking'] : array();
            
            if (!empty($bookings)) {
                $ids = implode(',', array_map('intval', $bookings));
                $wpdb->query("DELETE FROM $table_name WHERE id IN ($ids)");
                
                echo '<div class="notice notice-success"><p>' . __('Bookings deleted.', CR_TEXT_DOMAIN) . '</p></div>';
            }
        }
        
        if ('mark_completed' === $this->current_action()) {
            $bookings = isset($_REQUEST['booking']) ? $_REQUEST['booking'] : array();
            
            if (!empty($bookings)) {
                foreach ($bookings as $booking_id) {
                    $wpdb->update(
                        $table_name,
                        array('booking_status' => 'completed'),
                        array('id' => $booking_id),
                        array('%s'),
                        array('%d')
                    );
                }
                
                echo '<div class="notice notice-success"><p>' . __('Bookings marked as completed.', CR_TEXT_DOMAIN) . '</p></div>';
            }
        }
    }
    
    public function extra_tablenav($which) {
        if ($which == 'top') {
            ?>
            <div class="alignleft actions">
                <select name="filter_status">
                    <option value=""><?php _e('All statuses', CR_TEXT_DOMAIN); ?></option>
                    <option value="pending"><?php _e('Pending', CR_TEXT_DOMAIN); ?></option>
                    <option value="completed"><?php _e('Completed', CR_TEXT_DOMAIN); ?></option>
                    <option value="cancelled"><?php _e('Cancelled', CR_TEXT_DOMAIN); ?></option>
                </select>
                
                <select name="filter_payment">
                    <option value=""><?php _e('All payments', CR_TEXT_DOMAIN); ?></option>
                    <option value="pending"><?php _e('Pending', CR_TEXT_DOMAIN); ?></option>
                    <option value="completed"><?php _e('Completed', CR_TEXT_DOMAIN); ?></option>
                    <option value="failed"><?php _e('Failed', CR_TEXT_DOMAIN); ?></option>
                </select>
                
                <?php submit_button(__('Filter', CR_TEXT_DOMAIN), 'button', 'filter_action', false); ?>
            </div>
            <?php
        }
    }
}