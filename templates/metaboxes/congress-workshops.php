<?php if (!defined('ABSPATH')) exit; ?>
<div id="crs-workshops-repeater">
    <div class="crs-repeater-list">
        <?php foreach ($workshops as $index => $workshop): ?>
        <div class="crs-repeater-item">
            <div class="crs-repeater-header">
                <span class="crs-item-title"><?php echo !empty($workshop['workshop_title']) ? esc_html($workshop['workshop_title']) : __('New Workshop', 'crscngres'); ?></span>
                <button type="button" class="button crs-remove-item"><?php _e('Remove', 'crscngres'); ?></button>
            </div>
            <div class="crs-repeater-fields">
                <table class="form-table">
                    <tr>
                        <th><label><?php _e('Workshop Title', 'crscngres'); ?></label></th>
                        <td><input type="text" name="workshops[<?php echo $index; ?>][workshop_title]" value="<?php echo esc_attr($workshop['workshop_title']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Date', 'crscngres'); ?></label></th>
                        <td><input type="date" name="workshops[<?php echo $index; ?>][workshop_date]" value="<?php echo esc_attr($workshop['workshop_date']); ?>"></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Start Time', 'crscngres'); ?></label></th>
                        <td><input type="time" name="workshops[<?php echo $index; ?>][start_time]" value="<?php echo esc_attr($workshop['start_time']); ?>"></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('End Time', 'crscngres'); ?></label></th>
                        <td><input type="time" name="workshops[<?php echo $index; ?>][end_time]" value="<?php echo esc_attr($workshop['end_time']); ?>"></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Total Seats Capacity', 'crscngres'); ?></label></th>
                        <td><input type="number" name="workshops[<?php echo $index; ?>][total_seats_capacity]" value="<?php echo esc_attr($workshop['total_seats_capacity']); ?>" min="0"></td>
                    </tr>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <button type="button" id="crs-add-workshop" class="button button-primary"><?php _e('Add Workshop', 'crscngres'); ?></button>
</div>

<script>
jQuery(document).ready(function($) {
    var workshopIndex = <?php echo count($workshops); ?>;
    
    $('#crs-add-workshop').on('click', function() {
        var template = `
            <div class="crs-repeater-item">
                <div class="crs-repeater-header">
                    <span class="crs-item-title"><?php _e('New Workshop', 'crscngres'); ?></span>
                    <button type="button" class="button crs-remove-item"><?php _e('Remove', 'crscngres'); ?></button>
                </div>
                <div class="crs-repeater-fields">
                    <table class="form-table">
                        <tr><th><label><?php _e('Workshop Title', 'crscngres'); ?></label></th><td><input type="text" name="workshops[` + workshopIndex + `][workshop_title]" value="" class="regular-text"></td></tr>
                        <tr><th><label><?php _e('Date', 'crscngres'); ?></label></th><td><input type="date" name="workshops[` + workshopIndex + `][workshop_date]" value=""></td></tr>
                        <tr><th><label><?php _e('Start Time', 'crscngres'); ?></label></th><td><input type="time" name="workshops[` + workshopIndex + `][start_time]" value=""></td></tr>
                        <tr><th><label><?php _e('End Time', 'crscngres'); ?></label></th><td><input type="time" name="workshops[` + workshopIndex + `][end_time]" value=""></td></tr>
                        <tr><th><label><?php _e('Total Seats Capacity', 'crscngres'); ?></label></th><td><input type="number" name="workshops[` + workshopIndex + `][total_seats_capacity]" value="" min="0"></td></tr>
                    </table>
                </div>
            </div>
        `;
        $('.crs-repeater-list').append(template);
        workshopIndex++;
    });
    
    $(document).on('click', '.crs-remove-item', function() {
        if (confirm('<?php _e('Are you sure you want to remove this item?', 'crscngres'); ?>')) {
            $(this).closest('.crs-repeater-item').remove();
        }
    });
});
</script>