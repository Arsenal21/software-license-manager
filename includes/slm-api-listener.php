<?php

/**
 * This class listens for API queries and executes API requests.
 * It provides functionality to create, update, activate, deactivate,
 * check, and retrieve information about licenses.
 *
 * Available API Actions:
 * 1) slm_create_new       - Creates a new license.
 * 2) slm_update           - Updates an existing license.
 * 3) slm_activate         - Activates a license for a domain or device.
 * 4) slm_deactivate       - Deactivates a license from a domain or device.
 * 5) slm_remove           - Removes a license.
 * 6) slm_check            - Checks the status of a license.
 * 7) slm_info             - Retrieves detailed information about a license.
 *
 * Security:
 * - All actions require a secret key for validation.
 * - Input sanitization is performed to prevent malicious data.
 * - SQL queries are prepared to prevent SQL injection attacks.
 *
 * Performance:
 * - Optimized database queries and reduced redundant processing.
 * - Efficient handling of domain and device activation/deactivation.
 *
 * Backward Compatibility:
 * - Designed to work with older WordPress and PHP versions.
 *
 * @package   SLM Plus
 * @author    Michel Velis
 * @license   GPL-2.0+
 * @link      http://epikly.com
 */


class SLM_API_Listener
{
    function __construct() {
        if (isset($_REQUEST['slm_action']) && isset($_REQUEST['secret_key'])) {
            do_action('slm_api_listener_init');
            $slm_action = sanitize_text_field($_REQUEST['slm_action']);
            switch ($slm_action) {
                case 'renew_license':
                    $this->renew_api_listener();
                    break;
                case 'slm_create_new':
                    $this->creation_api_listener();
                    break;
                case 'slm_activate':
                    $this->activation_api_listener();
                    break;
                case 'slm_deactivate':
                    $this->deactivation_api_listener();
                    break;
                case 'slm_update':
                    $this->update_api_listener();
                    break;
                case 'slm_check':
                    $this->check_api_listener();
                    break;
                case 'slm_info':
                    $this->check_api_info();
                    break;
            }
        }
    }
    

