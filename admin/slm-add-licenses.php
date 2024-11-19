<?php
function slm_add_licenses_menu()
{
    global $wpdb;
    // Get the WordPress date format
    $slm_wp_date_format = get_option('date_format');
    $lic_status_table = SLM_TBL_LICENSE_STATUS;

    // Get the 'edit_record' parameter from the URL and sanitize it
    $id = !empty($_GET['edit_record']) ? intval(sanitize_text_field(wp_unslash($_GET['edit_record']))) : 0;

    $slm_options = get_option('slm_options');

    // Set initial variables for slm_billing_length and slm_billing_interval
    $slm_billing_length = SLM_API_Utility::get_slm_option('slm_billing_length');
    $slm_billing_interval = SLM_API_Utility::get_slm_option('slm_billing_interval');

    // Calculate date_expiry based on slm_billing_length and slm_billing_interval
    $date_created = date_i18n($slm_wp_date_format, strtotime('now'));
    $date_expiry = date_i18n($slm_wp_date_format, strtotime("+$slm_billing_length $slm_billing_interval"));

    // Get the active tab from the $_GET param
    $slm_lic_default_tab = null;
    $slm_lic_tab = isset($_GET['slm_tab']) ? sanitize_text_field(wp_unslash($_GET['slm_tab'])) : $slm_lic_default_tab;

    // Check if the form is submitted
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['slm_save_license'])) {
        // Verify the nonce before processing the form
        if (empty($_POST['slm_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['slm_nonce'])), 'slm_save_license')) {
            die(esc_html__('Security check failed', 'slm-plus'));
        }

        // Sanitize and validate the input data
        $data = [
            'license_key' => isset($_POST['license_key']) ? sanitize_text_field(wp_unslash($_POST['license_key'])) : '',
            'max_allowed_domains' => isset($_POST['max_allowed_domains']) ? intval(wp_unslash($_POST['max_allowed_domains'])) : 0,
            'max_allowed_devices' => isset($_POST['max_allowed_devices']) ? intval(wp_unslash($_POST['max_allowed_devices'])) : 0,
            'lic_status' => isset($_POST['lic_status']) ? sanitize_text_field(wp_unslash($_POST['lic_status'])) : '',
            'first_name' => isset($_POST['first_name']) ? sanitize_text_field(wp_unslash($_POST['first_name'])) : '',
            'last_name' => isset($_POST['last_name']) ? sanitize_text_field(wp_unslash($_POST['last_name'])) : '',
            'email' => isset($_POST['email']) && is_email(wp_unslash($_POST['email'])) ? sanitize_email(wp_unslash($_POST['email'])) : '', // Ensure unslash before sanitization
            'company_name' => isset($_POST['company_name']) ? sanitize_text_field(wp_unslash($_POST['company_name'])) : '',
            'txn_id' => isset($_POST['txn_id']) ? sanitize_text_field(wp_unslash($_POST['txn_id'])) : '',
            'manual_reset_count' => isset($_POST['manual_reset_count']) ? intval(wp_unslash($_POST['manual_reset_count'])) : 0,
            'purchase_id_' => isset($_POST['purchase_id_']) ? sanitize_text_field(wp_unslash($_POST['purchase_id_'])) : '',
            'date_created' => isset($_POST['date_created']) ? SLM_API_Utility::slm_validate_date(sanitize_text_field(wp_unslash($_POST['date_created']))) : date_i18n('Y-m-d'), // Default to today's date if not set
            'date_renewed' => isset($_POST['date_renewed']) ? SLM_API_Utility::slm_validate_date(sanitize_text_field(wp_unslash($_POST['date_renewed']))) : '',
            'date_activated' => isset($_POST['date_activated']) ? SLM_API_Utility::slm_validate_date(sanitize_text_field(wp_unslash($_POST['date_activated']))) : '',
            'product_ref' => isset($_POST['product_ref']) ? sanitize_text_field(wp_unslash($_POST['product_ref'])) : '',
            'until' => isset($_POST['until']) ? sanitize_text_field(wp_unslash($_POST['until'])) : '',
            'current_ver' => isset($_POST['current_ver']) ? sanitize_text_field(wp_unslash($_POST['current_ver'])) : '',
            'subscr_id' => isset($_POST['subscr_id']) ? sanitize_text_field(wp_unslash($_POST['subscr_id'])) : '',
            'lic_type' => isset($_POST['lic_type']) ? sanitize_text_field(wp_unslash($_POST['lic_type'])) : '',
            'date_expiry' => isset($_POST['lic_type']) && $_POST['lic_type'] === 'lifetime' ? gmdate('Y-m-d', strtotime('+200 years')) : (isset($_POST['date_expiry']) ? SLM_API_Utility::slm_validate_date(sanitize_text_field(wp_unslash($_POST['date_expiry']))) : ''),
            'item_reference' => isset($_POST['item_reference']) ? sanitize_text_field(wp_unslash($_POST['item_reference'])) : '',
            'slm_billing_length' => isset($_POST['slm_billing_length']) ? sanitize_text_field(wp_unslash($_POST['slm_billing_length'])) : '',
            'slm_billing_interval' => isset($_POST['slm_billing_interval']) ? sanitize_text_field(wp_unslash($_POST['slm_billing_interval'])) : '',
            'reminder_sent' => isset($_POST['reminder_sent']) ? intval(wp_unslash($_POST['reminder_sent'])) : 0,
            'reminder_sent_date' => isset($_POST['reminder_sent_date']) ? SLM_API_Utility::slm_validate_date(sanitize_text_field(wp_unslash($_POST['reminder_sent_date']))) : '',
        ];

        // Check for required fields
        if (empty($data['email']) || empty($data['date_created']) || ($data['lic_type'] !== 'lifetime' && empty($data['date_expiry'])) || empty($data['lic_type'])) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Required fields are missing.', 'slm-plus') . '</p></div>';
        } else {
            // Insert or update the data in the database
            if ($id) {
                $wpdb->update(SLM_TBL_LICENSE_KEYS, $data, ['id' => $id]);
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('License updated successfully.', 'slm-plus') . '</p></div>';
            } else {
                $wpdb->insert(SLM_TBL_LICENSE_KEYS, $data);
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('License created successfully.', 'slm-plus') . '</p>';
                echo '<a href="?page=slm_manage_license&edit_record=' . esc_attr($wpdb->insert_id) . '" class="button">' . esc_html__('View License', 'slm-plus') . '</a></p></div>';
            }
        }
    } else {
        // If editing, load existing data
        if ($id) {
            // Sanitize the $id to make sure it's an integer
            $id = intval($id);

            // Define the table name as a constant
            $table_name = SLM_TBL_LICENSE_KEYS;

            // Use $wpdb->prepare() for the actual query with a placeholder for the ID
            $query = $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE id = %d",  // Use %d for the integer placeholder
                $id
            );

            // Get the result using the prepared query
            $license = $wpdb->get_row($query);

            if ($license) {
                $data = (array) $license;
            } else {
                // If the license is not found, reset to create a new record
                $data = [
                    'license_key' => '',
                    'max_allowed_domains' => SLM_DEFAULT_MAX_DOMAINS,
                    'max_allowed_devices' => SLM_DEFAULT_MAX_DEVICES,
                    'lic_status' => 'pending',
                    'first_name' => '',
                    'last_name' => '',
                    'email' => '',
                    'company_name' => '',
                    'txn_id' => '',
                    'purchase_id_' => '',
                    'date_created' => date_i18n($slm_wp_date_format, strtotime('now')),
                    'date_renewed' => '',
                    'date_activated' => '',
                    'product_ref' => '',
                    'until' => '',
                    'current_ver' => '',
                    'subscr_id' => '',
                    'lic_type' => 'subscription',
                    'date_expiry' => $date_expiry, // Set a default expiry
                    'item_reference' => '',
                    'slm_billing_length' => $slm_billing_length,
                    'slm_billing_interval' => $slm_billing_interval,
                    'reminder_sent' => '0',
                    'manual_reset_count' => '',
                    'reminder_sent_date' => '0000-00-00'
                ];

                // Add error message that license key wasn't found
                echo '<div class="notice notice-error"><p>' . esc_html__('License key not found. Please create a new license.', 'slm-plus') . '</p></div>';
            }
        } else {
            // Prepare empty data for a new record
            $data = [
                'license_key' => '',
                'max_allowed_domains' => SLM_DEFAULT_MAX_DOMAINS,
                'max_allowed_devices' => SLM_DEFAULT_MAX_DEVICES,
                'lic_status' => 'pending',
                'first_name' => '',
                'last_name' => '',
                'email' => '',
                'company_name' => '',
                'txn_id' => '',
                'purchase_id_' => '',
                'date_created' => date_i18n($slm_wp_date_format, strtotime('now')),
                'date_renewed' => '',
                'date_activated' => '',
                'product_ref' => '',
                'until' => '',
                'current_ver' => '',
                'subscr_id' => '',
                'lic_type' => 'subscription',
                'date_expiry' => $date_expiry,
                'item_reference' => '',
                'slm_billing_length' => $slm_billing_length,
                'slm_billing_interval' => $slm_billing_interval,
                'reminder_sent' => '0',
                'manual_reset_count' => '',
                'reminder_sent_date' => '0000-00-00'
            ];

            // Generate a license key for new records
            $data['license_key'] = slm_get_license(KEY_API_PREFIX);
        }
    }

