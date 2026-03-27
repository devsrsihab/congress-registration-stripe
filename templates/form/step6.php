<?php 

    $workshops = get_post_meta($congress_id, 'congress_workshop', true);
    
    if (!is_array($workshops)) {
        $workshops = [];
    }
    
    ob_start();
    ?>
    <div class="crs-step-content">
        <h2 class="crs-step-title"><?php _e('Stake', 'crscngres'); ?></h2>
        <p class="crs-step-description"><?php _e('The following workshops will take place throughout the event. Please select the ones you wish to participate in (optional).', 'crscngres'); ?></p>
        
        <div class="crs-workshops-container">
            <?php 
            $has_available = false;
            foreach ($workshops as $index => $workshop): 
                // Handle seats
                $seats = !empty($workshop['total_seats_capacity']) ? intval($workshop['total_seats_capacity']) : 0;
                $seats_text = sprintf(_n('%s place', '%s places', $seats, 'crscngres'), $seats);
                $is_available = $seats > 0;
                
                if ($is_available) {
                    $has_available = true;
                }
                
                // Skip if no seats available
                if (!$is_available) {
                    continue;
                }
                
                // Handle date
                $workshop_date = '';
                if (!empty($workshop['workshop_date'])) {
                    if (is_numeric($workshop['workshop_date'])) {
                        $workshop_date = date('Y-m-d', $workshop['workshop_date']);
                    } else {
                        $workshop_date = date('Y-m-d', strtotime($workshop['workshop_date']));
                    }
                }
                
                // Handle time
                $start_time = !empty($workshop['start_time']) ? date('H:i', strtotime($workshop['start_time'])) : '09:00';
                $end_time = !empty($workshop['end_time']) ? date('H:i', strtotime($workshop['end_time'])) : '11:00';
                
                $is_checked = isset($data['workshops']) && is_array($data['workshops']) && in_array($index, $data['workshops']);
            ?>
            <div class="crs-workshop-row <?php echo $is_checked ? 'selected' : ''; ?>" data-index="<?php echo $index; ?>" data-seats="<?php echo $seats; ?>">
                <div class="crs-workshop-checkbox">
                    <label class="crs-custom-checkbox">
                        <input type="checkbox" name="workshops[<?php echo $index; ?>]" value="<?php echo $index; ?>" <?php echo $is_checked ? 'checked' : ''; ?>>
                        <span class="crs-checkbox-mark"></span>
                    </label>
                    <div class="crs-workshop-info">
                        <span class="crs-workshop-title"><?php echo esc_html($workshop['workshop_title']); ?></span>
                        <span class="crs-workshop-datetime"><?php echo $workshop_date; ?> · <?php echo $start_time; ?> - <?php echo $end_time; ?></span>
                    </div>
                </div>
                <div class="crs-workshop-seats">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <span class="crs-seats-count"><?php echo $seats_text; ?></span>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (!$has_available): ?>
            <div class="crs-no-workshops">
                <p><?php _e('No workshops available for this congress.', 'crscngres'); ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="crs-communication-section">
            <p class="crs-communication-question"><?php _e('Are you going to present a free communication? (optional)', 'crscngres'); ?></p>
            
            <div class="crs-button-group">
                <label class="crs-button-label <?php echo (isset($data['free_communication']) && $data['free_communication'] == 'yes') ? 'selected' : ''; ?>">
                    <input type="radio" name="free_communication" value="yes" <?php echo isset($data['free_communication']) && $data['free_communication'] == 'yes' ? 'checked' : ''; ?>>
                    <span class="crs-button-text"><?php _e('Yeah', 'crscngres'); ?></span>
                </label>
                
                <label class="crs-button-label <?php echo (isset($data['free_communication']) && $data['free_communication'] == 'no') ? 'selected' : ''; ?>">
                    <input type="radio" name="free_communication" value="no" <?php echo isset($data['free_communication']) && $data['free_communication'] == 'no' ? 'checked' : ''; ?>>
                    <span class="crs-button-text"><?php _e('No', 'crscngres'); ?></span>
                </label>
                
                <label class="crs-button-label crs-button-full <?php echo (isset($data['free_communication']) && $data['free_communication'] == 'thinking') ? 'selected' : ''; ?>">
                    <input type="radio" name="free_communication" value="thinking" <?php echo isset($data['free_communication']) && $data['free_communication'] == 'thinking' ? 'checked' : ''; ?>>
                    <span class="crs-button-text"><?php _e('I\'m thinking about it', 'crscngres'); ?></span>
                </label>
            </div>
        </div>
    </div>





    <script>
        jQuery(document).ready(function($) {
            // Disable workshops with no seats
            $('.crs-workshop-row').each(function() {
                var seats = $(this).data('seats');
                if (seats <= 0) {
                    $(this).addClass('disabled');
                    $(this).find('input[type="checkbox"]').prop('disabled', true);
                }
            });
            
            // Workshop row click handler - only if seats available
            $('.crs-workshop-row:not(.disabled)').on('click', function(e) {
                if ($(e.target).is('input[type="checkbox"]') || $(e.target).is('.crs-checkbox-mark') || $(e.target).is('.crs-custom-checkbox')) {
                    return;
                }
                
                var checkbox = $(this).find('input[type="checkbox"]');
                checkbox.prop('checked', !checkbox.prop('checked'));
                
                if (checkbox.prop('checked')) {
                    $(this).addClass('selected');
                } else {
                    $(this).removeClass('selected');
                }
            });
            
            // Custom checkbox click handler
            $('.crs-custom-checkbox').on('click', function(e) {
                e.stopPropagation();
                var checkbox = $(this).find('input[type="checkbox"]');
                if (!checkbox.prop('disabled')) {
                    checkbox.prop('checked', !checkbox.prop('checked'));
                    checkbox.trigger('change');
                }
            });
            
            // Checkbox change handler
            $('.crs-workshop-row input[type="checkbox"]').on('change', function() {
                var row = $(this).closest('.crs-workshop-row');
                if ($(this).prop('checked')) {
                    row.addClass('selected');
                } else {
                    row.removeClass('selected');
                }
            });
            
            // Radio button click handlers
            $('.crs-button-label').on('click', function() {
                var radio = $(this).find('input[type="radio"]');
                radio.prop('checked', true);
                
                $('.crs-button-label').removeClass('selected');
                $(this).addClass('selected');
            });
            
            // Restore selected state from data
            <?php if (isset($data['workshops']) && is_array($data['workshops'])): ?>
                <?php foreach ($data['workshops'] as $workshop_index): ?>
                    $('.crs-workshop-row[data-index="<?php echo $workshop_index; ?>"]').addClass('selected');
                    $('.crs-workshop-row[data-index="<?php echo $workshop_index; ?>"] input[type="checkbox"]').prop('checked', true);
                <?php endforeach; ?>
            <?php endif; ?>
            
            // Restore radio button selection
            <?php if (isset($data['free_communication'])): ?>
                $('.crs-button-label input[value="<?php echo $data['free_communication']; ?>"]').prop('checked', true);
                $('.crs-button-label input[value="<?php echo $data['free_communication']; ?>"]').closest('.crs-button-label').addClass('selected');
            <?php endif; ?>
        });
    </script>