    function creation_api_listener()
    {
        if (isset($_REQUEST['slm_action']) && trim($_REQUEST['slm_action']) === 'slm_create_new') {
            global $slm_debug_logger, $wpdb;
            $slm_lic_table = SLM_TBL_LICENSE_KEYS;
            $options = get_option('slm_plugin_options');
            $lic_key_prefix = $options['lic_prefix'] ?? 'SLM-'; // Use default prefix if missing

            // Security check: Verify secret key
            SLM_API_Utility::verify_secret_key_for_creation();

            // Logging for debugging
            $slm_debug_logger->log_debug("API - license creation (slm_create_new) request received.");

            // Trigger action hook for external integrations
            do_action('slm_api_listener_slm_create_new');

            // Initialize fields array
            $fields = [];

            // License key handling
            if (!empty($_REQUEST['license_key'])) {
                $fields['license_key'] = sanitize_text_field($_REQUEST['license_key']);
            } else {
                $fields['license_key'] = slm_get_license($lic_key_prefix); // Generate if not provided
            }

            // Sanitize and prepare other fields
            $fields['lic_status']       = !empty($_REQUEST['lic_status']) ? sanitize_text_field($_REQUEST['lic_status']) : 'pending';
            $fields['first_name']       = sanitize_text_field($_REQUEST['first_name']);
            $fields['last_name']        = sanitize_text_field($_REQUEST['last_name']);
            $fields['purchase_id_']     = sanitize_text_field($_REQUEST['purchase_id_']);
            $fields['email']            = sanitize_email($_REQUEST['email']);
            $fields['company_name']     = !empty($_REQUEST['company_name']) ? sanitize_text_field($_REQUEST['company_name']) : '';
            $fields['txn_id']           = sanitize_text_field($_REQUEST['txn_id']);
            $fields['max_allowed_domains'] = !empty($_REQUEST['max_allowed_domains']) ? intval($_REQUEST['max_allowed_domains']) : intval($options['default_max_domains']);
            $fields['max_allowed_devices'] = !empty($_REQUEST['max_allowed_devices']) ? intval($_REQUEST['max_allowed_devices']) : intval($options['default_max_devices']);
            $fields['date_created']     = isset($_REQUEST['date_created']) ? sanitize_text_field($_REQUEST['date_created']) : wp_date('Y-m-d');
            $fields['date_expiry']      = !empty($_REQUEST['date_expiry']) ? sanitize_text_field($_REQUEST['date_expiry']) : null;
            $fields['product_ref']      = !empty($_REQUEST['product_ref']) ? sanitize_text_field($_REQUEST['product_ref']) : '';
            $fields['until']            = !empty($_REQUEST['until']) ? sanitize_text_field($_REQUEST['until']) : '';
            $fields['current_ver']      = !empty($_REQUEST['current_ver']) ? sanitize_text_field($_REQUEST['current_ver']) : '';
            $fields['subscr_id']        = !empty($_REQUEST['subscr_id']) ? sanitize_text_field($_REQUEST['subscr_id']) : '';
            $fields['item_reference']   = !empty($_REQUEST['item_reference']) ? sanitize_text_field($_REQUEST['item_reference']) : '';
            $fields['lic_type']         = !empty($_REQUEST['lic_type']) ? sanitize_text_field($_REQUEST['lic_type']) : '';
            $fields['slm_billing_length'] = !empty($_REQUEST['slm_billing_length']) ? sanitize_text_field($_REQUEST['slm_billing_length']) : '';
            $fields['slm_billing_interval'] = !empty($_REQUEST['slm_billing_interval']) ? sanitize_text_field($_REQUEST['slm_billing_interval']) : '';

            // Validation for subscription-type licenses
            if ($fields['lic_type'] === 'subscription') {
                if (empty($fields['slm_billing_length'])) {
                    SLM_API_Utility::output_api_response([
                        'result'     => 'error',
                        'message'    => 'License creation failed. Specify "slm_billing_length".',
                        'error_code' => SLM_Error_Codes::CREATE_FAILED
                    ]);
                }
                if (empty($fields['slm_billing_interval'])) {
                    SLM_API_Utility::output_api_response([
                        'result'     => 'error',
                        'message'    => 'License creation failed. Specify "slm_billing_interval".',
                        'error_code' => SLM_Error_Codes::CREATE_FAILED
                    ]);
                }
            }

            // Insert the license into the database
            $result = $wpdb->insert($slm_lic_table, $fields);

            // Error handling for database insertion
            if ($result === false) {
                SLM_API_Utility::output_api_response([
                    'result'     => 'error',
                    'message'    => 'License creation failed',
                    'error_code' => SLM_Error_Codes::CREATE_FAILED
                ]);
            } else {
                // Success: License created
                $response_args = [
                    'result'    => 'success',
                    'message'   => 'License successfully created',
                    'key'       => $fields['license_key'],
                    'code'      => SLM_Error_Codes::LICENSE_CREATED
                ];

                // Log license creation
                SLM_Utility::create_log($fields['license_key'], 'slm_create_new');

                // Output API response
                SLM_API_Utility::output_api_response($response_args);
            }
        }
    }