?>
    <div class="wrap">
        <h1><?php esc_html_e('SLM Plus - License Management', 'slm-plus'); ?></h1>

        <div class="slm_ajax_msg"></div>

        <?php
        $edit_record = isset($_GET['edit_record']) ? sanitize_text_field(wp_unslash($_GET['edit_record'])) : '';
        ?>
        <nav class="slm nav-tab-wrapper">
            <a href="?page=slm_manage_license<?php echo $edit_record ? '&slm_tab=default&edit_record=' . esc_attr($edit_record) : ''; ?>" class="nav-tab <?php if ($slm_lic_tab === null): ?>nav-tab-active<?php endif; ?>">
                <?php esc_html_e('License Information', 'slm-plus'); ?>
            </a>
            <?php if ($edit_record): ?>
                <a href="?page=slm_manage_license&slm_tab=activation<?php echo '&edit_record=' . esc_attr($edit_record); ?>" class="nav-tab <?php if ($slm_lic_tab === 'activation'): ?>nav-tab-active<?php endif; ?>">
                    <?php esc_html_e('Activations', 'slm-plus'); ?>
                </a>
            <?php endif; ?>
            <?php if ($edit_record): ?>
                <a href="?page=slm_manage_license&slm_tab=activity<?php echo '&edit_record=' . esc_attr($edit_record); ?>" class="nav-tab <?php if ($slm_lic_tab === 'activity'): ?>nav-tab-active<?php endif; ?>">
                    <?php esc_html_e('Activity', 'slm-plus'); ?>
                </a>
            <?php endif; ?>
        </nav>


        <div class="slm tab-content">
            <?php switch ($slm_lic_tab):

                case 'activity': ?>

                    <?php
                    // Retrieve the license key for the current record
                    $license_key = esc_attr($data['license_key']);

                    // Fetch the log data using a utility function to handle the database query
                    $log_entries = SLM_Helper_Class::get_license_logs($license_key);

                    // Display the log table if there are any log entries
                    if ($log_entries) {
                    ?>
                        <div class="wrap">
                            <h2><?php esc_html_e('Activity Log', 'slm-plus'); ?></h2>
                            <table class="widefat striped">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('ID', 'slm-plus'); ?></th>
                                        <th><?php esc_html_e('Action', 'slm-plus'); ?></th>
                                        <th><?php esc_html_e('Time', 'slm-plus'); ?></th>
                                        <th><?php esc_html_e('Source', 'slm-plus'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($log_entries as $entry): ?>
                                        <tr>
                                            <td><?php echo esc_html($entry['id']); ?></td>
                                            <td><?php echo esc_html($entry['slm_action']); ?></td>
                                            <td><?php echo esc_html($entry['time']); ?></td>
                                            <td><?php echo esc_html($entry['source']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php
                    } else {
                        // Show a message if there are no log entries
                        echo '<p>' . esc_html__('No activity log found for this license.', 'slm-plus') . '</p>';
                    }
                    ?>

                <?php
                    break;
                case 'activation': ?>

                    <?php
                    // Make sure this runs only on the right page
                    if (isset($_GET['edit_record']) && !empty($_GET['edit_record'])) {
                        global $wpdb;

                        $license_key = esc_attr($data['license_key']);

                        // Fetch the max_allowed_domains and max_allowed_devices from the license key table
                        $license_info = $wpdb->get_row($wpdb->prepare(
                            "SELECT max_allowed_domains, max_allowed_devices FROM " . SLM_TBL_LICENSE_KEYS . " WHERE license_key = %s",
                            $license_key
                        ));

                        // Ensure the max values are retrieved and set them to default values if not found
                        $max_domains = isset($license_info->max_allowed_domains) ? intval($license_info->max_allowed_domains) : 0;
                        $max_devices = isset($license_info->max_allowed_devices) ? intval($license_info->max_allowed_devices) : 0;

                        // Fetch the current number of registered domains for this license key
                        $registered_domains = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM " . SLM_TBL_LIC_DOMAIN . " WHERE lic_key = %s",
                            $license_key
                        ));

                        // Fetch the current number of registered devices for this license key
                        $registered_devices = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM " . SLM_TBL_LIC_DEVICES . " WHERE lic_key = %s",
                            $license_key
                        ));

                        // Ensure the count values are integers
                        $registered_domains = isset($registered_domains) ? intval($registered_domains) : 0;
                        $registered_devices = isset($registered_devices) ? intval($registered_devices) : 0;

                        // Calculate how many domains and devices are left
                        $domains_left = $max_domains - $registered_domains;
                        $devices_left = $max_devices - $registered_devices;

                        // Ensure the result is not negative (to handle edge cases)
                        $domains_left = max(0, $domains_left);
                        $devices_left = max(0, $devices_left);

                        // Fetch all registered domains for this license key
                        $registered_domains_data = $wpdb->get_results($wpdb->prepare(
                            "SELECT id, registered_domain FROM " . SLM_TBL_LIC_DOMAIN . " WHERE lic_key = %s",
                            $license_key
                        ));

                        // Fetch all registered devices for this license key
                        $registered_devices_data = $wpdb->get_results($wpdb->prepare(
                            "SELECT id, registered_devices FROM " . SLM_TBL_LIC_DEVICES . " WHERE lic_key = %s",
                            $license_key
                        ));


                        $slm_ajax_uri = '';
                        $slm_deactivate_nonce = wp_create_nonce('slmplus_delete_activation_nonce');

                        // Render the table
                    ?>

                        <div class="wrap">
                            <h2><?php esc_html_e('Activation', 'slm-plus'); ?></h2>
                            <p>
                                <strong><?php esc_html_e('Domains Left', 'slm-plus'); ?>:</strong> <?php echo esc_html($domains_left); ?><br>
                                <strong><?php esc_html_e('Devices Left', 'slm-plus'); ?>:</strong> <?php echo esc_html($devices_left); ?>
                            </p>

                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('ID', 'slm-plus'); ?></th>
                                        <th><?php esc_html_e('License Key', 'slm-plus'); ?></th>
                                        <th><?php esc_html_e('Type', 'slm-plus'); ?></th>
                                        <th><?php esc_html_e('Origin', 'slm-plus'); ?></th> <!-- New column for Origin -->
                                        <th><?php esc_html_e('Action', 'slm-plus'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($registered_domains_data): ?>
                                        <?php foreach ($registered_domains_data as $domain_entry): ?>
                                            <?php
                                            $slm_ajax_uri = esc_url(home_url('/')) . '?slm_action=slm_deactivate&secret_key=' . VERIFY_KEY_API . '&license_key=' . esc_html($license_key) . '&registered_domain=' . esc_html($domain_entry->registered_domain);
                                            ?>
                                            <tr id="activation-row-<?php echo esc_attr($domain_entry->id); ?>" class="lic-entry-<?php echo esc_attr($domain_entry->id); ?>">
                                                <td><?php echo esc_html($domain_entry->id); ?></td>
                                                <td><?php echo esc_html($license_key); ?></td>
                                                <td><?php esc_html_e('Domain', 'slm-plus'); ?></td>
                                                <td><?php echo esc_html($domain_entry->registered_domain); ?></td> <!-- Display Domain Origin here -->
                                                <td>
                                                    <button class="button deactivate_registration" data-activation_type="domain" data-id="<?php echo esc_attr($domain_entry->id); ?>" data-device="<?php echo esc_attr($domain_entry->registered_domain); ?>" data-table="registered_domain" data-ajax_uri="<?php echo esc_url($slm_ajax_uri); ?>" data-nonce="<?php echo esc_attr($slm_deactivate_nonce); ?>">
                                                        <?php esc_html_e('Remove', 'slm-plus'); ?>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                    <?php if ($registered_devices_data): ?>
                                        <?php foreach ($registered_devices_data as $device_entry): ?>
                                            <?php
                                            $slm_ajax_uri = esc_url(home_url('/')) . '?slm_action=slm_deactivate&secret_key=' . VERIFY_KEY_API . '&license_key=' . esc_html($license_key) . '&registered_devices=' . esc_html($device_entry->registered_devices);
                                            ?>

                                            <tr id="activation-row-<?php echo esc_attr($device_entry->id); ?>" class="lic-entry-<?php echo esc_attr($device_entry->id); ?>">
                                                <td><?php echo esc_html($device_entry->id); ?></td>
                                                <td><?php echo esc_html($license_key); ?></td>
                                                <td><?php esc_html_e('Device', 'slm-plus'); ?></td>
                                                <td><?php echo esc_html($device_entry->registered_devices); ?></td> <!-- Display Device Origin here -->
                                                <td>

                                                    <button class="button deactivate_registration" data-activation_type="device" data-id="<?php echo esc_attr($device_entry->id); ?>" data-device="<?php echo esc_attr($device_entry->registered_devices); ?>" data-table="registered_devices" data-ajax_uri="<?php echo esc_url($slm_ajax_uri); ?>" data-nonce="<?php echo esc_attr($slm_deactivate_nonce); ?>">
                                                        <?php esc_html_e('Remove', 'slm-plus'); ?>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                    <?php if (!$registered_domains_data && !$registered_devices_data): ?>
                                        <tr>
                                            <td colspan="5"><?php esc_html_e('No activations found', 'slm-plus'); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <script type="text/javascript">
                            jQuery(document).ready(function($) {
                                // Event listener for the deactivate button
                                $('.deactivate_registration').click(function(event) {
                                    event.preventDefault(); // Prevent default behavior

                                    // Confirmation prompt
                                    if (!confirm('Are you sure you want to deactivate this license? This action cannot be undone.')) {
                                        return; // If the user clicks "Cancel", do nothing
                                    }

                                    // Store the button element reference
                                    var $button = $(this);

                                    // Get the data-ajax_uri and data-nonce from the clicked button
                                    var ajax_uri = $button.data('ajax_uri');
                                    var nonce = $button.data('nonce'); // Get the nonce

                                    // Change the button text to indicate action
                                    $button.text('Removing');

                                    // Make the AJAX POST request with nonce
                                    $.post(ajax_uri, {
                                        _wpnonce: nonce // Include the nonce in the request
                                    }, function(response) {
                                        ////console.log(response); // Debugging to ensure we're getting the response

                                        // Handle success response
                                        if (response.result === 'success') {
                                            // Show WordPress-style success message
                                            $('.slm_ajax_msg').html('<div class="notice notice-success is-dismissible"><p>' + response.message + '</p></div>');

                                            // Remove the entire row or element containing the button
                                            $button.closest('tr').remove(); // Adjust the selector to the parent element of the row or item you want to remove
                                        } else {
                                            // Show WordPress-style error message
                                            $('.slm_ajax_msg').html('<div class="notice notice-error is-dismissible"><p>License key was not deactivated!</p></div>');
                                        }
                                    }).fail(function() {
                                        // Handle AJAX request failure
                                        $('.slm_ajax_msg').html('<div class="notice notice-error is-dismissible"><p>Error during the AJAX request.</p></div>');
                                    });
                                });
                            });
                        </script>

                    <?php
                    }
                    // Register AJAX handler for deleting activations (without page reload)
                    add_action('wp_ajax_delete_activation', function () {
                        // Security check
                        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(wp_unslash($_POST['_wpnonce']), 'slmplus_delete_activation_nonce')) {
                            wp_send_json_error([
                                'result' => 'error',
                                'message' => __('Nonce verification failed.', 'slm-plus'),
                                'error_code' => 401
                            ]);
                        }

                        global $wpdb;

                        // Check if the activation ID and type are provided and valid
                        if (!isset($_POST['activation_id']) || !is_numeric(wp_unslash($_POST['activation_id'])) || !isset($_POST['activation_type']) || empty($_POST['activation_type'])) {
                            wp_send_json_error([
                                'result' => 'error',
                                'message' => __('Invalid activation data.', 'slm-plus'),
                                'error_code' => 400
                            ]);
                        }
                        $activation_id = isset($_POST['activation_id']) ? intval(wp_unslash($_POST['activation_id'])) : 0;
                        $activation_type = isset($_POST['activation_type']) ? sanitize_text_field(wp_unslash($_POST['activation_type'])) : '';

                        // Delete the activation from the correct table
                        if ($activation_type === 'domain') {
                            $result = $wpdb->delete(SLM_TBL_LIC_DOMAIN, ['id' => $activation_id]);
                        } elseif ($activation_type === 'device') {
                            $result = $wpdb->delete(SLM_TBL_LIC_DEVICES, ['id' => $activation_id]);
                        } else {
                            wp_send_json_error([
                                'result' => 'error',
                                'message' => __('Invalid activation type.', 'slm-plus'),
                                'error_code' => 400
                            ]);
                        }

                        // Handle result
                        if ($result !== false) {
                            wp_send_json_success([
                                'result' => 'success',
                                'message' => __('The license key has been deactivated for this domain.', 'slm-plus'),
                                'error_code' => 360
                            ]);
                        } else {
                            wp_send_json_error([
                                'result' => 'error',
                                'message' => __('Error deleting activation.', 'slm-plus'),
                                'error_code' => 500
                            ]);
                        }
                    });
                    ?>

                <?php
                    break;
                default: ?>
                    <form method="post" action="" id="slm_license_form">
                        <?php wp_nonce_field('slm_save_license', 'slm_nonce'); ?>
                        <?php if ($id) : ?>
                            <input name="edit_record" type="hidden" value="<?php echo esc_attr($id); ?>" />
                        <?php endif; ?>

                        <!-- Subscriber Information Section -->
                        <h2 class="hndle"><?php esc_html_e('Subscriber Information', 'slm-plus'); ?></h2>
                        <div class="postbox">
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th scope="row"><label for="first_name"><?php esc_html_e('First Name', 'slm-plus'); ?> <span style="color: red;">*</span></label></th>
                                        <td>
                                            <input name="first_name" type="text" id="first_name" value="<?php echo isset($data['first_name']) ? esc_attr($data['first_name']) : ''; ?>" class="regular-text user-search-input" required autocomplete="off" />
                                            <div class="user-search-suggestions wp-core-ui" data-field="first_name"></div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="last_name"><?php esc_html_e('Last Name', 'slm-plus'); ?> <span style="color: red;">*</span></label></th>
                                        <td>
                                            <input name="last_name" type="text" id="last_name" value="<?php echo isset($data['last_name']) ? esc_attr($data['last_name']) : ''; ?>" class="regular-text user-search-input" required autocomplete="off" />
                                            <div class="user-search-suggestions wp-core-ui" data-field="last_name"></div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="email"><?php esc_html_e('Email', 'slm-plus'); ?> <span style="color: red;">*</span></label></th>
                                        <td>
                                            <input name="email" type="email" id="email" value="<?php echo isset($data['email']) ? esc_attr($data['email']) : ''; ?>" class="regular-text user-search-input" required autocomplete="off" />
                                            <div class="user-search-suggestions wp-core-ui" data-field="email"></div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="company_name"><?php esc_html_e('Company Name', 'slm-plus'); ?></label></th>
                                        <td>
                                            <input name="company_name" type="text" id="company_name" value="<?php echo isset($data['company_name']) ? esc_attr($data['company_name']) : ''; ?>" class="regular-text" />
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <!-- Hidden field to store the selected user ID -->
                        <input type="hidden" name="user_id" id="user_id" value="<?php echo isset($data['user_id']) ? esc_attr($data['user_id']) : ''; ?>" />



                        <h2 class="hndle"><?php esc_html_e('License Information', 'slm-plus'); ?></h2>
                        <div class="postbox">
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="license_key"><?php esc_html_e('License Key', 'slm-plus'); ?></label>
                                        </th>
                                        <td>
                                            <input name="license_key" type="text" id="license_key" value="<?php echo esc_attr($data['license_key']); ?>" class="regular-text"
                                                <?php if (isset($_GET['edit']) || !empty($id)): ?>readonly<?php endif; ?> />
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row"><label for="max_allowed_domains"><?php esc_html_e('Max Allowed Domains', 'slm-plus'); ?></label></th>
                                        <td><input name="max_allowed_domains" type="number" id="max_allowed_domains" value="<?php echo esc_attr($data['max_allowed_domains']); ?>" class="regular-text" /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="max_allowed_devices"><?php esc_html_e('Max Allowed Devices', 'slm-plus'); ?></label></th>
                                        <td><input name="max_allowed_devices" type="number" id="max_allowed_devices" value="<?php echo esc_attr($data['max_allowed_devices']); ?>" class="regular-text" /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="lic_status"><?php esc_html_e('License Status', 'slm-plus'); ?></label></th>
                                        <td>
                                            <?php
                                            // Fetch all status records
                                            $lic_status_table = esc_sql($lic_status_table);

                                            // Now prepare the query with placeholders for values, not the table name.
                                            $query = "SELECT status_key, status_label FROM $lic_status_table";  // No placeholders needed for table name
                                            $statuses = $wpdb->get_results($query, ARRAY_A);

                                            // Create the <select> element
                                            echo '<select name="lic_status" id="lic_status" class="regular-text">';
                                            foreach ($statuses as $status) {
                                                // Set the selected attribute if the current status matches
                                                $selected = selected($data['lic_status'], $status['status_key'], false);
                                                echo '<option value="' . esc_attr($status['status_key']) . '" ' . esc_attr($selected) . '>' . esc_html($status['status_label']) . '</option>';
                                            }
                                            echo '</select>';
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="date_created"><?php esc_html_e('Date Created', 'slm-plus'); ?> <span style="color: red;">*</span></label></th>
                                        <td><input name="date_created" type="date" id="date_created" value="<?php echo esc_attr($data['date_created']); ?>" <?php if (isset($_GET['edit']) || !empty($id)): ?>readonly<?php endif; ?> class="regular-text datepicker" required />
                                            <p class="description" id="new-admin-email-description">
                                                <?php
                                                // Translators: %s is the date format used for input
                                                printf(esc_html__('Display Format: %s (input: YYYY-MM-DD)', 'slm-plus'), esc_html($slm_wp_date_format));
                                                ?>
                                            </p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="lic_type"><?php esc_html_e('License Type', 'slm-plus'); ?> <span style="color: red;">*</span></label></th>
                                        <td>
                                            <select name="lic_type" id="lic_type" class="regular-text" required>
                                                <option value="subscription" <?php selected($data['lic_type'], 'subscription'); ?>><?php esc_html_e('Subscription', 'slm-plus'); ?></option>
                                                <option value="lifetime" <?php selected($data['lic_type'], 'lifetime'); ?>><?php esc_html_e('Lifetime', 'slm-plus'); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="date_expiry"><?php esc_html_e('Expiration Date', 'slm-plus'); ?> <span style="color: red;">*</span></label></th>
                                        <td><input name="date_expiry" type="date" id="date_expiry" value="<?php echo esc_attr($data['date_expiry']); ?>" class="regular-text datepicker" required />
                                            <p class="description" id="new-admin-email-description">
                                                <?php
                                                // Translators: %s is the date format for input
                                                printf(esc_html__('Selecting a future date will automatically adjust the renewal term.<br>Choose this date to set when the license should renew or expire. <br>Format: %s (input: YYYY-MM-DD).', 'slm-plus'), esc_html($slm_wp_date_format));
                                                ?>
                                            </p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label><?php esc_html_e('Renewal', 'slm-plus'); ?></label></th>
                                        <td>
                                            <div style="display: flex; gap: 10px; width: 350px;">
                                                <div style="width: 50%;">
                                                    <label for="slm_billing_length" style="font-weight: bold; display: block; margin-bottom: 5px;"><?php esc_html_e('Billing Length', 'slm-plus'); ?></label>
                                                    <input name="slm_billing_length" type="text" id="slm_billing_length" value="<?php echo esc_attr($data['slm_billing_length']); ?>" class="regular-text" style="width: 100%;" />
                                                    <p class="description" id="billing_length_description" style="margin-top: 5px; font-size: 12px; line-height: 1.2;">
                                                        <?php esc_html_e('Sets how often the license renews. E.g., a length of 2 with a term of years means the license renews every 2 years.', 'slm-plus'); ?>
                                                    </p>
                                                </div>

                                                <div style="width: 50%;">
                                                    <label for="slm_billing_interval" style="font-weight: bold; display: block; margin-bottom: 5px;"><?php esc_html_e('Expiration Term', 'slm-plus'); ?></label>
                                                    <select name="slm_billing_interval" id="slm_billing_interval" class="regular-text" style="width: 100%;">
                                                        <option value="days" <?php selected($data['slm_billing_interval'], 'days'); ?>><?php esc_html_e('Day(s)', 'slm-plus'); ?></option>
                                                        <option value="months" <?php selected($data['slm_billing_interval'], 'months'); ?>><?php esc_html_e('Month(s)', 'slm-plus'); ?></option>
                                                        <option value="years" <?php selected($data['slm_billing_interval'], 'years'); ?>><?php esc_html_e('Year(s)', 'slm-plus'); ?></option>
                                                    </select>
                                                    <p class="description" id="expiration_term_description" style="margin-top: 5px; font-size: 12px; line-height: 1.2;">
                                                        <?php esc_html_e('Choose the renewal period: days, months, or years.', 'slm-plus'); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="subscr_id"><?php esc_html_e('Subscriber ID', 'slm-plus'); ?></label></th>
                                        <td><input name="subscr_id" type="text" id="subscr_id" value="<?php echo esc_attr($data['subscr_id']); ?>" class="regular-text" /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="date_renewed"><?php esc_html_e('Date Renewed', 'slm-plus'); ?></label></th>
                                        <td>
                                            <input name="date_renewed" type="date" id="date_renewed"
                                                value="<?php echo ($data['date_renewed'] === '0000-00-00' || empty($data['date_renewed'])) ? '' : esc_attr($data['date_renewed']); ?>"
                                                class="regular-text datepicker" />
                                            <p class="description" id="new-admin-email-description">
                                                <?php
                                                // Translators: %s is the date format for input (e.g., YYYY-MM-DD)
                                                printf(esc_html__('Display Format: %s (input: YYYY-MM-DD)', 'slm-plus'), esc_html($slm_wp_date_format));
                                                ?>
                                            </p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="date_activated"><?php esc_html_e('Date Activated', 'slm-plus'); ?></label></th>
                                        <td>
                                            <input name="date_activated" type="date" id="date_activated"
                                                value="<?php echo ($data['date_activated'] === '0000-00-00' || empty($data['date_activated'])) ? '' : esc_attr($data['date_activated']); ?>"
                                                class="regular-text datepicker" />
                                            <p class="description" id="new-admin-email-description">
                                                <?php
                                                // Translators: %s is the date format for input (e.g., YYYY-MM-DD)
                                                printf(esc_html__('Display Format: %s (input: YYYY-MM-DD)', 'slm-plus'), esc_html($slm_wp_date_format));
                                                ?>
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <h2 class="hndle"><?php esc_html_e('Transaction Information', 'slm-plus'); ?></h2>
                        <div class="postbox">
                            <div class="inside">
                                <table class="form-table">

                                    <tr>
                                        <th scope="row"><label for="item_reference"><?php esc_html_e('Item Reference', 'slm-plus'); ?></label></th>
                                        <td><input name="item_reference" type="text" id="item_reference" value="<?php echo esc_attr($data['item_reference']); ?>" class="regular-text" /></td>
                                    </tr>

                                    <tr>
                                        <th scope="row"><label for="txn_id"><?php esc_html_e('Transaction ID', 'slm-plus'); ?></label></th>
                                        <td><input name="txn_id" type="text" id="txn_id" value="<?php echo esc_attr($data['txn_id']); ?>" class="regular-text" /></td>
                                    </tr>

                                    <tr>
                                        <th scope="row"><label for="purchase_id_"><?php esc_html_e('Purchase ID', 'slm-plus'); ?></label></th>
                                        <td><input name="purchase_id_" type="text" id="purchase_id_" value="<?php echo esc_attr($data['purchase_id_']); ?>" class="regular-text" /></td>
                                    </tr>

                                    <tr>
                                        <th scope="row"><label for="product_ref"><?php esc_html_e('Product Reference', 'slm-plus'); ?></label></th>
                                        <td><input name="product_ref" type="text" id="product_ref" value="<?php echo esc_attr($data['product_ref']); ?>" class="regular-text" /></td>
                                    </tr>

                                </table>
                            </div>
                        </div>

                        <h2 class="hndle"><?php esc_html_e('Other', 'slm-plus'); ?></h2>
                        <div class="postbox">
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th scope="row"><label for="until"><?php esc_html_e('Until', 'slm-plus'); ?></label></th>
                                        <td><input name="until" type="text" id="until" value="<?php echo esc_attr($data['until']); ?>" class="regular-text" /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="current_ver"><?php esc_html_e('Current Version', 'slm-plus'); ?></label></th>
                                        <td><input name="current_ver" type="text" id="current_ver" value="<?php echo esc_attr($data['current_ver']); ?>" class="regular-text" /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="reminder_sent"><?php esc_html_e('Reminder Sent', 'slm-plus'); ?></label></th>
                                        <td><input name="reminder_sent" type="text" id="reminder_sent" value="<?php echo esc_attr($data['reminder_sent']); ?>" class="regular-text" /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="reminder_sent_date"><?php esc_html_e('Reminder Sent Date', 'slm-plus'); ?></label></th>
                                        <td>
                                            <input name="reminder_sent_date" type="date" id="reminder_sent_date"
                                                value="<?php echo ($data['reminder_sent_date'] === '0000-00-00') ? '' : esc_attr($data['reminder_sent_date']); ?>"
                                                class="regular-text datepicker"
                                                placeholder="YYYY-MM-DD" />
                                            <p class="description" id="new-admin-email-description">
                                                <?php
                                                // Translators: %s is the date format used for input (e.g., YYYY-MM-DD)
                                                printf(esc_html__('Display Format: %s (input: YYYY-MM-DD)', 'slm-plus'), esc_html($slm_wp_date_format));
                                                ?>
                                            </p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="manual_reset_count"><?php esc_html_e('Manual Reset Count', 'slm-plus'); ?></label></th>
                                        <td><input name="manual_reset_count" type="number" id="manual_reset_count" value="<?php echo esc_attr($data['manual_reset_count']); ?>" class="regular-text" /></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <?php
                        if (isset($_GET['edit_record']) && !empty($_GET['edit_record'])) : ?>
                            <?php submit_button(esc_html__('Update License', 'slm-plus'), 'primary', 'slm_save_license'); ?>
                        <?php else: ?>
                            <?php submit_button(esc_html__('Create License', 'slm-plus'), 'primary', 'slm_save_license'); ?>
                        <?php endif; ?>
                        <script type="text/javascript">
                            document.addEventListener('DOMContentLoaded', function() {
                                const form = document.querySelector('form'); // Adjust this selector to target your specific form

                                form.addEventListener('submit', function(event) {
                                    // Scroll to the top immediately
                                    window.scrollTo({
                                        top: 0,
                                        behavior: 'smooth'
                                    });
                                });
                            });
                        </script>
                    </form>
            <?php
                    break;
            endswitch; ?>
        </div>
    </div>
<?php
}
