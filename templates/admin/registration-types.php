<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
    <h1><?php _e('Registration Types', 'crscngres'); ?></h1>
    
    <div class="crs-admin-card">
        <h2><?php _e('Add New Registration Type', 'crscngres'); ?></h2>
        <form method="post" action="">
            <?php wp_nonce_field('crs_add_registration_type'); ?>
            <input type="hidden" name="action" value="add_type">
            
            <table class="form-table">
                <tr>
                    <th><label for="name"><?php _e('Name', 'crscngres'); ?></label></th>
                    <td><input type="text" name="name" id="name" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="price"><?php _e('Price (€)', 'crscngres'); ?></label></th>
                    <td><input type="number" name="price" id="price" step="0.01" min="0" required></td>
                </tr>
                <tr>
                    <th><label for="is_proof_require"><?php _e('Require Proof?', 'crscngres'); ?></label></th>
                    <td>
                        <input type="checkbox" name="is_proof_require" id="is_proof_require" value="1">
                        <label for="is_proof_require"><?php _e('Yes, require proof document', 'crscngres'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th><label for="sort_order"><?php _e('Sort Order', 'crscngres'); ?></label></th>
                    <td><input type="number" name="sort_order" id="sort_order" value="0" min="0"></td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" class="button button-primary" value="<?php _e('Add Registration Type', 'crscngres'); ?>">
            </p>
        </form>
    </div>
    
    <div class="crs-admin-card">
        <h2><?php _e('Existing Registration Types', 'crscngres'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Name', 'crscngres'); ?></th>
                    <th><?php _e('Price', 'crscngres'); ?></th>
                    <th><?php _e('Proof Required', 'crscngres'); ?></th>
                    <th><?php _e('Sort Order', 'crscngres'); ?></th>
                    <th><?php _e('Actions', 'crscngres'); ?></th>
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
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=crs-registration-types&action=delete&id=' . $type->id), 'crs_delete_registration_type'); ?>" 
                           class="button button-small" 
                           onclick="return confirm('<?php _e('Are you sure you want to delete this registration type?', 'crscngres'); ?>')"><?php _e('Delete', 'crscngres'); ?></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    .crs-admin-card {
        background: #fff;
        border: 1px solid #ccd0d4;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
        padding: 20px;
        margin: 20px 0;
    }
    .crs-admin-card h2 {
        margin-top: 0;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }
</style>