    function activation_api_listener()
    {
        if (isset($_REQUEST['slm_action']) && trim($_REQUEST['slm_action']) === 'slm_activate') {
            global $slm_debug_logger, $wpdb;
            $sql_prep1 = "";
            $options = get_option('slm_plugin_options');

            // Verify secret key first for security
            SLM_API_Utility::verify_secret_key();

            // Trigger action hook for external integrations
            do_action('slm_api_listener_slm_activate');

            // Initialize fields
            $fields = [];
            $fields['lic_key']      = sanitize_text_field($_REQUEST['license_key']);
            $registered_domain      = isset($_REQUEST['registered_domain']) ? sanitize_text_field($_REQUEST['registered_domain']) : '';
            $registered_devices     = isset($_REQUEST['registered_devices']) ? sanitize_text_field($_REQUEST['registered_devices']) : '';
            $item_reference         = isset($_REQUEST['item_reference']) ? sanitize_text_field($_REQUEST['item_reference']) : '';


            // Table names
            $slm_lic_table      = SLM_TBL_LICENSE_KEYS;
            $reg_domain_table   = SLM_TBL_LIC_DOMAIN;
            $reg_table_devices  = SLM_TBL_LIC_DEVICES;

            // Check if multiple items need verification
            if (!empty($item_reference) && $options['slm_multiple_items'] == 1) {
                $sql_prep1 = $wpdb->prepare("SELECT * FROM $slm_lic_table WHERE license_key = %s AND item_reference = %s", $fields['lic_key'], $item_reference);
            } else {
                $sql_prep1 = $wpdb->prepare("SELECT * FROM $slm_lic_table WHERE license_key = %s", $fields['lic_key']);
            }

            // Get the license details from the database
            $retLic = $wpdb->get_row($sql_prep1, OBJECT);

            SLM_Helper_Class::write_log('User ID (subscr_id): ' . $sql_prep1);


            if (!$retLic) {
                $args = ['result' => 'error', 'message' => 'Invalid license key, key was not found.', 'error_code' => SLM_Error_Codes::LICENSE_INVALID];
                SLM_API_Utility::output_api_response($args);
            }

            // Check if the license is blocked or expired
            if ($retLic->lic_status === 'blocked') {
                do_action('slm_api_listener_slm_activate_key_blocked', $fields['lic_key']);
                $args = ['result' => 'error', 'message' => 'Your license key is blocked', 'error_code' => SLM_Error_Codes::LICENSE_BLOCKED];
                SLM_API_Utility::output_api_response($args);
            }

            if ($retLic->lic_status === 'expired') {
                do_action('slm_api_listener_slm_activate_key_expired', $fields['lic_key']);
                $args = ['result' => 'error', 'message' => 'Your license key has expired', 'error_code' => SLM_Error_Codes::LICENSE_EXPIRED];
                SLM_API_Utility::output_api_response($args);
            }

            // Handling registered domains
            if (!empty($registered_domain)) {
                $sql_prep2 = $wpdb->prepare("SELECT * FROM $reg_domain_table WHERE lic_key = %s", $fields['lic_key']);
                $reg_domains = $wpdb->get_results($sql_prep2, OBJECT);

                if (count($reg_domains) < intval($retLic->max_allowed_domains)) {
                    foreach ($reg_domains as $reg_domain) {

                        // Handle domain migration
                        if (!empty($_REQUEST['migrate_from']) && $reg_domain->registered_domain === sanitize_text_field($_REQUEST['migrate_from'])) {
                            $wpdb->update($reg_domain_table, ['registered_domain' => $registered_domain], ['registered_domain' => sanitize_text_field($_REQUEST['migrate_from'])]);
                            $args = ['result' => 'success', 'message' => 'Registered domain has been updated'];
                            SLM_API_Utility::output_api_response($args);
                        }

                        // Check if the domain is already in use
                        if ($reg_domain->registered_domain === $registered_domain) {
                            $args = [
                                'result' => 'error',
                                'icon_url' => SLM_Utility::slm_get_icon_url('1x', 'f-remove.png'),
                                'message' => 'License key already in use on ' . $registered_domain,
                                'error_code' => SLM_Error_Codes::LICENSE_IN_USE,
                                'registered_domain' => $reg_domain->registered_domain,
                                'item_reference' => $item_reference
                            ];
                            SLM_API_Utility::output_api_response($args);
                        }
                    }

                    // Register new domain
                    // If the registered domain is provided, add it to the fields
                    $fields['registered_domain'] = $registered_domain;

                    // Assuming $retLic->id contains the license key ID, add it to the fields
                    $fields['lic_key_id'] = $retLic->id;

                    // Insert into the registered domain table
                    $wpdb->insert($reg_domain_table, $fields);

                    // Update license status to active
                    $current_date = wp_date('Y-m-d');
                    $wpdb->update($slm_lic_table, ['lic_status' => 'active', 'date_activated' => $current_date], ['id' => $retLic->id]);

                    //SLM_Helper_Class::write_log('LOG: ' . $url);

                    // Send activation email
                    $lic_email = SLM_Utility::slm_get_lic_email($fields['lic_key']);
                    $subject = 'Your license key was activated';
                    $message = 'Your license key: <strong>' . $fields['lic_key'] . '</strong> was activated successfully on ' . wp_date("F j, Y, g:i a") . '.';
                    SLM_Utility::slm_send_mail($lic_email, $subject, $message, 'success');

                    // Return success response
                    $args = [
                        'result' => 'success',
                        'icon_url' => SLM_Utility::slm_get_icon_url('1x', 'verified.png'),
                        'message' => 'License key activated.',
                        'registered_domain' => $registered_domain,
                        'code' => SLM_Error_Codes::LICENSE_VALID,
                        'item_reference' => $item_reference
                    ];
                    SLM_Utility::create_log($fields['lic_key'], 'License key activated for domain ' . $registered_domain);
                    SLM_API_Utility::output_api_response($args);
                } else {
                    $args = [
                        'result' => 'error',
                        'message' => 'Reached maximum allowable domains',
                        'error_code' => SLM_Error_Codes::REACHED_MAX_DOMAINS
                    ];
                    SLM_Utility::create_log($fields['lic_key'], 'Reached maximum allowable domains');
                    SLM_API_Utility::output_api_response($args);
                }
            }

            // Handling registered devices
            if (!empty($registered_devices)) {
                $sql_prep3 = $wpdb->prepare("SELECT * FROM $reg_table_devices WHERE lic_key = %s", $fields['lic_key']);
                $reg_devices = $wpdb->get_results($sql_prep3, OBJECT);

                if (count($reg_devices) < intval($retLic->max_allowed_devices)) {
                    foreach ($reg_devices as $reg_device) {
                        if (!empty($_REQUEST['migrate_from']) && $reg_device->registered_devices === sanitize_text_field($_REQUEST['migrate_from'])) {
                            $wpdb->update($reg_table_devices, ['registered_devices' => $registered_devices], ['registered_devices' => sanitize_text_field($_REQUEST['migrate_from'])]);
                            $args = ['result' => 'success', 'message' => 'Registered device has been updated'];
                            SLM_API_Utility::output_api_response($args);
                        }
                        if ($reg_device->registered_devices === $registered_devices) {
                            $args = [
                                'result' => 'error',
                                'icon_url' => SLM_Utility::slm_get_icon_url('1x', 'f-remove.png'),
                                'message' => 'License key already in use on ' . $registered_devices,
                                'error_code' => SLM_Error_Codes::LICENSE_IN_USE,
                                'device' => $reg_device->registered_devices
                            ];
                            SLM_API_Utility::output_api_response($args);
                        }
                    }

                    // Register new device
                    // If the registered device is provided, add it to the fields
                    $fields['registered_devices'] = $registered_devices;

                    // Assuming $retLic->id contains the license key ID, add it to the fields
                    $fields['lic_key_id'] = $retLic->id;

                    // Insert into the registered device table
                    $wpdb->insert($reg_table_devices, $fields);

                    // Update license status
                    $current_date = wp_date('Y-m-d');
                    $wpdb->update($slm_lic_table, ['lic_status' => 'active', 'date_activated' => $current_date], ['id' => $retLic->id]);

                    // Send activation email
                    $lic_email = SLM_Utility::slm_get_lic_email($fields['lic_key']);
                    $subject = 'Your license key was activated';
                    $message = 'Your license key: <strong>' . $fields['lic_key'] . '</strong> was activated successfully on ' . wp_date("F j, Y, g:i a") . '.';
                    SLM_Utility::slm_send_mail($lic_email, $subject, $message, 'success');

                    // Return success response
                    $args = [
                        'result' => 'success',
                        'registered_device' => $registered_devices,
                        'code' => SLM_Error_Codes::LICENSE_ACTIVATED,
                        'icon_url' => SLM_Utility::slm_get_icon_url('1x', 'verified.png'),
                        'message' => 'License key activated for device.',
                    ];
                    SLM_Utility::create_log($fields['lic_key'], 'License key activated for device ' . $registered_devices);
                    SLM_API_Utility::output_api_response($args);
                } else {
                    $args = [
                        'result' => 'error',
                        'icon_url' => SLM_Utility::slm_get_icon_url('1x', 'f-remove.png'),
                        'message' => 'Reached maximum allowable devices for this license. Please upgrade.',
                        'error_code' => SLM_Error_Codes::REACHED_MAX_DEVICES
                    ];
                    SLM_Utility::create_log($fields['lic_key'], 'Reached maximum allowable devices');
                    SLM_API_Utility::output_api_response($args);
                }
            }
        }
    }

