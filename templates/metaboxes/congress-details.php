<?php if (!defined('ABSPATH')) exit; ?>
<table class="form-table">
    <tr>
        <th><label for="start_date"><?php _e('Start Date', 'crscngres'); ?></label></th>
        <td><input type="date" name="start_date" id="start_date" value="<?php echo esc_attr($start_date); ?>" class="regular-text"></td>
    </tr>
    <tr>
        <th><label for="end_date"><?php _e('End Date', 'crscngres'); ?></label></th>
        <td><input type="date" name="end_date" id="end_date" value="<?php echo esc_attr($end_date); ?>" class="regular-text"></td>
    </tr>
    <tr>
        <th><label for="location"><?php _e('Location', 'crscngres'); ?></label></th>
        <td><input type="text" name="location" id="location" value="<?php echo esc_attr($location); ?>" class="regular-text"></td>
    </tr>
    <tr>
        <th><label for="registration_deadline"><?php _e('Registration Deadline', 'crscngres'); ?></label></th>
        <td><input type="date" name="registration_deadline" id="registration_deadline" value="<?php echo esc_attr($registration_deadline); ?>" class="regular-text"></td>
    </tr>
</table>