<?php
//slm_add_licenses_menu

function slm_add_licenses_menu()
{
    global $wpdb;
    // Get the WordPress date format
    $slm_wp_date_format = get_option('date_format');
    $lic_status_table = SLM_TBL_LICENSE_STATUS;

    $id = !empty($_GET['edit_record']) ? intval($_GET['edit_record']) : 0; // Check for 'edit_record' parameter in the URL
    $slm_options = get_option('slm_options');

    //Get the active tab from the $_GET param
    $slm_lic_default_tab = null;
    $slm_lic_tab = isset($_GET['slm_tab']) ? $_GET['slm_tab'] : $slm_lic_default_tab;

    // Check if the form is submitted
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['slm_save_license'])) {
        // Verify the nonce before processing the form
        if (!isset($_POST['slm_nonce']) || !wp_verify_nonce($_POST['slm_nonce'], 'slm_save_license')) {
            die(__('Security check failed', 'slmplus'));
        }

        // Sanitize and validate the input data
        $data = [
            'license_key' => sanitize_text_field($_POST['license_key']),
            'max_allowed_domains' => intval($_POST['max_allowed_domains']),
            'max_allowed_devices' => intval($_POST['max_allowed_devices']),
            'lic_status' => sanitize_text_field($_POST['lic_status']),
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            
            // Validate email
            'email' => is_email($_POST['email']) ? sanitize_email($_POST['email']) : '',
            
            'company_name' => sanitize_text_field($_POST['company_name']),
            'txn_id' => sanitize_text_field($_POST['txn_id']),
            'manual_reset_count' => intval($_POST['manual_reset_count']),
            'purchase_id_' => sanitize_text_field($_POST['purchase_id_']),
            
            // Date validation
            'date_created' => SLM_API_Utility::slm_validate_date($_POST['date_created']),
            'date_renewed' => SLM_API_Utility::slm_validate_date($_POST['date_renewed']),
            'date_activated' => SLM_API_Utility::slm_validate_date($_POST['date_activated']),
            
            'product_ref' => sanitize_text_field($_POST['product_ref']),
            'until' => sanitize_text_field($_POST['until']),
            'current_ver' => sanitize_text_field($_POST['current_ver']),
            'subscr_id' => sanitize_text_field($_POST['subscr_id']),
            'lic_type' => sanitize_text_field($_POST['lic_type']),
            
            // Handle 'lifetime' license expiration properly
            'date_expiry' => ($_POST['lic_type'] == 'lifetime') ? '0000-00-00' : SLM_API_Utility::slm_validate_date($_POST['date_expiry']),
            
            'item_reference' => sanitize_text_field($_POST['item_reference']),
            'slm_billing_length' => sanitize_text_field($_POST['slm_billing_length']),
            'slm_billing_interval' => sanitize_text_field($_POST['slm_billing_interval']),
            'reminder_sent' => intval($_POST['reminder_sent']),
            
            // Reminder date validation
            'reminder_sent_date' => SLM_API_Utility::slm_validate_date($_POST['reminder_sent_date'])
        ];

        // Check for required fields
        if (empty($data['email']) || empty($data['date_created']) || ($data['lic_type'] != 'lifetime' && empty($data['date_expiry'])) || empty($data['lic_type'])) {
            // Custom error notice displayed inline
            echo '<div class="notice notice-error"><p>' . __('Required fields are missing.', 'slmplus') . '</p></div>';
        } else {
            // Insert or update the data in the database
            if ($id) {
                $wpdb->update(SLM_TBL_LICENSE_KEYS, $data, ['id' => $id]);
                // Custom success notice for updating a license
                echo '<div class="notice notice-success is-dismissible"><p>' . __('License updated successfully.', 'slmplus') . '</p></div>';
            } else {
                $wpdb->insert(SLM_TBL_LICENSE_KEYS, $data);
                // Custom success notice for creating a license
                echo '<div class="notice notice-success is-dismissible"><p>' . __('License created successfully.', 'slmplus') . '</p>';
                echo '<a href="?page=slm_manage_license&edit_record=' . esc_attr($wpdb->insert_id) . '" class="button">' . __('View License', 'slmplus') . '</a></p></div>';
            }
        }
    }
    else {
        // If editing, load existing data
        // Ensure the correct data types and default values for new records
        if ($id) {
            $license = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . SLM_TBL_LICENSE_KEYS . " WHERE id = %d", $id));
            if ($license) {
                $data = (array) $license;
            }
        } else {

            // Prepare empty data for new record
            $data = [
                'license_key' => '',
                'max_allowed_domains' => SLM_DEFAULT_MAX_DOMAINS,
                'max_allowed_devices' => SLM_DEFAULT_MAX_DEVICES,
                'lic_status' => 'pending', // Default value
                'first_name' => '',
                'last_name' => '',
                'email' => '',
                'company_name' => '',
                'txn_id' => '',
                'manual_reset_count' => '',
                'purchase_id_' => '',
                'date_created' => date_i18n($slm_wp_date_format, strtotime('now')), // Use WP date format
                'date_renewed' => '0000-00-00',
                'date_activated' => '0000-00-00',
                'product_ref' => '',
                'until' => '',
                'current_ver' => '',
                'subscr_id' => '',
                'lic_type' => 'subscription',
                'date_expiry' => date_i18n($slm_wp_date_format, strtotime('+1 year')), // Use WP date format
                'item_reference' => '',
                'slm_billing_length' => '',
                'slm_billing_interval' => 'days', // Default value
                'reminder_sent' => '0',
                'reminder_sent_date' => '0000-00-00'
            ];


            // Generate a license key if it's a new record
            if (!isset($editing_record)) {
                $editing_record = new stdClass();
            }
            
            $lic_key_prefix = isset($slm_options['lic_prefix']) ? $slm_options['lic_prefix'] : '';
            $data['license_key'] = slm_get_license($lic_key_prefix);
        }
    }

