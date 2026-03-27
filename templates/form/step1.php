        <div class="crs-step-content">
            <h2 class="crs-step-title"><?php _e('Who do you want to register?', 'crscngres'); ?></h2>
            <p class="crs-step-description"><?php _e('Please select whether the registration is for you or for someone else.', 'crscngres'); ?></p>
            
            <div class="crs-options-container">
                <!-- Personally Option -->
                <div class="crs-option-card <?php echo (!isset($_POST['registration_type']) || $_POST['registration_type'] == 'personal') ? 'selected' : ''; ?>" data-type="personal">
                    <div class="crs-option-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div class="crs-option-content">
                        <h3 class="crs-option-title"><?php _e('Personally', 'crscngres'); ?></h3>
                        <p class="crs-option-subtitle"><?php _e('Registration will be done using my account details', 'crscngres'); ?></p>
                    </div>
                </div>
                
                <!-- To a third person Option -->
                <div class="crs-option-card <?php echo (isset($_POST['registration_type']) && $_POST['registration_type'] == 'third_person') ? 'selected' : ''; ?>" data-type="third_person">
                    <div class="crs-option-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <div class="crs-option-content">
                        <h3 class="crs-option-title"><?php _e('To a third person', 'crscngres'); ?></h3>
                        <p class="crs-option-subtitle"><?php _e('I will register someone else in my name', 'crscngres'); ?></p>
                    </div>
                </div>
            </div>
            
            <input type="hidden" name="registration_type" id="registration_type" value="<?php echo isset($_POST['registration_type']) ? $_POST['registration_type'] : 'personal'; ?>">
            
            <!-- Third Person Fields - Show only when third_person is selected -->
            <div class="crs-third-person-fields" id="third-person-fields" style="<?php echo (isset($_POST['registration_type']) && $_POST['registration_type'] == 'third_person') ? 'display: block;' : 'display: none;'; ?>">
                <div class="crs-form-group">
                    <label class="crs-label"><?php _e('Name of the person *', 'crscngres'); ?></label>
                    <input type="text" class="crs-input" name="third_person_name" value="<?php echo isset($_POST['third_person_name']) ? esc_attr($_POST['third_person_name']) : ''; ?>">
                </div>
                <div class="crs-form-group">
                    <label class="crs-label"><?php _e('Email *', 'crscngres'); ?></label>
                    <input type="email" class="crs-input" name="third_person_email" value="<?php echo isset($_POST['third_person_email']) ? esc_attr($_POST['third_person_email']) : ''; ?>">
                </div>
                <div class="crs-form-group">
                    <label class="crs-label"><?php _e('Password *', 'crscngres'); ?></label>
                    <input type="password" class="crs-input" name="third_person_password" minlength="8" value="<?php echo isset($_POST['third_person_password']) ? esc_attr($_POST['third_person_password']) : ''; ?>">
                    <p class="crs-field-note"><?php _e('Minimum 8 characters', 'crscngres'); ?></p>
                </div>
                
                <!-- Important Note - Only shows inside third person fields -->
                <div class="crs-note-box">
                    <p class="crs-note-text">
                        <strong><?php _e('Important:', 'crscngres'); ?></strong> 
                        <?php _e('Save the login details you set to provide them to the person you are registering, so they can access their profile and review their registration summary, documents, and other details.', 'crscngres'); ?>
                    </p>
                </div>
            </div>
            
            <!-- Optional: Show a different message for personal (if needed) -->
            <div class="crs-personal-message" id="personal-message" style="<?php echo (!isset($_POST['registration_type']) || $_POST['registration_type'] == 'personal') ? 'display: block;' : 'display: none;'; ?>">
                <!-- You can add personal-specific message here if needed, or leave empty -->
            </div>
        </div>


        <script>
            jQuery(document).ready(function($) {
                // Initially check which option is selected
                var initialType = $('#registration_type').val();
                
                // Option card click handler
                $('.crs-option-card').on('click', function() {
                    var type = $(this).data('type');
                    
                    // Remove selected class from all options
                    $('.crs-option-card').removeClass('selected');
                    $(this).addClass('selected');
                    
                    // Update hidden input
                    $('#registration_type').val(type);
                    
                    // Show/hide third person fields based on selection
                    if (type === 'third_person') {
                        $('#third-person-fields').slideDown();
                        $('#personal-message').slideUp();
                    } else {
                        $('#third-person-fields').slideUp();
                        $('#personal-message').slideDown();
                        // Clear fields when hidden
                        $('input[name="third_person_name"]').val('');
                        $('input[name="third_person_email"]').val('');
                        $('input[name="third_person_password"]').val('');
                    }
                });
                
                // If third person is selected on page load, make sure fields are visible
                if (initialType === 'third_person') {
                    $('#third-person-fields').show();
                } else {
                    $('#third-person-fields').hide();
                }
            });
        </script>