    function deactivation_api_listener()
    {
        if (isset($_REQUEST['slm_action']) && trim($_REQUEST['slm_action']) === 'slm_deactivate') {
            global $slm_debug_logger, $wpdb;

            // Verify the secret key for security
            SLM_API_Utility::verify_secret_key();
            $slm_debug_logger->log_debug("API - license deactivation (slm_deactivate) request received.");

            // Trigger deactivation hook for other integrations
            do_action('slm_api_listener_slm_deactivate');

            // Sanitize inputs
            $license_key = sanitize_text_field($_REQUEST['license_key']);
            $registered_domain = isset($_REQUEST['registered_domain']) ? sanitize_text_field($_REQUEST['registered_domain']) : '';
            $registered_devices = isset($_REQUEST['registered_devices']) ? sanitize_text_field($_REQUEST['registered_devices']) : '';


            // Handle domain deactivation if domain info is provided
            if (!empty($registered_domain)) {
                $registered_dom_table = SLM_TBL_LIC_DOMAIN;

                // Prepare SQL query for domain deactivation
                $sql_prep = $wpdb->prepare("DELETE FROM $registered_dom_table WHERE lic_key = %s AND registered_domain = %s", $license_key, $registered_domain);
                $delete = $wpdb->query($sql_prep);

                // Check result of the deletion query
                if ($delete === false) {
                    $slm_debug_logger->log_debug("Error - failed to delete the registered domain from the database.");
                    $args = ['result' => 'error', 'message' => 'Failed to delete the registered domain.', 'error_code' => SLM_Error_Codes::DOMAIN_MISSING];
                    SLM_API_Utility::output_api_response($args);
                } elseif ($delete === 0) {
                    $args = [
                        'result' => 'error',
                        'message' => 'The license key on this domain is already inactive',
                        'error_code' => SLM_Error_Codes::DOMAIN_ALREADY_INACTIVE,
                    ];
                    SLM_Utility::create_log($license_key, 'Domain license deactivation failed - already inactive.');
                    SLM_API_Utility::output_api_response($args);
                } else {
                    // Successful deactivation of the domain
                    $args = [
                        'result' => 'success',
                        'message' => 'The license key has been deactivated for this domain.',
                        'error_code' => SLM_Error_Codes::KEY_DEACTIVATE_DOMAIN_SUCCESS,
                    ];
                    SLM_Utility::create_log($license_key, 'Domain license deactivated successfully.');
                    SLM_API_Utility::output_api_response($args);
                }
            }

            // Handle device deactivation if device info is provided
            if (!empty($registered_devices)) {
                $registered_device_table = SLM_TBL_LIC_DEVICES;

                // Prepare SQL query for device deactivation
                $sql_prep2 = $wpdb->prepare("DELETE FROM $registered_device_table WHERE lic_key = %s AND registered_devices = %s", $license_key, $registered_devices);
                $delete2 = $wpdb->query($sql_prep2);

                // Check result of the deletion query
                if ($delete2 === false) {
                    $slm_debug_logger->log_debug("Error - failed to delete the registered device from the database.");
                    $args = ['result' => 'error', 'message' => 'Failed to delete the registered device.', 'error_code' => SLM_Error_Codes::DOMAIN_MISSING];
                    SLM_API_Utility::output_api_response($args);
                } elseif ($delete2 === 0) {
                    $args = [
                        'result' => 'error',
                        'message' => 'The license key on this device is already inactive',
                        'error_code' => SLM_Error_Codes::DOMAIN_ALREADY_INACTIVE,
                    ];
                    SLM_Utility::create_log($license_key, 'Device license deactivation failed - already inactive.');
                    SLM_API_Utility::output_api_response($args);
                } else {
                    // Successful deactivation of the device
                    $args = [
                        'result' => 'success',
                        'message' => 'The license key has been deactivated for this device.',
                        'error_code' => SLM_Error_Codes::KEY_DEACTIVATE_SUCCESS,
                    ];
                    SLM_Utility::create_log($license_key, 'Device license deactivated successfully.');
                    SLM_API_Utility::output_api_response($args);
                }
            }

            // If neither domain nor device info is provided, return an error response
            if (empty($registered_domain) && empty($registered_devices)) {
                $args = ['result' => 'error', 'message' => 'No deactivation target specified. Either a domain or device must be provided.', 'error_code' => SLM_Error_Codes::DOMAIN_MISSING];
                SLM_API_Utility::output_api_response($args);
            }
        }
    }

