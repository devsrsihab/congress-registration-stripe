<?php
$current_step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$congress = get_post($congress_id);
$start_date = get_post_meta($congress_id, 'start_date', true);
$end_date = get_post_meta($congress_id, 'end_date', true);
$location = get_post_meta($congress_id, 'location', true);
$congress_meals = get_post_meta($congress_id, 'congress_meals', true);
$congress_documents = get_post_meta($congress_id, 'congress_documents', true);
$congress_workshop = get_post_meta($congress_id, 'congress_workshop', true);



// Get related hotels from JetEngine relation
$related_hotels = array();
$related_ids = array();

global $wpdb;

// Use the correct table: wp_jet_rel_default
$table_name = $wpdb->prefix . 'jet_rel_default';

// Check what columns are in this table
$columns = $wpdb->get_results("SHOW COLUMNS FROM {$table_name}");
$column_names = array();
// foreach ($columns as $column) {
//     $column_names[] = $column->Field;
// }

// // Debug - show table structure
// echo '<div style="background: #f0f0f0; padding: 15px; margin: 10px 0; border-left: 4px solid #007cba; font-family: monospace;">';
// echo '<strong style="font-size: 16px;">🔍 JetEngine Relation Debug:</strong><br><br>';
// echo 'Table: <strong>' . $table_name . '</strong><br>';
// echo 'Columns: <strong>' . implode(', ', $column_names) . '</strong><br><br>';

// // Try different possible column names to find the relation
// $possible_parent_columns = ['parent_object_id', 'parent_id', 'from_object_id', 'from_id'];
// $possible_child_columns = ['child_object_id', 'child_id', 'to_object_id', 'to_id'];
// $possible_rel_columns = ['rel_id', 'relation_id', 'type'];

// $parent_col = '';
// $child_col = '';
// $rel_col = '';

// // Find the correct column names
// foreach ($column_names as $col) {
//     if (in_array($col, $possible_parent_columns)) {
//         $parent_col = $col;
//     }
//     if (in_array($col, $possible_child_columns)) {
//         $child_col = $col;
//     }
//     if (in_array($col, $possible_rel_columns)) {
//         $rel_col = $col;
//     }
// }

// echo 'Parent Column: <strong>' . ($parent_col ?: 'Not found') . '</strong><br>';
// echo 'Child Column: <strong>' . ($child_col ?: 'Not found') . '</strong><br>';
// echo 'Relation Column: <strong>' . ($rel_col ?: 'Not found') . '</strong><br><br>';

// // Build query based on found columns
// if ($parent_col && $child_col) {
//     $sql = "SELECT {$child_col} FROM {$table_name} WHERE {$parent_col} = %d";
//     $params = [$congress_id];
    
//     // Add relation filter if column exists
//     if ($rel_col) {
//         $sql .= " AND {$rel_col} = %s";
//         $params[] = '3'; // Your relation ID as string
//     }
    
//     echo 'SQL Query: <strong>' . $wpdb->prepare($sql, $params) . '</strong><br>';
    
//     $related_ids = $wpdb->get_col($wpdb->prepare($sql, $params));
    
//     echo 'Raw Results: <strong>' . (!empty($related_ids) ? implode(', ', $related_ids) : 'No results') . '</strong><br><br>';
    
//     // Also try the reverse (maybe hotels are parents and congress is child)
//     if (empty($related_ids)) {
//         $sql_reverse = "SELECT {$parent_col} FROM {$table_name} WHERE {$child_col} = %d";
//         $params_reverse = [$congress_id];
        
//         if ($rel_col) {
//             $sql_reverse .= " AND {$rel_col} = %s";
//             $params_reverse[] = '3';
//         }
        
//         echo 'Reverse SQL: <strong>' . $wpdb->prepare($sql_reverse, $params_reverse) . '</strong><br>';
        
//         $related_ids = $wpdb->get_col($wpdb->prepare($sql_reverse, $params_reverse));
//         echo 'Reverse Results: <strong>' . (!empty($related_ids) ? implode(', ', $related_ids) : 'No results') . '</strong><br>';
//     }
// }

// // Get the actual hotel posts
// if (!empty($related_ids)) {
//     $related_hotels = get_posts(array(
//         'post_type' => 'hotels',
//         'post__in' => $related_ids,
//         'posts_per_page' => -1,
//         'post_status' => 'publish'
//     ));
// }

