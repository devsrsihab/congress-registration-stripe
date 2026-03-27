<?php if (!defined('ABSPATH')) exit; ?>
<table class="form-table">
    <tr>
        <th><label for="price_per_night"><?php _e('Price per Night (€)', 'crscngres'); ?></label></th>
        <td><input type="number" name="price_per_night" id="price_per_night" value="<?php echo esc_attr($price_per_night); ?>" step="0.01" min="0" class="regular-text"></td>
    </tr>
    <tr>
        <th><label for="address"><?php _e('Address', 'crscngres'); ?></label></th>
        <td><textarea name="address" id="address" class="regular-text" rows="3"><?php echo esc_textarea($address); ?></textarea></td>
    </tr>
    <tr>
        <th><label for="phone"><?php _e('Phone', 'crscngres'); ?></label></th>
        <td><input type="text" name="phone" id="phone" value="<?php echo esc_attr($phone); ?>" class="regular-text"></td>
    </tr>
    <tr>
        <th><label for="email"><?php _e('Email', 'crscngres'); ?></label></th>
        <td><input type="email" name="email" id="email" value="<?php echo esc_attr($email); ?>" class="regular-text"></td>
    </tr>
    <tr>
        <th><label for="website"><?php _e('Website', 'crscngres'); ?></label></th>
        <td><input type="url" name="website" id="website" value="<?php echo esc_attr($website); ?>" class="regular-text"></td>
    </tr>
</table>