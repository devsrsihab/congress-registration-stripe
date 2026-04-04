<?php 


global $wpdb;

// Get speaker status from data
$is_speaker = isset($data['is_speaker']) && $data['is_speaker'] === 'Yes' ? true : false;
error_log("Step 4 - Speaker status: " . ($is_speaker ? 'Yes' : 'No'));
error_log("Step 4 - Data: " . print_r($data, true));

// Use the correct table
$table_name = $wpdb->prefix . 'jet_rel_default';
$related_hotels = array();

// Get column names
$columns = $wpdb->get_results("SHOW COLUMNS FROM {$table_name}");
$parent_col = '';
$child_col = '';

foreach ($columns as $column) {
    if (in_array($column->Field, ['parent_object_id', 'parent_id', 'from_object_id'])) {
        $parent_col = $column->Field;
    }
    if (in_array($column->Field, ['child_object_id', 'child_id', 'to_object_id'])) {
        $child_col = $column->Field;
    }
}

if ($parent_col && $child_col) {
    // Try to get child IDs (hotels) where parent is congress
    $related_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT {$child_col} FROM {$table_name} WHERE {$parent_col} = %d",
        $congress_id
    ));
    
    // If no results, try reverse
    if (empty($related_ids)) {
        $related_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT {$parent_col} FROM {$table_name} WHERE {$child_col} = %d",
            $congress_id
        ));
    }
    
    if (!empty($related_ids)) {
        $related_hotels = get_posts(array(
            'post_type' => 'hotels',
            'post__in' => $related_ids,
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
    }
}

// Filter hotels based on speaker status
$filtered_hotels = array();
foreach ($related_hotels as $hotel) {
    $only_for_speaker = get_post_meta($hotel->ID, 'only_for_speaker', true);
    error_log("Step 4 - Hotel ID: {$hotel->ID}, only_for_speaker: {$only_for_speaker}");
    
    // If hotel is only for speakers and user is NOT speaker, skip this hotel
    if ($only_for_speaker == 'Yes' && !$is_speaker) {
        error_log("Step 4 - Skipping hotel {$hotel->ID} - speaker only");
        continue;
    }
    
    $filtered_hotels[] = $hotel;
}

// Replace related_hotels with filtered list
$related_hotels = $filtered_hotels;
error_log("Step 4 - Filtered hotels count: " . count($related_hotels));

// Get congress dates
$start_date = get_post_meta($congress_id, 'start_date', true);
$end_date = get_post_meta($congress_id, 'end_date', true);

// Convert dates to timestamps
$start_timestamp = strtotime($start_date);
$end_timestamp = strtotime($end_date);

error_log("Step 4 - Congress dates: {$start_date} to {$end_date}");

// Get current month from start date
$current_month = date('n', $start_timestamp);
$current_year = date('Y', $start_timestamp);

// Create array of allowed dates
$allowed_dates = array();
$current = $start_timestamp;
while ($current <= $end_timestamp) {
    $allowed_dates[] = date('Y-m-d', $current);
    $current = strtotime('+1 day', $current);
}

ob_start();











    ?>
    <div class="crs-step-content">
        <h2 class="crs-step-title"><?php _e('Accommodation', 'crscngres'); ?></h2>
        <p class="crs-step-description"><?php _e('Select your preferred hotel for the dates of the congress:', 'crscngres'); ?></p>
        
        <div class="crs-hotels-list">
            <?php if (!empty($related_hotels)): ?>
                <?php foreach ($related_hotels as $hotel): 
                    $price_per_night = get_post_meta($hotel->ID, 'price_per_night', true);
                    $address = get_post_meta($hotel->ID, 'address', true);
                    $only_for_speaker = get_post_meta($hotel->ID, 'only_for_speaker', true);
                    $available = get_post_meta($hotel->ID, 'available', true);
                    
                    if ($available == 'Inactive') continue;
                ?>
                <div class="crs-hotel-option" data-id="<?php echo $hotel->ID; ?>" data-price="<?php echo $price_per_night; ?>">
                    <div class="crs-hotel-radio">
                        <input type="radio" name="hotel_id" value="<?php echo $hotel->ID; ?>" id="hotel_<?php echo $hotel->ID; ?>">
                        <label for="hotel_<?php echo $hotel->ID; ?>">
                            <strong><?php echo get_the_title($hotel); ?></strong>
                            <?php if (!empty($address)): ?>
                                <span class="crs-hotel-address"><?php echo esc_html($address); ?></span>
                            <?php endif; ?>
                        </label>
                    </div>
                    <div class="crs-hotel-price"><?php printf(__('€%s/night', 'crscngres'), $price_per_night); ?></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <div class="crs-hotel-option" data-id="0">
                <div class="crs-hotel-radio">
                    <input type="radio" name="hotel_id" value="0" id="hotel_none" <?php echo empty($related_hotels) ? 'checked' : ''; ?>>
                    <label for="hotel_none"><?php _e('No accommodation', 'crscngres'); ?></label>
                </div>
            </div>
        </div>
        
        <div class="crs-date-selection" style="display: none;">
            <div class="crs-calendar-header">
                <h3 class="crs-calendar-title"><?php _e('Select your dates', 'crscngres'); ?></h3>
                <p class="crs-calendar-range"><?php printf(__('Congress dates: %s - %s', 'crscngres'), date('F j, Y', $start_timestamp), date('F j, Y', $end_timestamp)); ?></p>
            </div>
            
            <div class="crs-calendar-container">
                <div class="crs-calendar-month">
                    <div class="crs-month-header">
                        <button type="button" class="crs-month-nav" id="prev-month">←</button>
                        <span class="crs-month-year" id="month-year-display"><?php echo date('F Y', $start_timestamp); ?></span>
                        <button type="button" class="crs-month-nav" id="next-month">→</button>
                    </div>
                    
                    <div class="crs-weekdays">
                        <span><?php _e('SU', 'crscngres'); ?></span>
                        <span><?php _e('MO', 'crscngres'); ?></span>
                        <span><?php _e('TU', 'crscngres'); ?></span>
                        <span><?php _e('WE', 'crscngres'); ?></span>
                        <span><?php _e('TH', 'crscngres'); ?></span>
                        <span><?php _e('FR', 'crscngres'); ?></span>
                        <span><?php _e('SA', 'crscngres'); ?></span>
                    </div>
                    
                    <div class="crs-calendar-days" id="calendar-days">
                        <!-- Days will be populated by JavaScript -->
                    </div>
                    
                    <div class="crs-calendar-actions">
                        <button type="button" class="crs-calendar-clear"><?php _e('Clear', 'crscngres'); ?></button>
                    </div>
                </div>
                
                <!-- Step 4 HTML er moddhe ei part ta thakbe: -->
                <div class="crs-selected-dates">
                    <div class="crs-date-field">
                        <label class="crs-date-label"><?php _e('Entry date *', 'crscngres'); ?></label>
                        <input type="text" class="crs-date-input" id="check_in_display" readonly placeholder="<?php _e('MM/DD/YYYY', 'crscngres'); ?>" value="<?php echo isset($data['check_in_date']) ? date('d/m/Y', strtotime($data['check_in_date'])) : ''; ?>">
                        <input type="hidden" name="check_in_date" id="check_in_date" value="<?php echo isset($data['check_in_date']) ? $data['check_in_date'] : ''; ?>">
                    </div>
                    
                    <div class="crs-date-field">
                        <label class="crs-date-label"><?php _e('Departure date *', 'crscngres'); ?></label>
                        <input type="text" class="crs-date-input" id="check_out_display" readonly placeholder="<?php _e('MM/DD/YYYY', 'crscngres'); ?>" value="<?php echo isset($data['check_out_date']) ? date('d/m/Y', strtotime($data['check_out_date'])) : ''; ?>">
                        <input type="hidden" name="check_out_date" id="check_out_date" value="<?php echo isset($data['check_out_date']) ? $data['check_out_date'] : ''; ?>">
                    </div>
                </div>
            </div>
            
            <div class="crs-price-calculation" id="hotel-price-calculation">
                <?php if (isset($data['check_in_date']) && isset($data['check_out_date']) && isset($data['hotel_id']) && $data['hotel_id'] != 0): 
                    $check_in = strtotime($data['check_in_date']);
                    $check_out = strtotime($data['check_out_date']);
                    $nights = ceil(($check_out - $check_in) / (60 * 60 * 24));
                    $hotel_price = 0;
                    foreach ($related_hotels as $hotel) {
                        if ($hotel->ID == $data['hotel_id']) {
                            $hotel_price = get_post_meta($hotel->ID, 'price_per_night', true);
                            break;
                        }
                    }
                    $total = $nights * $hotel_price;
                    $nights_text = sprintf(_n('%s night', '%s nights', $nights, 'crscngres'), $nights);
                ?>
                <div class="crs-price-summary">
                    <span><?php echo $nights_text; ?> × <?php printf(__('€%s', 'crscngres'), $hotel_price); ?></span>
                    <strong><?php printf(__('Total: €%s', 'crscngres'), $total); ?></strong>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>



    <script>
            jQuery(document).ready(function($) {
                // Congress date range
                var startDate = '<?php echo $start_date; ?>';
                var endDate = '<?php echo $end_date; ?>';
                var startDateTime = new Date(startDate + 'T00:00:00');
                var endDateTime = new Date(endDate + 'T00:00:00');
                
                // Create array of allowed dates (between start and end)
                var allowedDates = [];
                <?php foreach ($allowed_dates as $date): ?>
                allowedDates.push('<?php echo $date; ?>');
                <?php endforeach; ?>
                
                // Current month/year for calendar (start from congress start date)
                var currentMonth = <?php echo $current_month; ?>;
                var currentYear = <?php echo $current_year; ?>;
                
                // Selected dates
                var selectedCheckIn = $('#check_in_date').val() || null;
                var selectedCheckOut = $('#check_out_date').val() || null;
                
                // Initialize calendar if hotel is selected
                if ($('input[name="hotel_id"]:checked').length > 0 && $('input[name="hotel_id"]:checked').val() !== '0') {
                    $('.crs-date-selection').show();
                    renderCalendar(currentMonth, currentYear);
                }
                
                // Hotel selection
                $('.crs-hotel-option').on('click', function() {
                    var radio = $(this).find('input[type="radio"]');
                    var newHotelId = radio.val();
                    var currentHotelId = $('input[name="hotel_id"]:checked').val();
                    
                    // If hotel is changing, reset all dates
                    if (currentHotelId !== newHotelId) {
                        // Reset date selections
                        selectedCheckIn = null;
                        selectedCheckOut = null;
                        
                        // Clear all date inputs
                        $('#check_in_date, #check_out_date, #check_in_display, #check_out_display').val('');
                        $('#hotel-price-calculation').empty();
                        
                        // Refresh calendar
                        if (typeof renderCalendar === 'function') {
                            renderCalendar(currentMonth, currentYear);
                        }
                    }
                    
                    // Update UI
                    radio.prop('checked', true);
                    $('.crs-hotel-option').removeClass('selected');
                    $(this).addClass('selected');
                    
                    // Show/hide date picker
                    if (newHotelId !== '0') {
                        $('.crs-date-selection').slideDown();
                    } else {
                        $('.crs-date-selection').slideUp();
                    }
                });
                
                // Calendar navigation
                $('#prev-month').on('click', function() {
                    if (currentMonth === 1) {
                        currentMonth = 12;
                        currentYear--;
                    } else {
                        currentMonth--;
                    }
                    renderCalendar(currentMonth, currentYear);
                });
                
                $('#next-month').on('click', function() {
                    if (currentMonth === 12) {
                        currentMonth = 1;
                        currentYear++;
                    } else {
                        currentMonth++;
                    }
                    renderCalendar(currentMonth, currentYear);
                });
                
                // Date selection - ONLY DATES BETWEEN START AND END CAN BE SELECTED
                $(document).on('click', '.crs-calendar-day:not(.disabled):not(.empty)', function() {
                    var clickedDate = $(this).data('date');
                    
                    // Check if clicked date is allowed (between start and end)
                    if (!allowedDates.includes(clickedDate)) {
                        return; // Date not allowed
                    }
                    
                    var clickedDateTime = new Date(clickedDate + 'T00:00:00');
                    
                    // If no check-in selected, set as check-in
                    if (!selectedCheckIn) {
                        selectedCheckIn = clickedDate;
                        
                        // UPDATE HIDDEN INPUT IMMEDIATELY
                        $('#check_in_date').val(clickedDate);
                        $('#check_in_display').val(formatDisplayDate(new Date(clickedDate)));
                        
                        renderCalendar(currentMonth, currentYear);
                    }
                    // If check-in selected but no check-out
                    else if (selectedCheckIn && !selectedCheckOut) {
                        var checkInTime = new Date(selectedCheckIn + 'T00:00:00');
                        
                        // If clicked date is after check-in, set as check-out (must be allowed)
                        if (clickedDateTime > checkInTime && allowedDates.includes(clickedDate)) {
                            selectedCheckOut = clickedDate;
                            
                            // UPDATE HIDDEN INPUT IMMEDIATELY
                            $('#check_out_date').val(clickedDate);
                            $('#check_out_display').val(formatDisplayDate(new Date(clickedDate)));
                            
                            renderCalendar(currentMonth, currentYear);
                            calculateHotelPrice();
                        }
                        // If clicked date is before check-in, set as new check-in
                        else if (clickedDateTime < checkInTime) {
                            selectedCheckIn = clickedDate;
                            
                            // UPDATE HIDDEN INPUT IMMEDIATELY
                            $('#check_in_date').val(clickedDate);
                            $('#check_in_display').val(formatDisplayDate(new Date(clickedDate)));
                            $('#check_out_date').val('');
                            $('#check_out_display').val('');
                            selectedCheckOut = null;
                            
                            renderCalendar(currentMonth, currentYear);
                        }
                    }
                    // If both selected, start new selection with clicked date
                    else {
                        selectedCheckIn = clickedDate;
                        selectedCheckOut = null;
                        
                        // UPDATE HIDDEN INPUT IMMEDIATELY
                        $('#check_in_date').val(clickedDate);
                        $('#check_in_display').val(formatDisplayDate(new Date(clickedDate)));
                        $('#check_out_date').val('');
                        $('#check_out_display').val('');
                        
                        renderCalendar(currentMonth, currentYear);
                        $('#hotel-price-calculation').empty();
                    }
                });
                
                // Clear dates
                $('.crs-calendar-clear').on('click', function() {
                    clearDates();
                    renderCalendar(currentMonth, currentYear);
                });
                
                function renderCalendar(month, year) {
                    var firstDay = new Date(year, month - 1, 1);
                    var lastDay = new Date(year, month, 0);
                    var startingDay = firstDay.getDay(); // 0 = Sunday
                    var totalDays = lastDay.getDate();
                    
                    var html = '';
                    
                    // Previous month days (empty)
                    for (var i = 0; i < startingDay; i++) {
                        html += '<div class="crs-calendar-day empty"></div>';
                    }
                    
                    // Current month days
                    for (var day = 1; day <= totalDays; day++) {
                        var currentDate = new Date(year, month - 1, day);
                        currentDate.setHours(0, 0, 0, 0);
                        
                        var yearStr = currentDate.getFullYear();
                        var monthStr = String(currentDate.getMonth() + 1).padStart(2, '0');
                        var dayStr = String(day).padStart(2, '0');
                        var dateStr = yearStr + '-' + monthStr + '-' + dayStr;
                        
                        // Check if date is within congress range (allowed)
                        var isAllowed = allowedDates.includes(dateStr);
                        
                        // Check if date is selected
                        var isCheckIn = selectedCheckIn === dateStr;
                        var isCheckOut = selectedCheckOut === dateStr;
                        var isSelected = isCheckIn || isCheckOut;
                        
                        // Check if date is in between check-in and check-out
                        var isInBetween = false;
                        if (selectedCheckIn && selectedCheckOut) {
                            var checkInTime = new Date(selectedCheckIn + 'T00:00:00');
                            var checkOutTime = new Date(selectedCheckOut + 'T00:00:00');
                            isInBetween = currentDate > checkInTime && currentDate < checkOutTime;
                        }
                        
                        var classes = 'crs-calendar-day';
                        if (!isAllowed) classes += ' disabled';
                        if (isSelected) classes += ' selected';
                        if (isInBetween) classes += ' in-range';
                        
                        html += '<div class="' + classes + '" data-date="' + dateStr + '">' + day + '</div>';
                    }
                    
                    $('#calendar-days').html(html);
                    
                    // Update month/year display
                    var monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                                    'July', 'August', 'September', 'October', 'November', 'December'];
                    $('#month-year-display').text(monthNames[month - 1] + ' ' + year);
                    
                    // Disable navigation buttons if going outside congress date range
                    var firstOfMonth = new Date(year, month - 1, 1);
                    var lastOfMonth = new Date(year, month, 0);
                    
                    // Check if we can go to previous month (if it has any allowed dates)
                    var prevMonthLastDay = new Date(year, month - 1, 0);
                    var prevMonthHasAllowed = false;
                    for (var d = 1; d <= prevMonthLastDay.getDate(); d++) {
                        var checkDate = new Date(year, month - 2, d);
                        var checkDateStr = checkDate.getFullYear() + '-' + 
                                        String(checkDate.getMonth() + 1).padStart(2, '0') + '-' + 
                                        String(checkDate.getDate()).padStart(2, '0');
                        if (allowedDates.includes(checkDateStr)) {
                            prevMonthHasAllowed = true;
                            break;
                        }
                    }
                    $('#prev-month').prop('disabled', !prevMonthHasAllowed);
                    
                    // Check if we can go to next month
                    var nextMonthHasAllowed = false;
                    for (var d = 1; d <= 31; d++) {
                        var checkDate = new Date(year, month, d);
                        if (checkDate.getMonth() != month) break;
                        var checkDateStr = checkDate.getFullYear() + '-' + 
                                        String(checkDate.getMonth() + 1).padStart(2, '0') + '-' + 
                                        String(checkDate.getDate()).padStart(2, '0');
                        if (allowedDates.includes(checkDateStr)) {
                            nextMonthHasAllowed = true;
                            break;
                        }
                    }
                    $('#next-month').prop('disabled', !nextMonthHasAllowed);
                }
                
        function updateDateFields() {
            if (selectedCheckIn) {
                $('#check_in_date').val(selectedCheckIn);
                var checkInDate = new Date(selectedCheckIn + 'T00:00:00');
                $('#check_in_display').val(formatDisplayDate(checkInDate));
            } else {
                $('#check_in_date').val('');
                $('#check_in_display').val('');
            }
            
            if (selectedCheckOut) {
                $('#check_out_date').val(selectedCheckOut);
                var checkOutDate = new Date(selectedCheckOut + 'T00:00:00');
                $('#check_out_display').val(formatDisplayDate(checkOutDate));
            } else {
                $('#check_out_date').val('');
                $('#check_out_display').val('');
            }
        }
                
        function clearDates() {
            selectedCheckIn = null;
            selectedCheckOut = null;
            
            // CLEAR HIDDEN INPUTS
            $('#check_in_date').val('');
            $('#check_out_date').val('');
            $('#check_in_display').val('');
            $('#check_out_display').val('');
            
            $('#hotel-price-calculation').empty();
        }
                
                function calculateHotelPrice() {
                    if (!selectedCheckIn || !selectedCheckOut) {
                        return;
                    }
                    
                    var selectedHotel = $('input[name="hotel_id"]:checked');
                    if (!selectedHotel.length || selectedHotel.val() === '0') {
                        return;
                    }
                    
                    var checkInDate = new Date(selectedCheckIn + 'T00:00:00');
                    var checkOutDate = new Date(selectedCheckOut + 'T00:00:00');
                    var nights = Math.ceil((checkOutDate - checkInDate) / (1000 * 60 * 60 * 24));
                    
                    var pricePerNight = parseFloat(selectedHotel.closest('.crs-hotel-option').data('price'));
                    var total = nights * pricePerNight;
                    
                    var nightsText = nights === 1 ? 'night' : 'nights';
                    
                    $('#hotel-price-calculation').html(
                        '<div class="crs-price-summary">' +
                            '<span>' + nights + ' ' + nightsText + ' × €' + pricePerNight.toFixed(0) + '</span>' +
                            '<strong>Total: €' + total.toFixed(0) + '</strong>' +
                        '</div>'
                    );
                }
                
                function formatDisplayDate(date) {
                    var month = String(date.getMonth() + 1).padStart(2, '0');
                    var day = String(date.getDate()).padStart(2, '0');
                    var year = date.getFullYear();
                    return day + '/' + month + '/' + year;
                }
                
                // Restore selected hotel if exists
                <?php if (isset($data['hotel_id']) && $data['hotel_id'] != 0): ?>
                $('input[name="hotel_id"][value="<?php echo $data['hotel_id']; ?>"]').prop('checked', true);
                $('.crs-hotel-option').removeClass('selected');
                $('input[name="hotel_id"][value="<?php echo $data['hotel_id']; ?>"]').closest('.crs-hotel-option').addClass('selected');
                $('.crs-date-selection').show();
                renderCalendar(currentMonth, currentYear);
                <?php endif; ?>
                
                // Trigger calculation if both dates are already selected
                if (selectedCheckIn && selectedCheckOut) {
                    calculateHotelPrice();
                }
            });
    </script>