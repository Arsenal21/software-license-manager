<?php

/*
 * This class listens for API query and executes the API requests
 * Available API Actions
 * 1) slm_create_new
 * 2) slm_activate
 * 3) slm_deactivate
 * 4) slm_check
 */

class SLM_API_Listener {

    function __construct() {

        if (isset($_REQUEST['slm_action']) && isset($_REQUEST['secret_key'])) {
            //This is an API query for the license manager. Handle the query.
            $this->creation_api_listener();
            $this->activation_api_listener();
            $this->deactivation_api_listener();
            $this->check_api_listener();
        }
    }

    function creation_api_listener() {
        if (isset($_REQUEST['slm_action']) && trim($_REQUEST['slm_action']) == 'slm_create_new') {
            //Handle the licene creation API query
            global $slm_debug_logger;

            $options = get_option('slm_plugin_options');
            $lic_key_prefix = $options['lic_prefix'];

            SLM_API_Utility::verify_secret_key_for_creation(); //Verify the secret key first.

            $slm_debug_logger->log_debug("API - license creation (slm_create_new) request received.");

            //Action hook
            do_action('slm_api_listener_slm_create_new');

            $fields = array();
            if (isset($_REQUEST['license_key']) && !empty($_REQUEST['license_key'])){
                $fields['license_key'] = strip_tags($_REQUEST['license_key']);//Use the key you pass via the request
            }else{
                $fields['license_key'] = uniqid($lic_key_prefix);//Use random generated key
            }
            $fields['lic_status'] = 'pending';
            $fields['first_name'] = wp_unslash(strip_tags($_REQUEST['first_name']));
            $fields['last_name'] = wp_unslash(strip_tags($_REQUEST['last_name']));
            $fields['email'] = strip_tags($_REQUEST['email']);
            $fields['company_name'] = wp_unslash(strip_tags($_REQUEST['company_name']));
            $fields['txn_id'] = strip_tags($_REQUEST['txn_id']);

            if (empty($_REQUEST['max_allowed_domains'])) {
                $fields['max_allowed_domains'] = $options['default_max_domains'];
            } else {
                $fields['max_allowed_domains'] = strip_tags($_REQUEST['max_allowed_domains']);
            }
            if (empty($_REQUEST['max_allowed_devices'])) {
                $fields['max_allowed_devices'] = $options['default_max_devices'];
            } else {
                $fields['max_allowed_devices'] = strip_tags($_REQUEST['max_allowed_devices']);
            }
            $fields['date_created'] = isset($_REQUEST['date_created'])?strip_tags($_REQUEST['date_created']):date("Y-m-d");
            $fields['date_expiry'] = isset($_REQUEST['date_expiry'])?strip_tags($_REQUEST['date_expiry']):'';

            global $wpdb;
            $tbl_name = SLM_TBL_LICENSE_KEYS;
            $result = $wpdb->insert($tbl_name, $fields);
            if ($result === false) {
                //error inserting
                $args = (array('result' => 'error', 'message' => 'License creation failed', 'error_code' => SLM_Error_Codes::CREATE_FAILED));
                SLM_API_Utility::output_api_response($args);
            } else {
                $args = (array('result' => 'success', 'message' => 'License successfully created', 'key' => $fields['license_key']));
                SLM_API_Utility::output_api_response($args);
            }
        }
    }

    /*
     * Query Parameters
     * 1) slm_action = slm_create_new
     * 2) secret_key
     * 3) license_key
     * 4) registered_domain (optional)
     */

