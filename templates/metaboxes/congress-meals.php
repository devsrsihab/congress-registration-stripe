<?php if (!defined('ABSPATH')) exit; ?>
<div id="crs-meals-repeater">
    <div class="crs-repeater-list">
        <?php foreach ($meals as $index => $meal): ?>
        <div class="crs-repeater-item">
            <div class="crs-repeater-header">
                <span class="crs-item-title"><?php echo !empty($meal['meal_title']) ? esc_html($meal['meal_title']) : __('New Meal', 'crscngres'); ?></span>
                <button type="button" class="button crs-remove-item"><?php _e('Remove', 'crscngres'); ?></button>
            </div>
            <div class="crs-repeater-fields">
                <table class="form-table">
                    <tr>
                        <th><label><?php _e('Meal Title', 'crscngres'); ?></label></th>
                        <td><input type="text" name="meals[<?php echo $index; ?>][meal_title]" value="<?php echo esc_attr($meal['meal_title']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Meal Type', 'crscngres'); ?></label></th>
                        <td>
                            <select name="meals[<?php echo $index; ?>][meal_type]">
                                <option value="Meal" <?php selected($meal['meal_type'], 'Meal'); ?>><?php _e('Meal', 'crscngres'); ?></option>
                                <option value="Gala_Dinner" <?php selected($meal['meal_type'], 'Gala_Dinner'); ?>><?php _e('Gala Dinner', 'crscngres'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Date', 'crscngres'); ?></label></th>
                        <td><input type="date" name="meals[<?php echo $index; ?>][meal_date]" value="<?php echo esc_attr($meal['meal_date']); ?>"></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Price (€)', 'crscngres'); ?></label></th>
                        <td><input type="number" name="meals[<?php echo $index; ?>][meal_price]" value="<?php echo esc_attr($meal['meal_price']); ?>" step="0.01" min="0"></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Status', 'crscngres'); ?></label></th>
                        <td>
                            <select name="meals[<?php echo $index; ?>][meal_status]">
                                <option value="Enable" <?php selected($meal['meal_status'], 'Enable'); ?>><?php _e('Enable', 'crscngres'); ?></option>
                                <option value="Disable" <?php selected($meal['meal_status'], 'Disable'); ?>><?php _e('Disable', 'crscngres'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <button type="button" id="crs-add-meal" class="button button-primary"><?php _e('Add Meal', 'crscngres'); ?></button>
</div>

<script>
jQuery(document).ready(function($) {
    var mealIndex = <?php echo count($meals); ?>;
    
    $('#crs-add-meal').on('click', function() {
        var template = `
            <div class="crs-repeater-item">
                <div class="crs-repeater-header">
                    <span class="crs-item-title"><?php _e('New Meal', 'crscngres'); ?></span>
                    <button type="button" class="button crs-remove-item"><?php _e('Remove', 'crscngres'); ?></button>
                </div>
                <div class="crs-repeater-fields">
                    <table class="form-table">
                        <tr><th><label><?php _e('Meal Title', 'crscngres'); ?></label></th><td><input type="text" name="meals[` + mealIndex + `][meal_title]" value="" class="regular-text"></td></tr>
                        <tr><th><label><?php _e('Meal Type', 'crscngres'); ?></label></th><td><select name="meals[` + mealIndex + `][meal_type]"><option value="Meal"><?php _e('Meal', 'crscngres'); ?></option><option value="Gala_Dinner"><?php _e('Gala Dinner', 'crscngres'); ?></option></select></td></tr>
                        <tr><th><label><?php _e('Date', 'crscngres'); ?></label></th><td><input type="date" name="meals[` + mealIndex + `][meal_date]" value=""></td></tr>
                        <tr><th><label><?php _e('Price (€)', 'crscngres'); ?></label></th><td><input type="number" name="meals[` + mealIndex + `][meal_price]" value="" step="0.01" min="0"></td></tr>
                        <tr><th><label><?php _e('Status', 'crscngres'); ?></label></th><td><select name="meals[` + mealIndex + `][meal_status]"><option value="Enable"><?php _e('Enable', 'crscngres'); ?></option><option value="Disable"><?php _e('Disable', 'crscngres'); ?></option></select></td></tr>
                    </table>
                </div>
            </div>
        `;
        $('.crs-repeater-list').append(template);
        mealIndex++;
    });
    
    $(document).on('click', '.crs-remove-item', function() {
        if (confirm('<?php _e('Are you sure you want to remove this item?', 'crscngres'); ?>')) {
            $(this).closest('.crs-repeater-item').remove();
        }
    });
});
</script>

<style>
    .crs-repeater-item { background: #f9f9f9; border: 1px solid #ddd; margin-bottom: 15px; border-radius: 4px; }
    .crs-repeater-header { background: #f1f1f1; padding: 10px 15px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; }
    .crs-repeater-fields { padding: 15px; }
    .crs-repeater-fields table { margin: 0; }
</style>