// echo '<br><strong>Final Results:</strong><br>';
// echo 'Related IDs Found: <strong>' . (!empty($related_ids) ? implode(', ', $related_ids) : 'None') . '</strong><br>';
// echo 'Hotels Found: <strong>' . count($related_hotels) . '</strong><br>';

// if (!empty($related_hotels)) {
//     echo '<br><strong>Hotel List:</strong><br>';
//     echo '<ul>';
//     foreach ($related_hotels as $hotel) {
//         $price = get_post_meta($hotel->ID, 'price_per_night', true);
//         $available = get_post_meta($hotel->ID, 'available', true);
//         echo '<li>🏨 ' . $hotel->post_title . ' (ID: ' . $hotel->ID . ') - €' . $price . '/night - Status: ' . $available . '</li>';
//     }
//     echo '</ul>';
// }
// echo '</div>';
?>

<div class="crs-container">
    <!-- Header -->
    <div class="crs-header">
        <h1 class="crs-title">Registration: <?php echo get_the_title($congress); ?></h1>
        <p class="crs-subtitle">
            <?php echo date_i18n('F j, Y', strtotime($start_date)); ?> - 
            <?php echo date_i18n('F j, Y', strtotime($end_date)); ?> · 
            <?php echo esc_html($location); ?>
        </p>
    </div>
    
    <!-- Step Indicator -->
    <div class="crs-steps">
        <!-- Desktop Steps -->
        <div class="crs-steps-desktop">
            <?php
            $steps = [
                1 => 'Start',
                2 => 'Data',
                3 => 'Type',
                4 => 'Hotel',
                5 => 'Meals',
                6 => 'Workshops',
                7 => 'Others',
                8 => 'Summary'
            ];
            
            foreach ($steps as $step_num => $step_name):
                $marker_class = 'crs-step-marker';
                $connector_class = 'crs-step-connector';
                $label_class = 'crs-step-label';
                
                if ($step_num < $current_step) {
                    $marker_class .= ' completed';
                    $connector_class .= ' completed';
                } elseif ($step_num == $current_step) {
                    $marker_class .= ' active';
                    $label_class .= ' active';
                }
            ?>
            <div class="crs-step-item">
                <button type="button" class="<?php echo $marker_class; ?>" data-step="<?php echo $step_num; ?>">
                    <?php if ($step_num < $current_step): ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5"></path>
                        </svg>
                    <?php else: ?>
                        <span><?php echo $step_num; ?></span>
                    <?php endif; ?>
                </button>
                <span class="<?php echo $label_class; ?>"><?php echo $step_name; ?></span>
            </div>
            <?php if ($step_num < 8): ?>
            <div class="<?php echo $connector_class; ?>"></div>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
        
        <!-- Mobile Steps -->
        <div class="crs-steps-mobile">
            <div class="crs-steps-header">
                <span class="crs-step-counter">Step <?php echo $current_step; ?> of 8</span>
                <span class="crs-current-step"><?php echo $steps[$current_step]; ?></span>
            </div>
            <div class="crs-progress-bar">
                <?php for ($i = 1; $i <= 8; $i++): ?>
                <button type="button" 
                        class="crs-progress-step <?php echo $i < $current_step ? 'completed' : ($i == $current_step ? '' : ''); ?>"
                        data-step="<?php echo $i; ?>">
                </button>
                <?php endfor; ?>
            </div>
        </div>
    </div>
    
    <!-- Main Form Card -->
    <div class="crs-card">
        <form id="crs-registration-form" method="post">
            <input type="hidden" name="congress_id" id="congress_id" value="<?php echo $congress_id; ?>">
            <div id="crs-step-container" class="crs-fade-in">
                <!-- Content loaded via AJAX -->
                <div class="crs-loading">Loading...</div>
            </div>
        </form>
        
            <!-- Navigation Buttons -->
<div class="crs-navigation">
    <button type="button" class="crs-btn crs-btn-secondary" id="crs-prev-step" style="<?php echo ($current_step <= 1) ? 'display: none;' : ''; ?>">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5"></path>
            <path d="M12 19l-7-7 7-7"></path>
        </svg>
        Back
    </button>
    
    <button type="button" class="crs-btn crs-btn-primary" id="crs-next-step" style="<?php echo ($current_step >= 8) ? 'display: none;' : ''; ?>">
        Continue
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M5 12h14"></path>
            <path d="M12 5l7 7-7 7"></path>
        </svg>
    </button>
</div>
    </div>
</div>