    function update_api_listener()
    {
        if (isset($_REQUEST['slm_action']) && trim($_REQUEST['slm_action']) === 'slm_update') {
            global $slm_debug_logger, $wpdb;

            // Verify secret key for security
            SLM_API_Utility::verify_secret_key_for_creation();
            $slm_debug_logger->log_debug("API - license update (slm_update) request received.");

            // Trigger update hook for integrations
            do_action('slm_api_listener_slm_update');

            // Sanitize inputs and build the update fields array
            $fields = array(
                'license_key' => sanitize_text_field($_REQUEST['license_key']),
                'date_expiry' => isset($_REQUEST['date_expiry']) ? sanitize_text_field($_REQUEST['date_expiry']) : '',
                'product_ref' => isset($_REQUEST['product_ref']) ? sanitize_text_field($_REQUEST['product_ref']) : '',
                'max_allowed_devices' => isset($_REQUEST['max_allowed_devices']) ? sanitize_text_field($_REQUEST['max_allowed_devices']) : '',
                'max_allowed_domains' => isset($_REQUEST['max_allowed_domains']) ? sanitize_text_field($_REQUEST['max_allowed_domains']) : '',
                'txn_id' => isset($_REQUEST['txn_id']) ? sanitize_text_field($_REQUEST['txn_id']) : '',
                'lic_type' => isset($_REQUEST['lic_type']) ? sanitize_text_field($_REQUEST['lic_type']) : 'subscription',
                'lic_status' => isset($_REQUEST['lic_status']) ? sanitize_text_field($_REQUEST['lic_status']) : 'active',
                'item_reference' => isset($_REQUEST['item_reference']) ? sanitize_text_field($_REQUEST['item_reference']) : '',
            );

            // Validate that the license key is provided
            if (empty($fields['license_key'])) {
                $args = array(
                    'result' => 'error',
                    'message' => 'Cannot update license, license key not provided.',
                    'error_code' => SLM_Error_Codes::MISSING_KEY_UPDATE_FAILED
                );
                $slm_debug_logger->log_debug("API - License update failed: Missing license key.");
                SLM_API_Utility::output_api_response($args);
                return;
            }

            // Update the license in the database
            $slm_lic_table = SLM_TBL_LICENSE_KEYS;
            $where_clause = array('license_key' => $fields['license_key']);
            $update_result = $wpdb->update($slm_lic_table, $fields, $where_clause);

            // Handle update result
            if ($update_result === false) {
                $args = array(
                    'result' => 'error',
                    'message' => 'License update failed.',
                    'error_code' => SLM_Error_Codes::KEY_UPDATE_FAILED
                );
                SLM_Utility::create_log($fields['license_key'], 'License update failed');
                SLM_API_Utility::output_api_response($args);
            } else {
                $args = array(
                    'result' => 'success',
                    'message' => 'License successfully updated.',
                    'key' => $fields['license_key'],
                    'error_code' => SLM_Error_Codes::KEY_UPDATE_SUCCESS
                );
                SLM_Utility::create_log($fields['license_key'], 'License successfully updated');
                SLM_API_Utility::output_api_response($args);
            }
        } else {
            // Handle missing or incorrect action parameter
            $args = array(
                'result' => 'error',
                'message' => 'Cannot update license, license key not found or invalid action.',
                'error_code' => SLM_Error_Codes::MISSING_KEY_UPDATE_FAILED
            );
            SLM_Utility::create_log($_REQUEST['license_key'], 'License update failed: action parameter incorrect or missing.');
            SLM_API_Utility::output_api_response($args);
        }
    }

