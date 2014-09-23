<?php

class SLM_API_Utility {
    /*
     * The args array can contain the following:
     * result (success or error)
     * message (a message describing the outcome of the action
     */

    static function output_api_response($args) {
        echo json_encode($args);
        exit(0);
    }

    static function verify_secret_key() {
        $slm_options = get_option('slm_plugin_options');
        $right_secret_key = $slm_options['lic_verification_secret'];
        $received_secret_key = strip_tags($_REQUEST['secret_key']);
        if ($received_secret_key != $right_secret_key) {
            $args = (array('result' => 'error', 'message' => 'Verification API secret key is invalid'));
            SLM_API_Utility::output_api_response($args);
        }
    }
    
    /**
    * If debug is enabled and a log file exists in the root of the plugin directory, log the $data
    */
   public static function log($data) {
       //TODO - add option setting for debug
       //$debug = get_option('slm_enable_debug');
       $debug = TRUE; //harcoding for now
       if($debug) {
        $tz = '- Server time zone ' . date('T');
        $date = date('m/d/Y g:i:s a', self::localTs());
        $header = strpos($_SERVER['REQUEST_URI'], 'wp-admin') ? "\n\n======= ADMIN REQUEST =======\n[LOG DATE: $date $tz]\n" : "\n\n[LOG DATE: $date $tz]\n";
        $filename = WP_LICENSE_MANAGER_PATH . "/slm_log.txt"; 
        if(file_exists($filename) && is_writable($filename)) {
          file_put_contents($filename, $header . $data, FILE_APPEND);
        }
     }
  }
  
  public static function localTs($timestamp=null) {
    $timestamp = isset($timestamp) ? $timestamp : time();
    if(date('T') == 'UTC') {
      $timestamp += (get_option( 'gmt_offset' ) * 3600 );
    }
    return $timestamp;
  }
  


}