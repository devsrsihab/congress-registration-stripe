<?php
class CRS_Registration_Type {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_post_crs_save_registration_type', [$this, 'saveRegistrationType']);
        add_action('admin_post_crs_delete_registration_type', [$this, 'deleteRegistrationType']);
    }
    
    public function addAdminMenu() {
        add_submenu_page(
            'edit.php?post_type=congress',
            'Registration Types',
            'Registration Types',
            'manage_options',
            'crs-registration-types',
            [$this, 'renderAdminPage']
        );
    }
    
    public function registerSettings() {
        register_setting('crs_registration_types', 'crs_registration_types');
    }
    
    public function renderAdminPage() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'registration_types';
        $types = $wpdb->get_results("SELECT * FROM $table_name ORDER BY sort_order ASC");
        ?>
        <div class="wrap">
            <h1>Registration Types</h1>
            
            <div class="crs-admin-section">
                <h2>Add New Registration Type</h2>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="crs_save_registration_type">
                    <?php wp_nonce_field('crs_save_registration_type'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="name">Name</label></th>
                            <td><input type="text" name="name" id="name" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="price">Price (€)</label></th>
                            <td><input type="number" name="price" id="price" step="0.01" min="0" required></td>
                        </tr>
                        <tr>
                            <th><label for="is_proof_require">Require Proof?</label></th>
                            <td>
                                <input type="checkbox" name="is_proof_require" id="is_proof_require" value="1">
                                <label for="is_proof_require">Yes, require proof document</label>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="proof_file">Proof File</label></th>
                            <td>
                                <input type="file" name="proof_file" id="proof_file" accept=".jpg,.jpeg,.png,.pdf">
                                <p class="description">Upload a sample proof file (image or PDF)</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="sort_order">Sort Order</label></th>
                            <td><input type="number" name="sort_order" id="sort_order" value="0" min="0"></td>
                        </tr>
                    </table>
                    
                    <?php submit_button('Add Registration Type'); ?>
                </form>
            </div>
            
            <div class="crs-admin-section">
                <h2>Existing Registration Types</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Proof Required</th>
                            <th>Sort Order</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($types as $type): ?>
                        <tr>
                            <td><?php echo esc_html($type->name); ?></td>
                            <td>€<?php echo number_format($type->price, 2); ?></td>
                            <td><?php echo $type->is_proof_require ? 'Yes' : 'No'; ?></td>
                            <td><?php echo $type->sort_order; ?></td>
                            <td>
                                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=crs_delete_registration_type&id=' . $type->id), 'crs_delete_registration_type'); ?>" 
                                   class="button button-small" 
                                   onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <style>
            .crs-admin-section {
                background: white;
                padding: 20px;
                margin: 20px 0;
                border: 1px solid #ccd0d4;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
        </style>
        <?php
    }
    
    public function saveRegistrationType() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        check_admin_referer('crs_save_registration_type');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'registration_types';
        
        $data = [
            'name' => sanitize_text_field($_POST['name']),
            'price' => floatval($_POST['price']),
            'is_proof_require' => isset($_POST['is_proof_require']) ? 1 : 0,
            'sort_order' => intval($_POST['sort_order'])
        ];
        
        // Handle file upload
        if (!empty($_FILES['proof_file']['name'])) {
            $upload = wp_upload_bits($_FILES['proof_file']['name'], null, file_get_contents($_FILES['proof_file']['tmp_name']));
            if (empty($upload['error'])) {
                $data['proof_file'] = $upload['url'];
            }
        }
        
        $wpdb->insert($table_name, $data);
        
        wp_redirect(admin_url('edit.php?post_type=congress&page=crs-registration-types&message=added'));
        exit;
    }
    
    public function deleteRegistrationType() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        check_admin_referer('crs_delete_registration_type');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'registration_types';
        
        $id = intval($_GET['id']);
        $wpdb->delete($table_name, ['id' => $id], ['%d']);
        
        wp_redirect(admin_url('edit.php?post_type=congress&page=crs-registration-types&message=deleted'));
        exit;
    }
}

// Initialize
new CRS_Registration_Type();