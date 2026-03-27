<?php

    ob_start();
    ?>
    <div class="crs-step-content">
        <h2 class="crs-step-title"><?php _e('Other Details', 'crscngres'); ?></h2>
        <p class="crs-step-description"><?php _e('Additional information to complete your registration', 'crscngres'); ?></p>
        
        <div class="crs-image-release-section">
            <h3 class="crs-section-subtitle"><?php _e('Image release *', 'crscngres'); ?></h3>
            <p class="crs-release-description">
                <?php _e('During the Congress, photos and videos will be taken to document the event and for media dissemination. To continue your registration, please check the following box. By doing so, you authorize the Congress organizers to use any images or videos in which you appear for these purposes.', 'crscngres'); ?>
            </p>
            
            <label class="crs-checkbox-label crs-required-checkbox">
                <input type="checkbox" name="image_release" id="image_release" value="1">
                <span class="crs-checkbox-custom"></span>
                <span class="crs-checkbox-text"><?php _e('I authorize the Congress organization to use images or videos in which I may appear.', 'crscngres'); ?></span>
            </label>
            <div class="crs-validation-message" id="image-release-error" style="display: none; color: #dc3545; font-size: 13px; margin-top: 5px;">
                <?php _e('You must authorize image use to continue', 'crscngres'); ?>
            </div>
        </div>
        
        <div class="crs-observations-section">
            <h3 class="crs-section-subtitle"><?php _e('Observations', 'crscngres'); ?></h3>
            <label class="crs-observations-label">
                <?php _e('Please let us know of any comments, opinions, or observations we should take into account regarding your registration:', 'crscngres'); ?>
            </label>
            <textarea class="crs-textarea" name="observations" rows="4" placeholder="<?php _e('Write your observations here...', 'crscngres'); ?>"></textarea>
        </div>
    </div>
    


    <script>
        jQuery(document).ready(function($) {
            // Function to validate step 7
            window.validateStep7 = function() {
                var isChecked = $('#image_release').is(':checked');
                
                if (!isChecked) {
                    $('#image-release-error').slideDown();
                    $('html, body').animate({
                        scrollTop: $('#image_release').offset().top - 100
                    }, 500);
                    return false;
                }
                
                $('#image-release-error').slideUp();
                return true;
            };
            
            // Remove error when checkbox is checked
            $('#image_release').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#image-release-error').slideUp();
                }
            });
            
            // Override next button click for step 7
            $(document).on('click', '#crs-next-step', function(e) {
                var currentStep = <?php echo isset($_GET['step']) ? intval($_GET['step']) : 1; ?>;
                
                if (currentStep === 7) {
                    if (!window.validateStep7()) {
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    }
                }
            });
        });
    </script>