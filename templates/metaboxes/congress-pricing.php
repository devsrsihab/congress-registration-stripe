<?php if (!defined('ABSPATH')) exit; ?>
<p>
    <label for="base_price"><?php _e('Base Price (€):', 'crscngres'); ?></label>
    <input type="number" name="base_price" id="base_price" value="<?php echo esc_attr($base_price); ?>" class="widefat" step="0.01" min="0">
</p>