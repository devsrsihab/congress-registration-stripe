<?php 
global $wpdb;
$table_name = $wpdb->prefix . 'registration_types';
$registration_types = $wpdb->get_results("SELECT * FROM $table_name ORDER BY sort_order ASC");



// Check if $data exists and get values
$selected_type_id = isset($data) && isset($data['registration_type_id']) ? $data['registration_type_id'] : '';
$proof_file_name = isset($data) && isset($data['proof_file_name']) ? $data['proof_file_name'] : '';

// Helper function to check if proof is required
function crs_is_proof_required($type_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'registration_types';
    $result = $wpdb->get_var($wpdb->prepare(
        "SELECT is_proof_require FROM $table_name WHERE id = %d",
        $type_id
    ));
    return $result == 1;
}

ob_start();
?>
<div class="crs-step-content">
    <h2 class="crs-step-title"><?php _e('Registration Type', 'crscngres'); ?></h2>
    <p class="crs-step-description"><?php _e('Select the appropriate registration type', 'crscngres'); ?></p>
    
    <div class="crs-registration-types">
        <?php if (empty($registration_types)): ?>
            <div class="crs-error">No registration types found. Please add some in admin.</div>
        <?php else: ?>
            <?php foreach ($registration_types as $type): 
                $proof_text = $type->is_proof_require ? ' <span class="crs-proof-required">' . __('(proof required)', 'crscngres') . '</span>' : '';
                $checked = ($selected_type_id == $type->id) ? 'checked' : '';
                $selected_class = ($selected_type_id == $type->id) ? 'selected' : '';
            ?>
            <div class="crs-registration-option <?php echo $selected_class; ?>" 
                 data-id="<?php echo $type->id; ?>" 
                 data-price="<?php echo $type->price; ?>" 
                 data-proof="<?php echo $type->is_proof_require; ?>">
                <div class="crs-option-left">
                    <div class="crs-option-radio">
                        <div class="crs-radio-custom">
                            <input type="radio" name="registration_type_id" 
                                   value="<?php echo $type->id; ?>" 
                                   id="type_<?php echo $type->id; ?>" 
                                   <?php echo $checked; ?>>
                            <span class="crs-radio-mark"></span>
                        </div>
                        <label for="type_<?php echo $type->id; ?>">
                            <?php echo esc_html($type->name); ?><?php echo $proof_text; ?>
                        </label>
                    </div>
                </div>
                <div class="crs-option-price">
                    <?php echo $type->price == 0 ? __('Free', 'crscngres') : '€' . number_format($type->price, 0); ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Proof Upload Section -->
    <div class="crs-proof-upload" id="proof-upload-section" 
        style="<?php echo (!empty($selected_type_id) && crs_is_proof_required($selected_type_id)) ? 'display: block;' : 'display: none;'; ?>">
        <p class="crs-proof-label"><?php _e('Please provide proof that you are eligible to select this type of registration', 'crscngres'); ?></p>
        
        <div class="crs-file-upload">
            <input type="file" name="proof_file" id="proof_file" accept=".jpg,.jpeg,.png,.pdf" style="display: none;">
            <label for="proof_file" class="crs-file-label">
                <span class="crs-file-icon">📎</span>
                <span class="crs-file-text"><?php _e('Select file...', 'crscngres'); ?></span>
            </label>
            <span class="crs-file-name" id="file-name-display"><?php echo esc_html($proof_file_name); ?></span>
            <?php if (!empty($proof_file_name)): ?>
                <script>
                    jQuery(document).ready(function($) {
                        $('.crs-file-text').text('Change file...');
                    });
                </script>
            <?php endif; ?>
        </div>
        
        <!-- Hidden field to store file info for persistence -->
        <input type="hidden" id="proof_file_name_hidden" name="proof_file_name_hidden" value="<?php echo esc_attr($proof_file_name); ?>">
        <input type="hidden" id="proof_file_data_hidden" name="proof_file_data_hidden" value="">
    </div>

    <!-- Speaker Registration Option -->
    <div class="crs-speaker-option" style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
        <div class="crs-speaker-header">
            <h3 class="crs-speaker-title"><?php _e('Speaker Registration', 'crscngres'); ?></h3>
            <p class="crs-speaker-description"><?php _e('Are you registering as a speaker for this congress?', 'crscngres'); ?></p>
        </div>
        
        <div class="crs-speaker-radios" style="display: flex; gap: 20px; margin-top: 15px;">
            <label class="crs-speaker-radio-label" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="radio" name="is_speaker" value="Yes" id="speaker_yes" <?php echo isset($data['is_speaker']) && $data['is_speaker'] === 'Yes' ? 'checked' : ''; ?>>
                <span class="crs-radio-custom"></span>
                <span class="crs-speaker-text"><?php _e('Yes, I am a speaker', 'crscngres'); ?></span>
            </label>
            
            <label class="crs-speaker-radio-label" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="radio" name="is_speaker" value="No" id="speaker_no" <?php echo (!isset($data['is_speaker']) || $data['is_speaker'] === 'No') ? 'checked' : ''; ?>>
                <span class="crs-radio-custom"></span>
                <span class="crs-speaker-text"><?php _e('No, I am not a speaker', 'crscngres'); ?></span>
            </label>
        </div>
    </div>



</div>
<style>
/* Speaker Option Styles */
.crs-speaker-option {
    background: #f8fafc;
    border-radius: 12px;
    padding: 20px;
    margin-top: 25px;
}