?>
    <div class="wrap">
        <h1><?php _e('SLM Plus - License Management', 'slmplus'); ?></h1>

        <div class="slm_ajax_msg"></div>

        <nav class="slm nav-tab-wrapper">
            <a href="?page=slm_manage_license<?php echo isset($_GET['edit_record']) ? '&slm_tab=default&edit_record=' . esc_attr($_GET['edit_record']) : ''; ?>" class="nav-tab <?php if ($slm_lic_tab === null): ?>nav-tab-active<?php endif; ?>">
                <?php _e('License Information', 'slmplus'); ?>
            </a>
            <?php if (isset($_GET['edit_record'])): ?>
                <a href="?page=slm_manage_license&slm_tab=activation<?php echo isset($_GET['edit_record']) ? '&edit_record=' . esc_attr($_GET['edit_record']) : ''; ?>" class="nav-tab <?php if ($slm_lic_tab === 'activation'): ?>nav-tab-active<?php endif; ?>">
                    <?php _e('Activations', 'slmplus'); ?>
                </a>
            <?php endif; ?>
        </nav>

        <div class="slm tab-content">
            <?php switch ($slm_lic_tab): 
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
                        

                        $slm_ajax_uri ='';
                        $slm_deactivate_nonce = wp_create_nonce('slmplus_delete_activation_nonce');

                        // Render the table
                        ?>

                        <div class="wrap">
                            <h2><?php _e('Activation', 'slmplus'); ?></h2> 
                            <p>
                                <strong><?php _e('Domains Left', 'slmplus'); ?>:</strong> <?php echo esc_html($domains_left); ?><br>
                                <strong><?php _e('Devices Left', 'slmplus'); ?>:</strong> <?php echo esc_html($devices_left); ?>
                            </p>        

                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php _e('ID', 'slmplus'); ?></th>
                                        <th><?php _e('License Key', 'slmplus'); ?></th>
                                        <th><?php _e('Type', 'slmplus'); ?></th>
                                        <th><?php _e('Origin', 'slmplus'); ?></th> <!-- New column for Origin -->
                                        <th><?php _e('Action', 'slmplus'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($registered_domains_data): ?>
                                        <?php foreach ($registered_domains_data as $domain_entry): ?>
                                            <?php
                                                $slm_ajax_uri = esc_url(home_url('/')).'?slm_action=slm_deactivate&secret_key=' . VERIFY_KEY_API .'&license_key='.esc_html($license_key).'&registered_domain='. esc_html($domain_entry->registered_domain);
                                            ?>
                                            <tr id="activation-row-<?php echo esc_attr($domain_entry->id); ?>" class="lic-entry-<?php echo esc_attr($domain_entry->id); ?>">
                                                <td><?php echo esc_html($domain_entry->id); ?></td>
                                                <td><?php echo esc_html($license_key); ?></td>
                                                <td><?php _e('Domain', 'slmplus'); ?></td>
                                                <td><?php echo esc_html($domain_entry->registered_domain); ?></td> <!-- Display Domain Origin here -->
                                                <td>
                                                <button class="button deactivate_registration" data-activation_type="domain" data-id="<?php echo esc_attr($domain_entry->id); ?>" data-device="<?php echo esc_attr($domain_entry->registered_domain); ?>" data-table="registered_domain" data-ajax_uri="<?php echo $slm_ajax_uri; ?>"  data-nonce="<?php echo esc_attr($slm_deactivate_nonce); ?>">
                                                        <?php _e('Remove', 'slmplus'); ?>
                                                </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                    <?php if ($registered_devices_data): ?>
                                        <?php foreach ($registered_devices_data as $device_entry): ?>
                                            <?php
                                                $slm_ajax_uri = esc_url(home_url('/')).'?slm_action=slm_deactivate&secret_key=' . VERIFY_KEY_API .'&license_key='.esc_html($license_key).'&registered_devices='. esc_html($device_entry->registered_devices);
                                            ?>

                                            <tr id="activation-row-<?php echo esc_attr($device_entry->id); ?>" class="lic-entry-<?php echo esc_attr($device_entry->id); ?>">
                                                <td><?php echo esc_html($device_entry->id); ?></td>
                                                <td><?php echo esc_html($license_key); ?></td>
                                                <td><?php _e('Device', 'slmplus'); ?></td>
                                                <td><?php echo esc_html($device_entry->registered_devices); ?></td> <!-- Display Device Origin here -->
                                                <td>


                                                <button class="button deactivate_registration" data-activation_type="device" data-id="<?php echo esc_attr($device_entry->id); ?>" data-device="<?php echo esc_attr($device_entry->registered_devices); ?>" data-table="registered_devices" data-ajax_uri="<?php echo $slm_ajax_uri; ?>" data-nonce="<?php echo esc_attr($slm_deactivate_nonce); ?>">
                                                        <?php _e('Remove', 'slmplus'); ?>
                                                </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                    <?php if (!$registered_domains_data && !$registered_devices_data): ?>
                                        <tr><td colspan="5"><?php _e('No activations found', 'slmplus'); ?></td></tr>
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
                                        console.log(response); // Debugging to ensure we're getting the response
                                        
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
                    add_action('wp_ajax_delete_activation', function() {
                        // Security check
                        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'slmplus_delete_activation_nonce')) {
                            wp_send_json_error([
                                'result' => 'error',
                                'message' => __('Nonce verification failed.', 'slmplus'),
                                'error_code' => 401
                            ]);
                        }

                        global $wpdb;

                        // Check if the activation ID and type are provided and valid
                        if (!isset($_POST['activation_id']) || !is_numeric($_POST['activation_id']) || !isset($_POST['activation_type'])) {
                            wp_send_json_error([
                                'result' => 'error',
                                'message' => __('Invalid activation data.', 'slmplus'),
                                'error_code' => 400
                            ]);
                        }

                        $activation_id = intval($_POST['activation_id']);
                        $activation_type = sanitize_text_field($_POST['activation_type']);

                        // Delete the activation from the correct table
                        if ($activation_type === 'domain') {
                            $result = $wpdb->delete(SLM_TBL_LIC_DOMAIN, ['id' => $activation_id]);
                        } elseif ($activation_type === 'device') {
                            $result = $wpdb->delete(SLM_TBL_LIC_DEVICES, ['id' => $activation_id]);
                        } else {
                            wp_send_json_error([
                                'result' => 'error',
                                'message' => __('Invalid activation type.', 'slmplus'),
                                'error_code' => 400
                            ]);
                        }

                        // Handle result
                        if ($result !== false) {
                            wp_send_json_success([
                                'result' => 'success',
                                'message' => __('The license key has been deactivated for this domain.', 'slmplus'),
                                'error_code' => 360
                            ]);
                        } else {
                            wp_send_json_error([
                                'result' => 'error',
                                'message' => __('Error deleting activation.', 'slmplus'),
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

                        <h2 class="hndle"><?php esc_html_e('License Information', 'slmplus'); ?></h2>
                        <div class="postbox">
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="license_key"><?php _e('License Key', 'slmplus'); ?></label>
                                        </th>
                                        <td>
                                            <input name="license_key" type="text" id="license_key" value="<?php echo esc_attr($data['license_key']); ?>" class="regular-text" 
                                                <?php if (isset($_GET['edit']) || !empty($id)): ?>readonly<?php endif; ?> />
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row"><label for="max_allowed_domains"><?php _e('Max Allowed Domains', 'slmplus'); ?></label></th>
                                        <td><input name="max_allowed_domains" type="number" id="max_allowed_domains" value="<?php echo esc_attr($data['max_allowed_domains']); ?>" class="regular-text" /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="max_allowed_devices"><?php _e('Max Allowed Devices', 'slmplus'); ?></label></th>
                                        <td><input name="max_allowed_devices" type="number" id="max_allowed_devices" value="<?php echo esc_attr($data['max_allowed_devices']); ?>" class="regular-text" /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="lic_status"><?php _e('License Status', 'slmplus'); ?></label></th>
                                        <td>
                                            <?php
                                            // Fetch all status records
                                            $statuses = $wpdb->get_results("SELECT status_key, status_label FROM $lic_status_table ", ARRAY_A);
                                            
                                            // Create the <select> element
                                            echo '<select name="lic_status" id="lic_status" class="regular-text">';
                                            foreach ($statuses as $status) {
                                                // Set the selected attribute if the current status matches
                                                $selected = selected($data['lic_status'], $status['status_key'], false);
                                                echo '<option value="' . esc_attr($status['status_key']) . '"' . $selected . '>' . esc_html($status['status_label']) . '</option>';
                                            }
                                            echo '</select>';
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="date_created"><?php _e('Date Created', 'slmplus'); ?> <span style="color: red;">*</span></label></th>
                                        <td><input name="date_created" type="date" id="date_created" value="<?php echo esc_attr($data['date_created']); ?>"  <?php if (isset($_GET['edit']) || !empty($id)): ?>readonly<?php endif; ?>  class="regular-text datepicker" required/>
                                            <p class="description" id="new-admin-email-description"><?php printf(__('Display Format: %s (input: YYYY-MM-DD)', 'slmplus'), $slm_wp_date_format); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="lic_type"><?php _e('License Type', 'slmplus'); ?> <span style="color: red;">*</span></label></th>
                                        <td>
                                            <select name="lic_type" id="lic_type" class="regular-text" required>
                                                <option value="subscription" <?php selected($data['lic_type'], 'subscription'); ?>><?php _e('Subscription', 'slmplus'); ?></option>
                                                <option value="lifetime" <?php selected($data['lic_type'], 'lifetime'); ?>><?php _e('Lifetime', 'slmplus'); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="date_expiry"><?php _e('Date Expiry', 'slmplus'); ?> <span style="color: red;">*</span></label></th>
                                        <td><input name="date_expiry" type="date" id="date_expiry" value="<?php echo esc_attr($data['date_expiry']); ?>" class="regular-text datepicker" required />
                                            <p class="description" id="new-admin-email-description"><?php printf(__('Display Format: %s (input: YYYY-MM-DD)', 'slmplus'), $slm_wp_date_format); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="subscr_id"><?php _e('Subscriber ID', 'slmplus'); ?></label></th>
                                        <td><input name="subscr_id" type="text" id="subscr_id" value="<?php echo esc_attr($data['subscr_id']); ?>" class="regular-text" /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="date_renewed"><?php _e('Date Renewed', 'slmplus'); ?></label></th>
                                        <td><input name="date_renewed" type="date" id="date_renewed" value="<?php echo esc_attr($data['date_renewed']); ?>" class="regular-text datepicker" />
                                            <p class="description" id="new-admin-email-description"><?php printf(__('Display Format: %s (input: YYYY-MM-DD)', 'slmplus'), $slm_wp_date_format); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="date_activated"><?php _e('Date Activated', 'slmplus'); ?></label></th>
                                        <td><input name="date_activated" type="date" id="date_activated" value="<?php echo esc_attr($data['date_activated']); ?>" class="regular-text datepicker" />
                                            <p class="description" id="new-admin-email-description"><?php printf(__('Display Format: %s (input: YYYY-MM-DD)', 'slmplus'), $slm_wp_date_format); ?></p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <h2 class="hndle"><?php esc_html_e('Subscriber Information', 'slmplus'); ?></h2>
                        <div class="postbox">
                            <div class="inside">                                        
                                <table class="form-table">

                                    <tr>
                                        <th scope="row"><label for="first_name"><?php _e('First Name', 'slmplus'); ?> <span style="color: red;">*</span></label></th>
                                        <td><input name="first_name" type="text" id="first_name" value="<?php echo esc_attr($data['first_name']); ?>" class="regular-text" required /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="last_name"><?php _e('Last Name', 'slmplus'); ?> <span style="color: red;">*</span></label></th>
                                        <td><input name="last_name" type="text" id="last_name" value="<?php echo esc_attr($data['last_name']); ?>" class="regular-text" required /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="email"><?php _e('Email', 'slmplus'); ?> <span style="color: red;">*</span></label></th>
                                        <td><input name="email" type="email" id="email" value="<?php echo esc_attr($data['email']); ?>" class="regular-text" required /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="company_name"><?php _e('Company Name', 'slmplus'); ?></label></th>
                                        <td><input name="company_name" type="text" id="company_name" value="<?php echo esc_attr($data['company_name']); ?>" class="regular-text" /></td>
                                    </tr>

                                </table>
                            </div>
                        </div>

                        <h2 class="hndle"><?php esc_html_e('Transaction Information', 'slmplus'); ?></h2>
                        <div class="postbox">
                            <div class="inside">
                                <table class="form-table">

                                    <tr>
                                        <th scope="row"><label for="item_reference"><?php _e('Item Reference', 'slmplus'); ?></label></th>
                                        <td><input name="item_reference" type="text" id="item_reference" value="<?php echo esc_attr($data['item_reference']); ?>" class="regular-text" /></td>
                                    </tr>

                                    <tr>
                                        <th scope="row"><label for="txn_id"><?php _e('Transaction ID', 'slmplus'); ?></label></th>
                                        <td><input name="txn_id" type="text" id="txn_id" value="<?php echo esc_attr($data['txn_id']); ?>" class="regular-text" /></td>
                                    </tr>

                                    <tr>
                                        <th scope="row"><label for="purchase_id_"><?php _e('Purchase ID', 'slmplus'); ?></label></th>
                                        <td><input name="purchase_id_" type="text" id="purchase_id_" value="<?php echo esc_attr($data['purchase_id_']); ?>" class="regular-text" /></td>
                                    </tr>

                                    <tr>
                                        <th scope="row"><label for="product_ref"><?php _e('Product Reference', 'slmplus'); ?></label></th>
                                        <td><input name="product_ref" type="text" id="product_ref" value="<?php echo esc_attr($data['product_ref']); ?>" class="regular-text" /></td>
                                    </tr>

                                </table>
                            </div>
                        </div>

                        <h2 class="hndle"><?php esc_html_e('Other', 'slmplus'); ?></h2>
                        <div class="postbox">
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th scope="row"><label for="until"><?php _e('Until', 'slmplus'); ?></label></th>
                                        <td><input name="until" type="text" id="until" value="<?php echo esc_attr($data['until']); ?>" class="regular-text" /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="current_ver"><?php _e('Current Version', 'slmplus'); ?></label></th>
                                        <td><input name="current_ver" type="text" id="current_ver" value="<?php echo esc_attr($data['current_ver']); ?>" class="regular-text" /></td>
                                    </tr>

                                    <tr>
                                        <th scope="row"><label for="slm_billing_length"><?php _e('Billing Length', 'slmplus'); ?></label></th>
                                        <td><input name="slm_billing_length" type="text" id="slm_billing_length" value="<?php echo esc_attr($data['slm_billing_length']); ?>" class="regular-text" /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="slm_billing_interval"><?php _e('Expiration Term', 'slmplus'); ?></label>
                                        </th>
                                        <td>
                                            <select name="slm_billing_interval" id="slm_billing_interval" class="regular-text">
                                                <option value="days" <?php selected($data['slm_billing_interval'], 'days'); ?>><?php _e('Day(s)', 'slmplus'); ?></option>
                                                <option value="months" <?php selected($data['slm_billing_interval'], 'months'); ?>><?php _e('Month(s)', 'slmplus'); ?></option>
                                                <option value="years" <?php selected($data['slm_billing_interval'], 'years'); ?>><?php _e('Year(s)', 'slmplus'); ?></option>
                                            </select>
                                            <p class="description" id="new-admin-email-description"><?php _e('Frequency period: in days, months, years','softwarelicensemanager'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="reminder_sent"><?php _e('Reminder Sent', 'slmplus'); ?></label></th>
                                        <td><input name="reminder_sent" type="text" id="reminder_sent" value="<?php echo esc_attr($data['reminder_sent']); ?>" class="regular-text" /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="reminder_sent_date"><?php _e('Reminder Sent Date', 'slmplus'); ?></label></th>
                                        <td><input name="reminder_sent_date" type="date" id="reminder_sent_date" value="<?php echo esc_attr($data['reminder_sent_date']); ?>" class="regular-text datepicker" />
                                            <p class="description" id="new-admin-email-description"><?php printf(__('Display Format: %s (input: YYYY-MM-DD)', 'slmplus'), $slm_wp_date_format); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="manual_reset_count"><?php _e('Manual Reset Count', 'slmplus'); ?></label></th>
                                        <td><input name="manual_reset_count" type="number" id="manual_reset_count" value="<?php echo esc_attr($data['manual_reset_count']); ?>" class="regular-text" /></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <?php
                        if (isset($_GET['edit_record']) && !empty($_GET['edit_record'])) : ?>
                            <?php submit_button(__('Update License', 'slmplus'), 'primary', 'slm_save_license'); ?>
                        <?php else: ?>
                            <?php submit_button(__('Create License', 'slmplus'), 'primary', 'slm_save_license'); ?>
                        <?php endif; ?>

                        <script type="text/javascript">
                            document.addEventListener('DOMContentLoaded', function() {
                                const form = document.querySelector('form'); // Adjust this selector to target your specific form

                                form.addEventListener('submit', function(event) {
                                    // Scroll to the top immediately
                                    window.scrollTo({ top: 0, behavior: 'smooth' });
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