    function activation_api_listener() {
        if (isset($_REQUEST['slm_action']) && trim($_REQUEST['slm_action']) == 'slm_activate') {
            //Handle the license activation API query
            global $slm_debug_logger;

            SLM_API_Utility::verify_secret_key(); //Verify the secret key first.

            $slm_debug_logger->log_debug("API - license activation (slm_activate) request received.");

            //Action hook
            do_action('slm_api_listener_slm_activate');

            $fields = array();
            $fields['lic_key']              = trim(strip_tags($_REQUEST['license_key']));
            $fields['registered_domain']    = trim(wp_unslash(strip_tags($_REQUEST['registered_domain']))); //gethostbyaddr($_SERVER['REMOTE_ADDR']);
            $fields['registered_devices']   = trim(wp_unslash(strip_tags($_REQUEST['registered_devices']))); //client ip or machine name
            $fields['item_reference']       = trim(strip_tags($_REQUEST['item_reference']));

            $slm_debug_logger->log_debug("License key: " . $fields['lic_key'] . " Domain: " . $fields['registered_domain']);
            $slm_debug_logger->log_debug("License key: " . $fields['lic_key'] . " Device: " . $fields['registered_devices']);

            global $wpdb;
            $tbl_name           = SLM_TBL_LICENSE_KEYS;
            $reg_table          = SLM_TBL_LIC_DOMAIN;
            $reg_table_devices  = SLM_TBL_LIC_DEVICES;

            $key                = $fields['lic_key'];

            // $sql_prep0          = $wpdb->prepare("SELECT * FROM $tbl_name WHERE license_key = %s", $key);
            $sql_prep1          = $wpdb->prepare("SELECT * FROM $tbl_name WHERE license_key = %s", $key);
            $retLic             = $wpdb->get_row($sql_prep1, OBJECT);
            // $retLicDevices      = $wpdb->get_row($sql_prep0, OBJECT);

            $sql_prep2          = $wpdb->prepare("SELECT * FROM $reg_table WHERE lic_key = %s", $key);
            $reg_domains        = $wpdb->get_results($sql_prep2, OBJECT);

            $sql_prep3          = $wpdb->prepare("SELECT * FROM $reg_table_devices WHERE lic_key = %s", $key);
            $reg_devices        = $wpdb->get_results($sql_prep3, OBJECT);

            if ($retLic) {
                if ($retLic->lic_status == 'blocked') {
                    $args = (array('result' => 'error', 'message' => 'Your License key is blocked', 'error_code' => SLM_Error_Codes::LICENSE_BLOCKED));
                    SLM_API_Utility::output_api_response($args);
                }
                elseif ($retLic->lic_status == 'expired') {
                    $args = (array('result' => 'error', 'message' => 'Your License key has expired', 'error_code' => SLM_Error_Codes::LICENSE_EXPIRED));
                    SLM_API_Utility::output_api_response($args);
                }

                if (isset($_REQUEST['registered_domain']) && !empty($_REQUEST['registered_domain'])) {
                    if (count($reg_domains) < floor($retLic->max_allowed_domains)) {
                        foreach ($reg_domains as $reg_domain) {
                            if (isset($_REQUEST['migrate_from']) && (trim($_REQUEST['migrate_from']) == $reg_domain->registered_domain)) {
                                $wpdb->update($reg_table, array('registered_domain' => $fields['registered_domain']), array('registered_domain' => trim(strip_tags($_REQUEST['migrate_from']))));
                                $args = (array('result' => 'success', 'message' => 'Registered domain has been updated'));
                                SLM_API_Utility::output_api_response($args);
                            }
                            if ($fields['registered_domain'] == $reg_domain->registered_domain) {
                                $args = (array('result' => 'error', 'message' => 'License key already in use on ' . $reg_domain->registered_domain, 'error_code' => SLM_Error_Codes::LICENSE_IN_USE));
                                SLM_API_Utility::output_api_response($args);
                            }
                        }

                        $fields['lic_key_id'] = $retLic->id;
                        $wpdb->insert($reg_table, $fields);

                        $slm_debug_logger->log_debug("Updating license key status to active for domain.");
                        $data = array('lic_status' => 'active');
                        $where = array('id' => $retLic->id);
                        $updated = $wpdb->update($tbl_name, $data, $where);

                        $args = (array('result' => 'success', 'message' => 'License key activated'));
                        SLM_API_Utility::output_api_response($args);
                    }
                    else {
                        $args = (array('result' => 'error', 'message' => 'Reached maximum allowable domains', 'error_code' => SLM_Error_Codes::REACHED_MAX_DOMAINS));
                        SLM_API_Utility::output_api_response($args);
                    }
                }


                if (isset($_REQUEST['registered_devices']) && !empty($_REQUEST['registered_devices'])) {

                    if (count($reg_devices) < floor($retLic->max_allowed_devices)) {

                        foreach ($reg_devices as $reg_devices) {
                            if (isset($_REQUEST['migrate_from']) && (trim($_REQUEST['migrate_from']) == $reg_devices->registered_devices)) {

                                $wpdb->update($reg_table_devices, array('registered_devices' => $fields['registered_devices']), array('registered_devices' => trim(strip_tags($_REQUEST['migrate_from']))));
                                $devices_args = (array('result' => 'success', 'message' => 'Registered device has been updated'));
                                SLM_API_Utility::output_api_response($devices_args);
                            }
                            if ($fields['registered_devices'] == $reg_devices->registered_devices) {
                                $devices_args = (array('result' => 'error', 'message' => 'License key already in use on ' . $reg_devices->registered_devices, 'error_code' => SLM_Error_Codes::LICENSE_IN_USE));
                                SLM_API_Utility::output_api_response($devices_args);
                            }
                        }

                        $fields['lic_key_id'] = $retLic->id;
                        $wpdb->insert($reg_table_devices, $fields);

                        $slm_debug_logger->log_debug("Updating license key status to active for device.");
                        $data = array('lic_status' => 'active');
                        $where = array('id' => $retLic->id);
                        $updated = $wpdb->update($tbl_name, $data, $where);

                        $args = (array('result' => 'success', 'message' => 'License key activated'));
                        SLM_API_Utility::output_api_response($args);
                    }

                    else {
                        $args = (array('result' => 'error', 'message' => 'Reached maximum allowable devices', 'error_code' => SLM_Error_Codes::REACHED_MAX_DEVICES));
                        SLM_API_Utility::output_api_response($args);
                    }
                }

            } else {
                $args = (array('result' => 'error', 'message' => 'Invalid license key', 'error_code' => SLM_Error_Codes::LICENSE_INVALID));
                SLM_API_Utility::output_api_response($args);
            }
        }
    }