.crs-speaker-title {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 5px 0;
}

.crs-speaker-description {
    font-size: 13px;
    color: #64748b;
    margin: 0;
}

.crs-speaker-radio-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    padding: 10px 15px;
    background: white;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    transition: all 0.2s;
}

.crs-speaker-radio-label:hover {
    border-color: #2271b1;
    background: #f0f9ff;
}

.crs-speaker-radio-label input[type="radio"] {
    width: 18px;
    height: 18px;
    margin: 0;
    cursor: pointer;
}

.crs-speaker-text {
    font-size: 14px;
    font-weight: 500;
    color: #1e293b;
}

/* Selected state for speaker radio */
.crs-speaker-radio-label:has(input:checked) {
    border-color: #2271b1;
    background: #e6f0fa;
}
</style>
<script>
jQuery(document).ready(function($) {
    // Restore file info from sessionStorage when page loads
    restoreFileInfo();
    
    // Click on entire option selects the radio
    $('.crs-registration-option').on('click', function(e) {
        // Don't trigger if clicking on file input
        if ($(e.target).is('input[type="file"]') || $(e.target).is('.crs-file-label')) {
            return;
        }
        
        var radio = $(this).find('input[type="radio"]');
        radio.prop('checked', true);
        
        // Remove selected class from all options
        $('.crs-registration-option').removeClass('selected');
        $(this).addClass('selected');
        
        // Check if proof is required
        var proofRequired = $(this).data('proof');
        if (proofRequired == 1) {
            $('#proof-upload-section').slideDown();
            // Restore file info when showing section
            restoreFileInfo();
        } else {
            $('#proof-upload-section').slideUp();
            // Clear file input and storage
            clearFileData();
        }
    });
    
    // Also trigger on radio click (for accessibility)
    $('.crs-registration-option input[type="radio"]').on('click', function(e) {
        e.stopPropagation();
        var $option = $(this).closest('.crs-registration-option');
        
        $('.crs-registration-option').removeClass('selected');
        $option.addClass('selected');
        
        var proofRequired = $option.data('proof');
        if (proofRequired == 1) {
            $('#proof-upload-section').slideDown();
            restoreFileInfo();
        } else {
            $('#proof-upload-section').slideUp();
            clearFileData();
        }
    });
    
    // File input change handler
    $('#proof_file').on('change', function() {
        var file = this.files[0];
        if (file) {
            window.proofFile = file;
            
            var reader = new FileReader();
            reader.onload = function(e) {
                sessionStorage.setItem('crs_proof_file_data', e.target.result);
                sessionStorage.setItem('crs_proof_file_name', file.name);
                sessionStorage.setItem('crs_proof_file_type', file.type);
                
                // UI feedback
                $('.crs-file-name').text(file.name);
                $('.crs-file-text').text('Change file...');
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Function to restore file info
    function restoreFileInfo() {
        var fileName = sessionStorage.getItem('crs_proof_file_name');
        var fileData = sessionStorage.getItem('crs_proof_file_data');
        
        if (fileName && fileData) {
            $('.crs-file-name').text(fileName);
            $('.crs-file-text').text('Change file...');
            
            // Also restore the file object if needed
            if (!window.proofFile) {
                try {
                    var byteString = atob(fileData.split(',')[1]);
                    var mimeType = fileData.split(',')[0].split(':')[1].split(';')[0];
                    var ab = new ArrayBuffer(byteString.length);
                    var ia = new Uint8Array(ab);
                    for (var i = 0; i < byteString.length; i++) {
                        ia[i] = byteString.charCodeAt(i);
                    }
                    var blob = new Blob([ab], { type: mimeType });
                    window.proofFile = new File([blob], fileName, { type: mimeType });
                } catch(e) {
                    console.log('Error restoring file:', e);
                }
            }
        }
    }
    
    // Function to clear file data
    function clearFileData() {
        $('#proof_file').val('');
        $('.crs-file-name').text('');
        $('.crs-file-text').text('Select file...');
        
        // Clear sessionStorage
        sessionStorage.removeItem('crs_proof_file_data');
        sessionStorage.removeItem('crs_proof_file_name');
        sessionStorage.removeItem('crs_proof_file_type');
        
        // Clear hidden fields
        $('#proof_file_name_hidden').val('');
        $('#proof_file_data_hidden').val('');
        
        window.proofFile = null;
    }
    
    // Custom styling for file label
    $('.crs-file-label').on('click', function() {
        $(this).css('transform', 'scale(0.98)');
        setTimeout(() => {
            $(this).css('transform', 'scale(1)');
        }, 100);
    });
    
    // Save file data to sessionStorage when leaving step 3
    $(document).on('click', '#crs-next-step', function() {
        var currentStep = <?php echo isset($_GET['step']) ? intval($_GET['step']) : 1; ?>;
        if (currentStep === 3 && window.proofFile) {
            // Ensure file is saved in sessionStorage
            var reader = new FileReader();
            reader.onload = function(e) {
                sessionStorage.setItem('crs_proof_file_data', e.target.result);
                sessionStorage.setItem('crs_proof_file_name', window.proofFile.name);
                sessionStorage.setItem('crs_proof_file_type', window.proofFile.type);
            };
            reader.readAsDataURL(window.proofFile);
        }
    });
    
    // Restore file name if exists from previous session
    <?php if (!empty($proof_file_name)): ?>
    // File name already set in HTML
    <?php endif; ?>
});
</script>
