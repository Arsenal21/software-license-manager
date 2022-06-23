<?php

/*
 * This class listens for API query and executes the API requests
 * Available API Actions
 * 1) slm_create_new
 * 2) slm_update
 * 3) slm_activate
 * 4) slm_deactivate
 * 5) slm_remove
 * 6) slm_check
 * 7) slm_info
 */

class SLM_API_Listener
{
    function __construct()
    {
        if (isset($_REQUEST['slm_action']) && isset($_REQUEST['secret_key'])) {

            //Trigger an action hook
            do_action('slm_api_listener_init');

            $this->creation_api_listener();
            $this->activation_api_listener();
            $this->deactivation_api_listener();
            $this->check_api_listener();
            $this->check_api_info();
            $this->update_api_listener();
        }
    }

    function creation_api_listener()
    {
        if (isset($_REQUEST['slm_action']) && trim($_REQUEST['slm_action']) == 'slm_create_new') {
            //Handle the licene creation API query
            global $slm_debug_logger, $wpdb;
            $tbl_name = SLM_TBL_LICENSE_KEYS;

            $options        = get_option('slm_plugin_options');
            $lic_key_prefix = $options['lic_prefix'];

            SLM_API_Utility::verify_secret_key_for_creation(); //Verify the secret key first.

            $slm_debug_logger->log_debug("API - license creation (slm_create_new) request received.");

            //Action hook
            do_action('slm_api_listener_slm_create_new');

            $fields = array();

            if (isset($_REQUEST['license_key']) && !empty($_REQUEST['license_key'])) {
                $fields['license_key']  = strip_tags($_REQUEST['license_key']); //Use the key you pass via the request
            } else {
                $fields['license_key']  = slm_get_license($lic_key_prefix);
            }

            $fields['lic_status']       = isset($_REQUEST['lic_status']) ? wp_unslash(strip_tags($_REQUEST['lic_status'])) : 'pending';
            $fields['first_name']       = wp_unslash(strip_tags($_REQUEST['first_name']));
            $fields['last_name']        = wp_unslash(strip_tags($_REQUEST['last_name']));
            $fields['purchase_id_']     = wp_unslash(strip_tags($_REQUEST['purchase_id_']));
            $fields['email']            = strip_tags($_REQUEST['email']);
            $fields['company_name']     = isset($_REQUEST['company_name']) ? wp_unslash(strip_tags($_REQUEST['company_name'])) : '';
            $fields['txn_id']           = strip_tags($_REQUEST['txn_id']);

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
            $fields['date_created']     = isset($_REQUEST['date_created']) ? strip_tags($_REQUEST['date_created']) : wp_date("Y-m-d");
            $fields['date_expiry']      = isset($_REQUEST['date_expiry']) ? strip_tags($_REQUEST['date_expiry']) : '';
            $fields['product_ref']      = isset($_REQUEST['product_ref']) ? wp_unslash(strip_tags($_REQUEST['product_ref'])) : '';
            $fields['until']            = isset($_REQUEST['until']) ? wp_unslash(strip_tags($_REQUEST['until'])) : '';
            $fields['current_ver']            = isset($_REQUEST['current_ver']) ? wp_unslash(strip_tags($_REQUEST['current_ver'])) : '';
            //current_ver
            $fields['subscr_id']        = isset($_REQUEST['subscr_id']) ? wp_unslash(strip_tags($_REQUEST['subscr_id'])) : '';
            $fields['item_reference']   = isset($_REQUEST['item_reference']) ? wp_unslash(strip_tags($_REQUEST['item_reference'])) : '';

            $fields['lic_type']         = isset($_REQUEST['lic_type']) ? wp_unslash(strip_tags($_REQUEST['lic_type'])) : '';

            $fields['slm_billing_length']   = isset($_REQUEST['slm_billing_length']) ? wp_unslash(strip_tags($_REQUEST['slm_billing_length'])) : '';
            $fields['slm_billing_interval']   = isset($_REQUEST['slm_billing_interval']) ? wp_unslash(strip_tags($_REQUEST['slm_billing_interval'])) : '';

            if ($_REQUEST['lic_type'] == 'subscription' && !isset($_REQUEST['slm_billing_length'])) {
                //error inserting
                $error_args = (array(
                    'result'     => 'error',
                    'message'    => 'License creation failed. Specify license length "slm_billing_length".',
                    'error_code' => SLM_Error_Codes::CREATE_FAILED
                ));
                SLM_API_Utility::output_api_response($error_args);
            }

            if ($_REQUEST['lic_type'] == 'subscription' && !isset($_REQUEST['slm_billing_interval'])) {
                //error inserting
                $error_args = (array(
                    'result'     => 'error',
                    'message'    => 'License creation failed. Specify license length "slm_billing_interval".',
                    'error_code' => SLM_Error_Codes::CREATE_FAILED
                ));
                SLM_API_Utility::output_api_response($error_args);
            }


            $result = $wpdb->insert($tbl_name, $fields);
            if ($result === false) {
                //error inserting
                $args = (array(
                    'result'     => 'error',
                    'message'    => 'License creation failed',
                    'error_code' => SLM_Error_Codes::CREATE_FAILED
                ));
                SLM_API_Utility::output_api_response($args);
            } else {
                $args = (array(
                    'result'    => 'success',
                    'message'   => 'License successfully created',
                    'key'       => $fields['license_key'],
                    'code'      => SLM_Error_Codes::LICENSE_CREATED
                ));

                // log data
                global $wpdb;
                SLM_Utility::create_log($fields['license_key'], 'slm_create_new');
                SLM_API_Utility::output_api_response($args);
            }
        }
    }