    // TODO : add support for devices deactivation

    function deactivation_api_listener() {
        if (isset($_REQUEST['slm_action']) && trim($_REQUEST['slm_action']) == 'slm_deactivate') {
            //Handle the license deactivation API query
            global $slm_debug_logger;

            SLM_API_Utility::verify_secret_key(); //Verify the secret key first.

            $slm_debug_logger->log_debug("API - license deactivation (slm_deactivate) request received.");

            //Action hook
            do_action('slm_api_listener_slm_deactivate');

            $registered_domain  = trim(wp_unslash(strip_tags($_REQUEST['registered_domain'])));
            $license_key        = trim(strip_tags($_REQUEST['license_key']));
            $slm_debug_logger->log_debug("License key: " . $license_key . " Domain: " . $registered_domain);
            $registered_devices  = trim(wp_unslash(strip_tags($_REQUEST['registered_devices'])));

            global $wpdb;

            if (isset($_REQUEST['registered_domain']) && !empty($_REQUEST['registered_domain'])) {
                if (empty($_REQUEST['registered_domain'])) {
                    $args = (array('result' => 'error', 'message' => 'Registered domain information is missing', 'error_code' => SLM_Error_Codes::DOMAIN_MISSING));
                    SLM_API_Utility::output_api_response($args);
                }
                else {
                    $registered_dom_table = SLM_TBL_LIC_DOMAIN;
                    $sql_prep = $wpdb->prepare("DELETE FROM $registered_dom_table WHERE lic_key=%s AND registered_domain=%s", $license_key, $registered_domain);
                    $delete = $wpdb->query($sql_prep);

                    if ($delete === false) {
                        $slm_debug_logger->log_debug("Error - failed to delete the registered domain from the database.");
                    }
                    else if ($delete == 0) {
                        $args = (array('result' => 'error', 'message' => 'The license key on this domain is already inactive', 'error_code' => SLM_Error_Codes::DOMAIN_ALREADY_INACTIVE));
                        SLM_API_Utility::output_api_response($args);
                    }
                    else {
                        $args = (array('result' => 'success', 'message' => 'The license key has been deactivated for this domain'));
                        SLM_API_Utility::output_api_response($args);
                    }
                }
            }
            if (isset($_REQUEST['registered_devices']) && !empty($_REQUEST['registered_devices'])) {
                // devices deactivation
                if (empty($_REQUEST['registered_devices'])) {
                    $args_ = (array('result' => 'error', 'message' => 'Registered device information is missing', 'error_code' => SLM_Error_Codes::DOMAIN_MISSING));
                    SLM_API_Utility::output_api_response($args_);
                }
                else {
                    $registered_device_table = SLM_TBL_LIC_DEVICES;
                    $sql_prep2 = $wpdb->prepare("DELETE FROM $registered_device_table WHERE lic_key=%s AND registered_devices=%s", $license_key, $registered_devices);
                    $delete2 = $wpdb->query($sql_prep2);

                    if ($delete2 === false) {
                        $slm_debug_logger->log_debug("Error - failed to delete the registered device from the database.");
                    }
                    else if ($delete2 == 0) {
                        $args_ = (array('result' => 'error', 'message' => 'The license key on this device is already inactive', 'error_code' => SLM_Error_Codes::DOMAIN_ALREADY_INACTIVE));
                        SLM_API_Utility::output_api_response($args_);
                    }
                    else {
                        $args_ = (array('result' => 'success', 'message' => 'The license key has been deactivated for this device'));
                        SLM_API_Utility::output_api_response($args_);
                    }
                }
            }
        }
    }