    function check_api_listener()
    {
        if (isset($_REQUEST['slm_action']) && trim($_REQUEST['slm_action']) === 'slm_check') {
            global $slm_debug_logger, $wpdb;

            // Verify secret key for security
            SLM_API_Utility::verify_secret_key();

            $slm_debug_logger->log_debug("API - license check (slm_check) request received.");

            // Sanitize input
            $license_key = sanitize_text_field($_REQUEST['license_key']);
            $slm_debug_logger->log_debug("Checking license key: " . $license_key);

            // Action hook for additional integrations
            do_action('slm_api_listener_slm_check');

            // Query license key details
            $slm_lic_table = SLM_TBL_LICENSE_KEYS;
            $reg_domain_table = SLM_TBL_LIC_DOMAIN;
            $reg_table_devices = SLM_TBL_LIC_DEVICES;

            // Retrieve the license key details from the database
            $license_query = $wpdb->prepare("SELECT * FROM $slm_lic_table WHERE license_key = %s", $license_key);
            $retLic = $wpdb->get_row($license_query, OBJECT);

            if ($retLic) {
                // If the license exists, retrieve domain and device information
                $domain_query = $wpdb->prepare("SELECT * FROM $reg_domain_table WHERE lic_key = %s", $license_key);
                $device_query = $wpdb->prepare("SELECT * FROM $reg_table_devices WHERE lic_key = %s", $license_key);

                $registered_domains = $wpdb->get_results($domain_query, OBJECT);
                $registered_devices = $wpdb->get_results($device_query, OBJECT);

                // Prepare response with license and registration data
                $response_args = apply_filters('slm_check_response_args', array(
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
                    'registered_domains'    => $registered_domains,
                    'registered_devices'    => $registered_devices,
                    'date_created'          => $retLic->date_created,
                    'date_renewed'          => $retLic->date_renewed,
                    'date_expiry'           => $retLic->date_expiry,
                    'product_ref'           => $retLic->product_ref,
                    'txn_id'                => $retLic->txn_id,
                    'until'                 => $retLic->until,
                    'current_ver'           => $retLic->current_ver,
                ));

                // Log and send the response
                SLM_Utility::create_log($license_key, 'License check successful');
                SLM_API_Utility::output_api_response($response_args);
            } else {
                // Invalid license key case
                $error_args = array(
                    'result'        => 'error',
                    'message'       => 'Invalid license key',
                    'error_code'    => SLM_Error_Codes::LICENSE_INVALID
                );

                // Log the error and respond
                SLM_Utility::create_log($license_key, 'License check failed: Invalid license key');
                SLM_API_Utility::output_api_response($error_args);
            }
        }
    }