    function activation_api_listener()
    {
        $registered_devices = $item_reference  = $registered_domain = '';

        if (isset($_REQUEST['slm_action']) && trim($_REQUEST['slm_action']) == 'slm_activate') {
            //Handle the license activation API query
            global $slm_debug_logger;
            $options = get_option('slm_plugin_options');

            SLM_API_Utility::verify_secret_key(); //Verify the secret key first.
            $slm_debug_logger->log_debug("API - license activation (slm_activate) request received.");

            //Action hook
            do_action('slm_api_listener_slm_activate');

            $fields                         = array();
            $fields['lic_key']              = trim(strip_tags($_REQUEST['license_key']));

            if (isset($_REQUEST['registered_domain'])) {
                $registered_domain = $fields['registered_domain']    = trim(wp_unslash(strip_tags($_REQUEST['registered_domain'])));
            }

            if (isset($_REQUEST['registered_devices'])) {
                $registered_devices = $fields['registered_devices']    = trim(wp_unslash(strip_tags($_REQUEST['registered_devices'])));
            }

            if (isset($_REQUEST['item_reference'])) {
                $item_reference = $fields['item_reference']    = trim(wp_unslash(strip_tags($_REQUEST['item_reference'])));
            }


            $slm_debug_logger->log_debug("License key: " . $fields['lic_key'] . " Domain: " . $registered_domain);
            $slm_debug_logger->log_debug("License key: " . $fields['lic_key'] . " Device: " . $registered_devices);

            global $wpdb;

            $tbl_name           = SLM_TBL_LICENSE_KEYS;
            $reg_table          = SLM_TBL_LIC_DOMAIN;
            $reg_table_devices  = SLM_TBL_LIC_DEVICES;

            $key                = $fields['lic_key'];

            //Enable item_reference verification during activation
            if ($options['slm_multiple_items'] == 1) {
                $sql_prep1          = $wpdb->prepare("SELECT * FROM $tbl_name WHERE license_key = %s AND item_reference = %s", $key, $item_reference);
                $retLic             = $wpdb->get_row($sql_prep1, OBJECT);
            } else {
                $sql_prep1          = $wpdb->prepare("SELECT * FROM $tbl_name WHERE license_key = %s", $key);
                $retLic             = $wpdb->get_row($sql_prep1, OBJECT);
            }

            $sql_prep2          = $wpdb->prepare("SELECT * FROM $reg_table WHERE lic_key = %s", $key);
            $reg_domains        = $wpdb->get_results($sql_prep2, OBJECT);

            $sql_prep3          = $wpdb->prepare("SELECT * FROM $reg_table_devices WHERE lic_key = %s", $key);
            $reg_devices        = $wpdb->get_results($sql_prep3, OBJECT);

            if ($retLic) {
                if ($retLic->lic_status == 'blocked') {
                    //Trigger action hook
                    do_action('slm_api_listener_slm_activate_key_blocked', $key);


                    $args = (array('result' => 'error', 'message' => 'Your license key is blocked', 'error_code' => SLM_Error_Codes::LICENSE_BLOCKED));
                    SLM_API_Utility::output_api_response($args);
                } elseif ($retLic->lic_status == 'expired') {
                    //Trigger action hook
                    do_action('slm_api_listener_slm_activate_key_expired', $key);

                    $args = (array('result' => 'error', 'message' => 'Your license key has expired', 'error_code' => SLM_Error_Codes::LICENSE_EXPIRED));
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
                                $args = (array(
                                    'result'            => 'error',
                                    'icon_url'          =>  SLM_Utility::slm_get_icon_url('1x', 'f-remove.png'),
                                    'message'           => 'License key already in use on ' . $reg_domain->registered_domain,
                                    'error_code'        => SLM_Error_Codes::LICENSE_IN_USE,
                                    'registered_domain' => $reg_domain->registered_domain,
                                    'item_reference' =>  $item_reference
                                ));
                                SLM_API_Utility::output_api_response($args);
                            }
                        }

                        $fields['lic_key_id'] = $retLic->id;

                        $wpdb->insert($reg_table, $fields);
                        $slm_debug_logger->log_debug("Updating license key status to active for domain.");

                        $current_date   = wp_date('Y/m/d');
                        $data           = array('lic_status' => 'active', 'date_activated' => '' . $current_date . '');
                        $where          = array('id' => $retLic->id);
                        $updated        = $wpdb->update($tbl_name, $data, $where);

                        //get email record
                        $lic_email = SLM_Utility::slm_get_lic_email($key);
                        // SLM_Helper_Class::write_log( 'iam here ' . $lic_email);

                        // send activation email
                        $subject = 'Your license key was activated';
                        $message = ' Your license key: <strong>' . $key . '</strong> was activated successfully on ' . wp_date("F j, Y, g:i a") . '.';

                        SLM_Utility::slm_send_mail($lic_email, $subject, $message, 'success');

                        $args           = (array(
                            'result'                => 'success',
                            'icon_url'              =>  SLM_Utility::slm_get_icon_url('1x', 'verified.png'),
                            'message'               => 'License key activated.',
                            'registered_domain'     => $_REQUEST['registered_domain'],
                            'code'                  => SLM_Error_Codes::LICENSE_VALID,
                            'item_reference'        =>  $item_reference
                        ));

                        SLM_Utility::create_log($fields['lic_key'], 'Updating license key status to active for domain.' . $fields['registered_domain']);
                        SLM_API_Utility::output_api_response($args);
                    } else {
                        $args = (array('result' => 'error', 'message' => 'Reached maximum allowable domains', 'error_code' => SLM_Error_Codes::REACHED_MAX_DOMAINS));
                        SLM_Utility::create_log($fields['lic_key'], 'Reached maximum allowable domains');
                        SLM_API_Utility::output_api_response($args);
                    }
                }

