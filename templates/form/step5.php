<?php 

    $meals = get_post_meta($congress_id, 'congress_meals', true);
    
    if (!is_array($meals)) {
        $meals = [];
    }
    
    // Filter only enabled meals
    $meals = array_filter($meals, function($meal) {
        return isset($meal['meal_status']) && $meal['meal_status'] == 'Enable';
    });
    
    // Calculate total selected meals
    $selected_meals_total = 0;
    if (isset($data['meals']) && is_array($data['meals'])) {
        foreach ($data['meals'] as $meal_index) {
            if (isset($meals[$meal_index])) {
                $selected_meals_total += $meals[$meal_index]['meal_price'];
            }
        }
    }
    
    // Get diet values
    $diet_values = isset($data['diet']) && is_array($data['diet']) ? $data['diet'] : array();
    if (empty($diet_values)) {
        $diet_values = array('no');
    }
    
    // Get allergy value (handle both string and array)
    $allergy_value = 'no';
    if (isset($data['allergy'])) {
        if (is_array($data['allergy'])) {
            $allergy_value = in_array('yes', $data['allergy']) ? 'yes' : 'no';
        } else {
            $allergy_value = $data['allergy'];
        }
    }
    
    $allergy_details = isset($data['allergy_details']) ? $data['allergy_details'] : '';
    
    ob_start();
    ?>
    <div class="crs-step-content">
        <h2 class="crs-step-title"><?php _e('Lunches and Dinners sds', 'crscngres'); ?></h2>
        <p class="crs-step-description"><?php _e('Select the lunches and dinners you wish to attend', 'crscngres'); ?></p>
        
        <div class="crs-meals-list">
            <?php foreach ($meals as $index => $meal): 
                $meal_type = $meal['meal_type'] == 'Gala_Dinner' ? __('Gala Dinner', 'crscngres') : __('Meal', 'crscngres');
                $meal_date = date('M j', strtotime($meal['meal_date']));
                $is_checked = isset($data['meals']) && is_array($data['meals']) && in_array($index, $data['meals']);
                $badge_class = $meal['meal_type'] == 'Gala_Dinner' ? 'crs-badge-gala' : 'crs-badge-meal';
            ?>
            <div class="crs-meal-option <?php echo $is_checked ? 'selected' : ''; ?>" data-index="<?php echo $index; ?>" data-price="<?php echo $meal['meal_price']; ?>">
                <div class="crs-meal-left">
                    <div class="crs-meal-checkbox">
                        <input type="checkbox" name="meals[<?php echo $index; ?>]" value="<?php echo $index; ?>" id="meal_<?php echo $index; ?>" <?php echo $is_checked ? 'checked' : ''; ?>>
                        <div class="crs-meal-text">
                            <span class="crs-meal-title"><?php echo esc_html($meal['meal_title']); ?> (<?php echo $meal_date; ?>)</span>
                            <span class="crs-meal-badge <?php echo $badge_class; ?>"><?php echo $meal_type; ?></span>
                        </div>
                    </div>
                </div>
                <div class="crs-meal-price"><?php printf(__('€%s', 'crscngres'), $meal['meal_price']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="crs-total-section">
            <div class="crs-total-row">
                <span class="crs-total-label"><?php _e('Total meals selected:', 'crscngres'); ?></span>
                <span class="crs-total-amount"><?php printf(__('€%s', 'crscngres'), $selected_meals_total); ?></span>
            </div>
        </div>
        
        <div class="crs-allergy-section">
            <h3 class="crs-section-subtitle"><?php _e('Allergy and intolerance information', 'crscngres'); ?></h3>
            
            <!-- Diet Section -->
            <div class="crs-diet-question">
                <label class="crs-question-label"><?php _e('Do you follow any special diet?', 'crscngres'); ?></label>
                
                <div class="crs-diet-options">
                    <label class="crs-diet-btn <?php echo in_array('no', $diet_values) ? 'active' : ''; ?>">
                        <input type="checkbox" name="diet[]" value="no" <?php echo in_array('no', $diet_values) ? 'checked' : ''; ?>>
                        <span><?php _e('No', 'crscngres'); ?></span>
                    </label>
                    
                    <label class="crs-diet-btn <?php echo in_array('vegetarian', $diet_values) ? 'active' : ''; ?>">
                        <input type="checkbox" name="diet[]" value="vegetarian" <?php echo in_array('vegetarian', $diet_values) ? 'checked' : ''; ?>>
                        <span><?php _e('Vegetarian', 'crscngres'); ?></span>
                    </label>
                    
                    <label class="crs-diet-btn <?php echo in_array('vegan', $diet_values) ? 'active' : ''; ?>">
                        <input type="checkbox" name="diet[]" value="vegan" <?php echo in_array('vegan', $diet_values) ? 'checked' : ''; ?>>
                        <span><?php _e('Vegan', 'crscngres'); ?></span>
                    </label>
                    
                    <label class="crs-diet-btn <?php echo in_array('other', $diet_values) ? 'active' : ''; ?>">
                        <input type="checkbox" name="diet[]" value="other" <?php echo in_array('other', $diet_values) ? 'checked' : ''; ?>>
                        <span><?php _e('Other', 'crscngres'); ?></span>
                    </label>
                </div>
                
                <div class="crs-diet-other-field" id="diet-other-field" style="<?php echo in_array('other', $diet_values) ? 'display:block;' : 'display:none;'; ?>">
                    <input type="text" class="crs-text-input" name="diet_other" placeholder="<?php _e('Please specify your diet', 'crscngres'); ?>" value="<?php echo isset($data['diet_other']) ? esc_attr($data['diet_other']) : ''; ?>">
                </div>
            </div>
            
            <!-- Allergy Section -->
            <div class="crs-allergy-question">
                <label class="crs-question-label"><?php _e('Do you suffer from any type of allergy or intolerance?', 'crscngres'); ?></label>
                
                <div class="crs-allergy-options">
                    <label class="crs-allergy-label <?php echo ($allergy_value == 'no') ? 'active' : ''; ?>">
                        <input type="radio" name="allergy" value="no" <?php echo ($allergy_value == 'no') ? 'checked' : ''; ?>>
                        <span><?php _e('No', 'crscngres'); ?></span>
                    </label>
                    
                    <label class="crs-allergy-label <?php echo ($allergy_value == 'yes') ? 'active' : ''; ?>">
                        <input type="radio" name="allergy" value="yes" <?php echo ($allergy_value == 'yes') ? 'checked' : ''; ?>>
                        <span><?php _e('Yes', 'crscngres'); ?></span>
                    </label>
                </div>
                
                <div class="crs-allergy-yes-field" id="allergy-yes-field" style="<?php echo ($allergy_value == 'yes') ? 'display:block;' : 'display:none;'; ?>">
                    <input type="text" class="crs-text-input" name="allergy_details" placeholder="<?php _e('Please specify your allergy or intolerance', 'crscngres'); ?>" value="<?php echo esc_attr($allergy_details); ?>">
                </div>
            </div>
        </div>
        
        <div class="crs-warning-section">
            <svg class="crs-warning-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21.73 18l-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3z"></path>
                <path d="M12 9v4"></path>
                <path d="M12 17h.01"></path>
            </svg>
            <p class="crs-warning-text">
                <strong><?php _e('IMPORTANT!', 'crscngres'); ?></strong> 
                <?php _e('Please note that if you indicate you will attend a conference dinner or lunch, the organizers will reserve your place. Therefore, if you ultimately do not attend, this will result in an unnecessary additional cost for the conference. We kindly ask that if you confirm your attendance at a dinner or lunch during your registration, you commit to attending.', 'crscngres'); ?>
            </p>
        </div>
    </div>



    <script>
        jQuery(document).ready(function($) {
            // Meal option click handler
            $('.crs-meal-option').on('click', function(e) {
                if ($(e.target).is('input[type="checkbox"]')) {
                    return;
                }
                
                var checkbox = $(this).find('input[type="checkbox"]');
                checkbox.prop('checked', !checkbox.prop('checked'));
                $(this).toggleClass('selected', checkbox.prop('checked'));
                updateMealsTotal();
            });
            
            // Checkbox change handler
            $('.crs-meal-option input[type="checkbox"]').on('change', function() {
                $(this).closest('.crs-meal-option').toggleClass('selected', $(this).prop('checked'));
                updateMealsTotal();
            });
            
            // Diet checkbox handler - ensure "no" works correctly
            $('.crs-diet-btn').on('click', function(e) {
                e.preventDefault();
                
                var $btn = $(this);
                var $checkbox = $btn.find('input[type="checkbox"]');
                var checkboxValue = $checkbox.val();
                var wasChecked = $checkbox.prop('checked');
                
                // Special handling for "No" option
                if (checkboxValue === 'no') {
                    if (!wasChecked) {
                        // Checking "No" - uncheck all others
                        $('.crs-diet-btn input[type="checkbox"]').each(function() {
                            if ($(this).val() !== 'no') {
                                $(this).prop('checked', false);
                                $(this).closest('.crs-diet-btn').removeClass('active');
                            }
                        });
                        $checkbox.prop('checked', true);
                        $btn.addClass('active');
                        $('#diet-other-field').slideUp();
                        $('input[name="diet_other"]').val('');
                    } else {
                        // Unchecking "No" - do nothing, but don't allow unchecking "No" if nothing else is checked
                        var anyOtherChecked = $('.crs-diet-btn input[type="checkbox"]:checked').not('[value="no"]').length > 0;
                        if (!anyOtherChecked) {
                            // Keep "No" checked if nothing else is checked
                            $checkbox.prop('checked', true);
                            $btn.addClass('active');
                        } else {
                            $checkbox.prop('checked', false);
                            $btn.removeClass('active');
                        }
                    }
                } else {
                    // Handling non-"No" options
                    if (!wasChecked) {
                        $checkbox.prop('checked', true);
                        $btn.addClass('active');
                        
                        // If checking any non-"No", uncheck "No"
                        var $noCheckbox = $('.crs-diet-btn input[type="checkbox"][value="no"]');
                        if ($noCheckbox.prop('checked')) {
                            $noCheckbox.prop('checked', false);
                            $noCheckbox.closest('.crs-diet-btn').removeClass('active');
                        }
                        
                        // Handle "Other" field
                        if (checkboxValue === 'other') {
                            $('#diet-other-field').slideDown();
                        }
                    } else {
                        $checkbox.prop('checked', false);
                        $btn.removeClass('active');
                        
                        // If unchecking "Other" and no other "other" checked
                        if (checkboxValue === 'other') {
                            var otherStillChecked = $('input[name="diet[]"][value="other"]:checked').length > 0;
                            if (!otherStillChecked) {
                                $('#diet-other-field').slideUp();
                                $('input[name="diet_other"]').val('');
                            }
                        }
                        
                        // If no non-"No" options checked, check "No"
                        var anyNonNoChecked = $('.crs-diet-btn input[type="checkbox"]:checked').not('[value="no"]').length > 0;
                        if (!anyNonNoChecked) {
                            var $noCheckbox = $('.crs-diet-btn input[type="checkbox"][value="no"]');
                            $noCheckbox.prop('checked', true);
                            $noCheckbox.closest('.crs-diet-btn').addClass('active');
                        }
                    }
                }
                
                // Update formData if exists
                if (typeof formData !== 'undefined') {
                    var selectedValues = [];
                    $('input[name="diet[]"]:checked').each(function() {
                        selectedValues.push($(this).val());
                    });
                    if (selectedValues.length === 0) {
                        selectedValues = ['no'];
                    }
                    formData.diet = selectedValues;
                    formData.diet_other = $('input[name="diet_other"]').val();
                    saveToLocalStorage();
                }
                
                console.log('Selected diet values:', selectedValues);
            });
            
            // Allergy radio handler - FIXED
            $('.crs-allergy-label').on('click', function(e) {
                e.preventDefault();
                var checkbox = $(this).find('input[type="checkbox"]');
                checkbox.prop("checked", !checkbox.prop("checked"));
                
                var $label = $(this);
                var $radio = $label.find('input[type="radio"]');
                var radioValue = $radio.val();
                
                // Update radio checked state
                $radio.prop('checked', true);
                
                // Update active class
                $('.crs-allergy-label').removeClass('active');
                $label.addClass('active');
                
                // Show/hide allergy details field
                if (radioValue === 'yes') {
                    $('#allergy-yes-field').slideDown();
                } else {
                    $('#allergy-yes-field').slideUp();
                    $('input[name="allergy_details"]').val('');
                }
                
                // Update formData immediately (if formData exists in scope)
                if (typeof formData !== 'undefined') {
                    var selectedValues = [];
                    $('input[name="diet[]"]:checked').each(function() {
                        selectedValues.push($(this).val());
                    });
                    if (selectedValues.length === 0) {
                        selectedValues = ['no'];
                    }
                    formData.diet = selectedValues;
                    formData.diet_other = $('input[name="diet_other"]').val();
                    saveToLocalStorage();
                }
            });
            
            // Initialize - make sure "No" is properly handled if it's the only one checked
            var $checkedBoxes = $('input[name="diet[]"]:checked');
            if ($checkedBoxes.length === 0) {
                // If nothing checked, check "No" by default
                var $noCheckbox = $('.crs-diet-btn input[type="checkbox"][value="no"]');
                $noCheckbox.prop('checked', true);
                $noCheckbox.closest('.crs-diet-btn').addClass('active');
            }
            
            // If "No" is checked with others, uncheck others
            var $noChecked = $('.crs-diet-btn input[type="checkbox"][value="no"]:checked');
            if ($noChecked.length > 0) {
                $('.crs-diet-btn input[type="checkbox"]').not('[value="no"]').each(function() {
                    if ($(this).prop('checked')) {
                        $(this).prop('checked', false);
                        $(this).closest('.crs-diet-btn').removeClass('active');
                    }
                });
            }
            
            // Initialize allergy field based on selected radio
            if ($('input[name="allergy"]:checked').val() === 'yes') {
                $('#allergy-yes-field').show();
            }
            
            function updateMealsTotal() {
                var total = 0;
                
                $('.crs-meal-option input[type="checkbox"]:checked').each(function() {
                    var price = $(this).closest('.crs-meal-option').data('price');
                    total += parseFloat(price);
                });
                
                $('.crs-total-amount').text('€' + total);
            }

            
            
            // Initialize total on page load
            updateMealsTotal();
        });
    </script>