
<?php 

class CRS_Steps {

    // constructor keep @congress_id and @data for future use
    public function __construct($congress_id, $data) {
        $this->congress_id = $congress_id;
        $this->data = $data;
    }

    // Step 1: Create a new campaign
    public static function step1() {

        ob_start();

        include CRS_PLUGIN_DIR . 'templates/form/step1.php';

        return ob_get_clean();
    }

    // Step 2: Configure campaign settings
    public static function step2($congress_id, $data) {

        ob_start();

        include CRS_PLUGIN_DIR . 'templates/form/step2.php';

        return ob_get_clean();
    }
    
    // Step 3: Design your campaign
    public static function step3($congress_id, $data) {
        ob_start();
        include CRS_PLUGIN_DIR . 'templates/form/step3.php';
        $html = ob_get_clean();
        
        // Debug
        error_log('CRS Steps step3: HTML length: ' . strlen($html));
        
        return $html;
    }

    // Step 4: Review and launch
    public static function step4($congress_id, $data) {
        
        ob_start();

        include CRS_PLUGIN_DIR . 'templates/form/step4.php';

        return ob_get_clean();
    }

    // step 5: Campaign management
    public static function step5($congress_id, $data) {
        
        ob_start();

        include CRS_PLUGIN_DIR . 'templates/form/step5.php';

        return ob_get_clean();
    }

    // step 6: Analytics and reporting
    public static function step6( $congress_id, $data) {
        
        ob_start();

        include CRS_PLUGIN_DIR . 'templates/form/step6.php';

        return ob_get_clean();
    }

    // step 7: Optimization and A/B testing
    public static function step7($congress_id, $data) {    
    
        ob_start();

        include CRS_PLUGIN_DIR . 'templates/form/step7.php';

        return ob_get_clean();
    }

    // step 8: Integrations and automation
    public static function step8($congress_id, $data) {
        
        ob_start();

        include CRS_PLUGIN_DIR . 'templates/form/step8.php';

        return ob_get_clean();
    }

}