                if (isset($_REQUEST['registered_devices']) && !empty($_REQUEST['registered_devices'])) {
                    if (count($reg_devices) < floor($retLic->max_allowed_devices)) {
                        foreach ($reg_devices as $reg_devices) {
                            if (isset($_REQUEST['migrate_from']) && (trim($_REQUEST['migrate_from']) == $reg_devices->registered_devices)) {

                                $wpdb->update($reg_table_devices, array(
                                    'registered_devices' => $fields['registered_devices']
                                ), array(
                                    'registered_devices' => trim(strip_tags($_REQUEST['migrate_from']))
                                ));
                                $devices_args = (array(
                                    'result' => 'success',
                                    'message' => 'Registered device has been updated'
                                ));
                                SLM_API_Utility::output_api_response($devices_args);
                            }
                            if ($fields['registered_devices'] == $reg_devices->registered_devices) {
                                $devices_args = (array(
                                    'result'        => 'error',
                                    'icon_url'      =>  SLM_Utility::slm_get_icon_url('1x', 'f-remove.png'),
                                    'message'       => 'License key already in use on ' . $reg_devices->registered_devices,
                                    'error_code'    => SLM_Error_Codes::LICENSE_IN_USE,
                                    'device'        => $reg_devices->registered_devices
                                ));
                                SLM_Utility::create_log($fields['lic_key'], 'License key already in use on ' . $reg_devices->registered_devices);
                                SLM_API_Utility::output_api_response($devices_args);
                            }
                        }

                        $fields['lic_key_id'] = $retLic->id;
                        $wpdb->insert($reg_table_devices, $fields);

                        $slm_debug_logger->log_debug("Updating license key status to active for device.");
                        $current_date = wp_date('Y/m/d');
                        $data = array(
                            'lic_status'        => 'active',
                            'date_activated'    => '' . $current_date . ''
                        );
                        $where          = array('id' => $retLic->id);
                        $updated        = $wpdb->update($tbl_name, $data, $where);

                        $args = (array(
                            'result'                => 'success',
                            'registered_device'     => $_REQUEST['registered_devices'],
                            'code'                  => SLM_Error_Codes::LICENSE_ACTIVATED,
                            'icon_url'              =>  SLM_Utility::slm_get_icon_url('1x', 'verified.png'),
                            'message'               => 'Updating license key status to active for device.',
                        ));

                        SLM_Utility::create_log($fields['lic_key'], 'Updating license key status to active for device.');
                        SLM_API_Utility::output_api_response($args);

                        // send activation email
                        $subject = 'Your license key was activated';
                        $message = ' Your license key: <strong>' . $key . '</strong> was activated successfully on ' . wp_date("F j, Y, g:i a") . '.';

                        SLM_Utility::slm_send_mail($lic_email, $subject, $message, 'success');
                    } else {
                        $args = (array(
                            'result'        => 'error',
                            'icon_url'      =>  SLM_Utility::slm_get_icon_url('1x', 'f-remove.png'),
                            'message'       => 'Reached maximum allowable devices for this license. Please upgarde.',
                            'error_code'    => SLM_Error_Codes::REACHED_MAX_DEVICES
                        ));
                        SLM_Utility::create_log($fields['lic_key'], 'Reached maximum allowable devices');
                        SLM_API_Utility::output_api_response($args);
                    }
                }
            } else {
                $args = (array(
                    'result'        => 'error',
                    'message'       => 'Invalid license key, key was not found.',
                    'error_code'    => SLM_Error_Codes::LICENSE_INVALID
                ));
                SLM_API_Utility::output_api_response($args);
            }
        }
    }

    function deactivation_api_listener()
    {
        if (isset($_REQUEST['slm_action']) && trim($_REQUEST['slm_action']) == 'slm_deactivate') {
            //Handle the license deactivation API query
            global $slm_debug_logger;

            SLM_API_Utility::verify_secret_key(); //Verify the secret key first.
            $slm_debug_logger->log_debug("API - license deactivation (slm_deactivate) request received.");

            //Action hook
            do_action('slm_api_listener_slm_deactivate');

            $registered_domain  = trim(wp_unslash(strip_tags($_REQUEST['registered_domain'])));
            $license_key        = trim(strip_tags($_REQUEST['license_key']));
            $registered_devices  = trim(wp_unslash(strip_tags($_REQUEST['registered_devices'])));

            $slm_debug_logger->log_debug("License key: " . $license_key . " Domain: " . $registered_domain);

            global $wpdb;

            if (isset($_REQUEST['registered_domain']) && !empty($_REQUEST['registered_domain'])) {

                if (empty($_REQUEST['registered_domain'])) {
                    $args = (array('result' => 'error', 'message' => 'Registered domain information is missing', 'error_code' => SLM_Error_Codes::DOMAIN_MISSING));
                    SLM_API_Utility::output_api_response($args);
                } else {
                    $registered_dom_table = SLM_TBL_LIC_DOMAIN;
                    $sql_prep = $wpdb->prepare("DELETE FROM $registered_dom_table WHERE lic_key=%s AND registered_domain=%s", $license_key, $registered_domain);
                    $delete = $wpdb->query($sql_prep);

                    if ($delete === false) {
                        $slm_debug_logger->log_debug("Error - failed to delete the registered domain from the database.");
                    } else if ($delete == 0) {
                        $args = (array(
                            'result'            => 'error',
                            'message'           => 'The license key on this domain is already inactive',
                            'error_code'        => SLM_Error_Codes::DOMAIN_ALREADY_INACTIVE,
                            'registered_domain' => $registered_domain->registered_domain
                        ));
                        SLM_Utility::create_log($license_key, 'domain license deactivation request failed');
                        SLM_API_Utility::output_api_response($args);
                    } else {
                        $args = (array(
                            'result'        => 'success',
                            'error_code'    => SLM_Error_Codes::KEY_DEACTIVATE_DOMAIN_SUCCESS,
                            'message'       => 'The license key has been deactivated for this domain'
                        ));
                        SLM_Utility::create_log($license_key, 'domain license deactivation request sucess');
                        SLM_API_Utility::output_api_response($args);
                    }
                }
            }


            if (isset($_REQUEST['registered_devices']) && !empty($_REQUEST['registered_devices'])) {
                // devices deactivation
                if (empty($_REQUEST['registered_devices'])) {
                    $args_ = (array('result' => 'error', 'message' => 'Registered device information is missing', 'error_code' => SLM_Error_Codes::DOMAIN_MISSING));
                    SLM_API_Utility::output_api_response($args_);
                } else {
                    $registered_device_table = SLM_TBL_LIC_DEVICES;
                    $sql_prep2 = $wpdb->prepare("DELETE FROM $registered_device_table WHERE lic_key=%s AND registered_devices=%s", $license_key, $registered_devices);
                    $delete2 = $wpdb->query($sql_prep2);

                    if ($delete2 === false) {
                        $slm_debug_logger->log_debug("Error - failed to delete the registered device from the database.");
                    } else if ($delete2 == 0) {
                        $args_ = (array('result' => 'error', 'message' => 'The license key on this device is already inactive', 'error_code' => SLM_Error_Codes::DOMAIN_ALREADY_INACTIVE));
                        SLM_Utility::create_log($license_key, 'device license deactivation request failed');
                        SLM_API_Utility::output_api_response($args_);
                    } else {
                        $args_ = (array(
                            'result' => 'success',
                            'error_code' => SLM_Error_Codes::KEY_DEACTIVATE_SUCCESS,
                            'message' => 'The license key has been deactivated for this device'
                        ));
                        SLM_Utility::create_log($license_key, 'device license deactivation request success');
                        SLM_API_Utility::output_api_response($args_);
                    }
                }
            }
        }
    }

    function update_api_listener()
    {
        if (isset($_REQUEST['slm_action']) && trim($_REQUEST['slm_action']) == 'slm_update') {

            //Handle the license activation API query
            global $slm_debug_logger;

            $options = get_option('slm_plugin_options');

            SLM_API_Utility::verify_secret_key_for_creation(); //Verify the secret key first.
            $slm_debug_logger->log_debug("API - license creation (slm_update) request received.");

            //Action hook
            do_action('slm_api_listener_slm_update');

            $fields = array();
            $fields['date_expiry'] = isset($_REQUEST['date_expiry']) ? strip_tags(sanitize_text_field($_REQUEST['date_expiry'])) : '';
            $fields['product_ref'] = isset($_REQUEST['product_ref']) ? wp_unslash(strip_tags(sanitize_text_field($_REQUEST['product_ref']))) : '';
            $fields['license_key'] = trim(strip_tags($_REQUEST['license_key']));
            $fields['max_allowed_devices'] = strip_tags($_REQUEST['max_allowed_devices']);
            $fields['max_allowed_domains'] = strip_tags($_REQUEST['max_allowed_domains']);
            $fields['txn_id'] = strip_tags(sanitize_text_field($_REQUEST['txn_id']));
            $fields['lic_type'] = isset($_REQUEST['lic_type']) ? wp_unslash(strip_tags(sanitize_text_field($_REQUEST['lic_type']))) : 'subscription';
            $fields['lic_status'] = isset($_REQUEST['lic_status']) ? wp_unslash(strip_tags(sanitize_text_field($_REQUEST['lic_status']))) : 'active';
            $fields['item_reference']   = isset($_REQUEST['item_reference']) ? wp_unslash(strip_tags($_REQUEST['item_reference'])) : '';


            global $wpdb;
            $tbl_name   = SLM_TBL_LICENSE_KEYS;
            $key        = array('license_key' => $fields['license_key']);
            $result     = $wpdb->update($tbl_name, $fields, $key);

            if ($result === false) {
                //error updating the license
                $args = (array(
                    'result'        => 'error',
                    'message'       => 'License update failed',
                    'error_code'    => SLM_Error_Codes::KEY_UPDATE_FAILED
                ));
                SLM_Utility::create_log($fields['license_key'], 'License update failed');
                SLM_API_Utility::output_api_response($args);
            } else {
                $args = (array(
                    'result'        => 'success',
                    'message'       => 'License successfully updated',
                    'key'           => $fields['license_key'],
                    'error_code'    => SLM_Error_Codes::KEY_UPDATE_SUCCESS,
                ));
                SLM_Utility::create_log($fields['license_key'], 'License successfully updated');
                SLM_API_Utility::output_api_response($args);
            }
        } else {
            $args = (array(
                'result'        => 'error',
                'message'       => 'Cannot update license, license key not found.',
                'error_code'    => SLM_Error_Codes::MISSING_KEY_UPDATE_FAILED
            ));
            SLM_Utility::create_log($_REQUEST['license_key'], 'License key not found.');
            SLM_API_Utility::output_api_response($args);
        }
    }

    function check_api_listener()
    {
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

            if ($retLic) {
                //A license key exists
                $args = apply_filters('slm_check_response_args', array(
                    'result'                => 'success',
                    'code'                  => SLM_Error_Codes::LICENSE_EXIST,
                    'message'               => 'License key details retrieved.',
                    'status'                => $retLic->lic_status,
                    'subscr_id'             => $retLic->subscr_id,
                    'first_name'            => $retLic->first_name,
                    'last_name'             => $retLic->last_name,
                    'company_name'          => $retLic->company_name,
                    'email'                 => $retLic->email,
                    'license_key'           => $retLic->license_key,
                    'lic_type'              => $retLic->lic_type,
                    'max_allowed_domains'   => $retLic->max_allowed_domains,
                    'max_allowed_devices'   => $retLic->max_allowed_devices,
                    'item_reference'        => $retLic->item_reference,
                    'registered_domains'    => $reg_domains,
                    'registered_devices'    => $reg_devices,
                    'date_created'          => $retLic->date_created,
                    'date_renewed'          => $retLic->date_renewed,
                    'date_expiry'           => $retLic->date_expiry,
                    'product_ref'           => $retLic->product_ref,
                    'txn_id'                => $retLic->txn_id,
                    'until'                 => $retLic->until,
                    'current_ver'           => $retLic->current_ver,
                ));

                //Output the license details
                SLM_Utility::create_log($key, 'check: valid license key');
                SLM_API_Utility::output_api_response($args);
            } else {
                $args = (array(
                    'result'        => 'error',
                    'message'       => 'Invalid license key',
                    'error_code'    => SLM_Error_Codes::LICENSE_INVALID
                ));
                SLM_Utility::create_log($key, 'check: invalid license key');
                SLM_API_Utility::output_api_response($args);
            }
        }
    }

    function check_api_info()
    {
        if (isset($_REQUEST['slm_action']) && trim($_REQUEST['slm_action']) == 'slm_info') {
            //Handle the license check API query
            global $slm_debug_logger;

            SLM_API_Utility::verify_secret_key(); //Verify the secret key first.

            $slm_debug_logger->log_debug("API - license check (slm_info) request received.");

            $fields = array();
            $fields['lic_key'] = trim(strip_tags($_REQUEST['license_key']));
            $slm_debug_logger->log_debug("License key: " . $fields['lic_key']);

            //Action hook
            do_action('slm_api_listener_slm_info');

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

            if ($retLic) {
                //A license key exists
                $args = apply_filters('slm_info_response_args', array(
                    'result'                => 'success',
                    'message'               => 'License key details retrieved.',
                    'code'                  => SLM_Error_Codes::LICENSE_EXIST,
                    'status'                => $retLic->lic_status,
                    'subscr_id'             => $retLic->subscr_id,
                    'first_name'            => $retLic->first_name,
                    'last_name'             => $retLic->last_name,
                    'company_name'          => $retLic->company_name,
                    'email'                 => $retLic->email,
                    'license_key'           => $retLic->license_key,
                    'lic_type'              => $retLic->lic_type,
                    'max_allowed_domains'   => $retLic->max_allowed_domains,
                    'item_reference'        => $retLic->item_reference,
                    'max_allowed_devices'   => $retLic->max_allowed_devices,
                    'date_created'          => $retLic->date_created,
                    'date_renewed'          => $retLic->date_renewed,
                    'date_expiry'           => $retLic->date_expiry,
                    'product_ref'           => $retLic->product_ref,
                    'txn_id'                => $retLic->txn_id,
                    'until'                 => $retLic->until,
                    'current_ver'           => $retLic->current_ver,
                    //current_ver
                ));

                //Output the license details
                SLM_Utility::create_log($key, 'check: valid license key');
                SLM_API_Utility::output_api_response($args);
            } else {
                $args = (array(
                    'result'        => 'error',
                    'message'       => 'Invalid license key',
                    'error_code'    => SLM_Error_Codes::LICENSE_INVALID
                ));
                SLM_Utility::create_log($key, 'check: invalid license key');
                SLM_API_Utility::output_api_response($args);
            }
        }
    }
}
