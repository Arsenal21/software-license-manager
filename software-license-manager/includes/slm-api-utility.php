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

}