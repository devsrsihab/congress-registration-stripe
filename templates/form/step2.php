<?php 

        $user = wp_get_current_user();
        $is_third_person = isset($data['registration_type']) && $data['registration_type'] == 'third_person';
        
        ob_start();
        ?>
        <div class="crs-step-content">
            <h2 class="crs-step-title"><?php _e('Personal Data', 'crscngres'); ?></h2>
            <p class="crs-step-description"><?php _e('Complete all fields to continue with the registration', 'crscngres'); ?></p>
            
            <div class="crs-form-row">
                <div class="crs-form-group">
                    <label class="crs-label"><?php _e('Name *', 'crscngres'); ?></label>
                    <input type="text" class="crs-input" name="first_name" value="<?php echo $is_third_person ? '' : esc_attr($user->first_name); ?>">
                </div>
                <div class="crs-form-group">
                    <label class="crs-label"><?php _e('Last Name *', 'crscngres'); ?></label>
                    <input type="text" class="crs-input" name="last_name" value="<?php echo $is_third_person ? '' : esc_attr($user->last_name); ?>">
                </div>
            </div>
            
            <div class="crs-form-row">
                <div class="crs-form-group">
                    <label class="crs-label"><?php _e('ID/NIE/Passport *', 'crscngres'); ?></label>
                    <input type="text" class="crs-input" name="id_number">
                </div>
                <div class="crs-form-group">
                    <label class="crs-label"><?php _e('Phone *', 'crscngres'); ?></label>
                    <input type="tel" class="crs-input" name="phone" value="<?php echo $is_third_person ? '' : esc_attr(get_user_meta($user->ID, 'billing_phone', true)); ?>">
                </div>
            </div>
            
            <div class="crs-form-row">
                <div class="crs-form-group">
                    <label class="crs-label"><?php _e('Address *', 'crscngres'); ?></label>
                    <input type="text" class="crs-input" name="address">
                </div>
                <div class="crs-form-group">
                    <label class="crs-label"><?php _e('Location *', 'crscngres'); ?></label>
                    <input type="text" class="crs-input" name="location">
                </div>
            </div>
            
            <div class="crs-form-row">
                <div class="crs-form-group">
                    <label class="crs-label"><?php _e('Postal Code *', 'crscngres'); ?></label>
                    <input type="text" class="crs-input" name="postal_code">
                </div>
                <div class="crs-form-group">
                    <label class="crs-label"><?php _e('Country *', 'crscngres'); ?></label>
                    <input type="text" class="crs-input" name="country">
                </div>
            </div>
            
            <div class="crs-form-row">
                <div class="crs-form-group">
                    <label class="crs-label"><?php _e('Province *', 'crscngres'); ?></label>
                    <input type="text" class="crs-input" name="province">
                </div>
                <div class="crs-form-group">
                    <label class="crs-label"><?php _e('Work Center', 'crscngres'); ?></label>
                    <input type="text" class="crs-input" name="work_center">
                </div>
            </div>
            
            <div class="crs-form-group">
                <label class="crs-label"><?php _e('Email *', 'crscngres'); ?></label>
                <input type="email" class="crs-input" name="email" value="<?php echo $is_third_person ? '' : esc_attr($user->user_email); ?>">
            </div>
        </div>