    function check_api_listener() {
        if (isset($_REQUEST['slm_action']) && trim($_REQUEST['slm_action']) == 'slm_check') {
            //Handle the license check API query
            global $slm_debug_logger;

            SLM_API_Utility::verify_secret_key(); //Verify the secret key first.

            $slm_debug_logger->log_debug("API - license check (slm_check) request received.");

            $fields = array();
            $fields['lic_key'] = trim(strip_tags($_REQUEST['license_key']));
            $slm_debug_logger->log_debug("License key: " . $fields['lic_key']);

            //Action hook
            do_action('slm_api_listener_slm_check');

            global $wpdb;
            $tbl_name           = SLM_TBL_LICENSE_KEYS;
            $reg_table          = SLM_TBL_LIC_DOMAIN;
            $reg_table_devices  = SLM_TBL_LIC_DEVICES;
            $key                = $fields['lic_key'];
            $sql_prep1          = $wpdb->prepare("SELECT * FROM $tbl_name WHERE license_key = %s", $key);
            $retLic             = $wpdb->get_row($sql_prep1, OBJECT);

            $sql_prep2 = $wpdb->prepare("SELECT * FROM $reg_table WHERE lic_key = %s", $key);
            $sql_prep3 = $wpdb->prepare("SELECT * FROM $reg_table_devices WHERE lic_key = %s", $key);
            $reg_domains = $wpdb->get_results($sql_prep2, OBJECT);
            $reg_devices = $wpdb->get_results($sql_prep3, OBJECT);
            if ($retLic) {//A license key exists
                $args = (array(
                    'result' => 'success',
                    'code' => '200',
                    'message' => 'License key details retrieved.',
                    'status' => $retLic->lic_status,
                    'max_allowed_domains' => $retLic->max_allowed_domains,
                    'max_allowed_devices' => $retLic->max_allowed_devices,
                    'email' => $retLic->email,
                    'first_name' => $retLic->first_name,
                    'last_name' => $retLic->last_name,
                    // 'until' => $retLic->until, //until what version license is supported
                    'registered_domains' => $reg_domains,
                    'registered_devices' => $reg_devices,
                    'license_key' => $retLic->license_key,
                    'date_created' => $retLic->date_created,
                    'date_renewed' => $retLic->date_renewed,
                    'date_expiry' => $retLic->date_expiry,
                ));
                //Output the license details
                SLM_API_Utility::output_api_response($args);
            }
            else {
                $args = (array('result' => 'error', 'message' => 'Invalid license key', 'error_code' => SLM_Error_Codes::LICENSE_INVALID));
                SLM_API_Utility::output_api_response($args);
            }
        }
    }

}