    function check_api_info()
    {
        if (isset($_REQUEST['slm_action']) && trim($_REQUEST['slm_action']) === 'slm_info') {
            global $slm_debug_logger, $wpdb;

            // Verify secret key for security
            SLM_API_Utility::verify_secret_key();

            // Log the API request
            $slm_debug_logger->log_debug("API - license info (slm_info) request received.");

            // Sanitize input data
            $license_key = sanitize_text_field($_REQUEST['license_key']);
            $slm_debug_logger->log_debug("License key: " . $license_key);

            // Action hook for additional integrations
            do_action('slm_api_listener_slm_info');

            // Fetch license details from the database
            $slm_lic_table = SLM_TBL_LICENSE_KEYS;
            $reg_domain_table = SLM_TBL_LIC_DOMAIN;
            $reg_table_devices = SLM_TBL_LIC_DEVICES;

            $license_query = $wpdb->prepare("SELECT * FROM $slm_lic_table WHERE license_key = %s", $license_key);
            $retLic = $wpdb->get_row($license_query, OBJECT);

            if ($retLic) {
                // If the license exists, fetch associated domains and devices
                $domain_query = $wpdb->prepare("SELECT * FROM $reg_domain_table WHERE lic_key = %s", $license_key);
                $device_query = $wpdb->prepare("SELECT * FROM $reg_table_devices WHERE lic_key = %s", $license_key);

                $registered_domains = $wpdb->get_results($domain_query, OBJECT);
                $registered_devices = $wpdb->get_results($device_query, OBJECT);

                // Prepare the response with the license and registration data
                $response_args = apply_filters('slm_info_response_args', array(
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
                ));

                // Log the successful check
                SLM_Utility::create_log($license_key, 'info: valid license key');
                SLM_API_Utility::output_api_response($response_args);
            } else {
                // Handle invalid license case
                $error_args = array(
                    'result'        => 'error',
                    'message'       => 'Invalid license key',
                    'error_code'    => SLM_Error_Codes::LICENSE_INVALID
                );

                // Log the error
                SLM_Utility::create_log($license_key, 'info: invalid license key');
                SLM_API_Utility::output_api_response($error_args);
            }
        }
    }

    function wc_slm_handle_license_renewal($order_id, $license_key)
    {
        // Log the renewal action for debugging
        SLM_Helper_Class::write_log("Processing license renewal for Order ID: $order_id, License Key: $license_key");

        // Retrieve the license data
        $license_data = SLM_Utility::get_licence_by_key($license_key);
        if (!$license_data) {
            SLM_Helper_Class::write_log("License key $license_key not found.");
            return; // Stop if the license is not found
        }

        global $wpdb;
        $license_table = SLM_TBL_LICENSE_KEYS;

        // Calculate the new expiration date
        $renewal_period = intval($license_data['slm_billing_length']);
        $renewal_term = sanitize_text_field($license_data['slm_billing_interval']);
        $new_expiry_date = date('Y-m-d', strtotime('+' . $renewal_period . ' ' . $renewal_term));

        // Update the license expiry date in the database
        $update_result = $wpdb->update(
            $license_table,
            ['date_expiry' => $new_expiry_date, 'lic_status' => 'active'],
            ['license_key' => $license_key]
        );

        if ($update_result !== false) {
            SLM_Helper_Class::write_log("License $license_key renewed successfully. New expiry date: $new_expiry_date.");
        } else {
            SLM_Helper_Class::write_log("Failed to renew license $license_key.");
        }

        // Add order note for renewal confirmation
        $order = wc_get_order($order_id);
        if ($order) {
            $order->add_order_note(sprintf(
                __('License Key %s renewed. New expiry date: %s', 'slm-plus'),
                $license_key,
                $new_expiry_date
            ));
            $order->save();
        }

        // Optional: Notify the user about the renewal
        wc_slm_notify_user_about_renewal($order, $license_key);
    }


    function renew_api_listener() {
        if (isset($_POST['slm_action']) && trim($_POST['slm_action']) === 'renew_license') {
            global $wpdb;

            // Verify the secret key for security
            SLM_API_Utility::verify_secret_key();

            // Get and sanitize the license key and WooCommerce order ID
            $license_key = sanitize_text_field($_POST['license_key']);
            $wc_order_id = intval($_POST['wc_order_id']);

            // Check if license key and order ID are provided
            if (empty($license_key) || empty($wc_order_id)) {
                SLM_API_Utility::output_api_response([
                    'result' => 'error',
                    'message' => 'License key or WooCommerce order ID is missing.',
                    'error_code' => SLM_Error_Codes::MISSING_PARAMETERS,
                    'status_code' => 400,
                ]);
                return;
            }

            // Fetch the WooCommerce order
            $order = wc_get_order($wc_order_id);
            if (!$order) {
                SLM_API_Utility::output_api_response([
                    'result' => 'error',
                    'message' => 'Invalid WooCommerce order ID.',
                    'error_code' => SLM_Error_Codes::ORDER_NOT_FOUND,
                    'status_code' => 404,
                ]);
                return;
            }

            // Fetch license details from the database
            $license = SLM_Utility::get_license_by_key($license_key);
            if (!$license) {
                SLM_API_Utility::output_api_response([
                    'result' => 'error',
                    'message' => 'License not found.',
                    'error_code' => SLM_Error_Codes::LICENSE_INVALID,
                    'status_code' => 404,
                ]);
                return;
            }

            // Verify the order ID matches the one associated with the license (if applicable)
            if (!empty($license->wc_order_id) && $license->wc_order_id != $wc_order_id) {
                SLM_API_Utility::output_api_response([
                    'result' => 'error',
                    'message' => 'The provided order ID does not match the one associated with this license.',
                    'error_code' => SLM_Error_Codes::ORDER_ID_MISMATCH,
                    'status_code' => 400,
                ]);
                return;
            }

            // Check the WooCommerce order status
            if ($order->get_status() !== 'completed') {
                SLM_API_Utility::output_api_response([
                    'result' => 'error',
                    'message' => 'The WooCommerce order has not been completed.',
                    'error_code' => SLM_Error_Codes::ORDER_NOT_COMPLETED,
                    'status_code' => 400,
                ]);
                return;
            }

            // Handle license renewal process
            $this->handle_license_renewal($wc_order_id, $license_key);

            // Return a success response
            SLM_API_Utility::output_api_response([
                'result' => 'success',
                'message' => 'License renewed successfully.',
                'license_key' => $license_key,
                'renewal_date' => current_time('mysql'),
                'status_code' => 200,
            ]);
        }
    } 

    private function handle_license_renewal($order_id, $license_key) {
        // Log the renewal action for debugging
        SLM_Helper_Class::write_log("Processing license renewal for Order ID: $order_id, License Key: $license_key");

        // Retrieve the license data
        $license_data = SLM_Utility::get_license_by_key($license_key);
        if (!$license_data) {
            SLM_Helper_Class::write_log("License key $license_key not found.");
            return; // Stop if the license is not found
        }

        global $wpdb;
        $license_table = SLM_TBL_LICENSE_KEYS;

        // Calculate the new expiration date
        $renewal_period = intval($license_data->slm_billing_length);
        $renewal_term = sanitize_text_field($license_data->slm_billing_interval);
        $new_expiry_date = date('Y-m-d', strtotime('+' . $renewal_period . ' ' . $renewal_term));

        // Update the license expiry date in the database
        $update_result = $wpdb->update(
            $license_table,
            ['date_expiry' => $new_expiry_date, 'lic_status' => 'active'],
            ['license_key' => $license_key]
        );

        if ($update_result !== false) {
            SLM_Helper_Class::write_log("License $license_key renewed successfully. New expiry date: $new_expiry_date.");
        } else {
            SLM_Helper_Class::write_log("Failed to renew license $license_key.");
        }

        // Add order note for renewal confirmation
        $order = wc_get_order($order_id);
        if ($order) {
            $order->add_order_note(sprintf(
                __('License Key %s renewed. New expiry date: %s', 'slm-plus'),
                $license_key,
                $new_expiry_date
            ));
            $order->save();
        }

        // Notify the user about the renewal
        wc_slm_notify_user_about_renewal($order, $license_key);
    }
}
