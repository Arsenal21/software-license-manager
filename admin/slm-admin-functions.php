<?php

if (!defined('WPINC')) {
    die;
}

function slm_admin_tools_menu()
{
    if (isset($_POST['slm_backup_db'])) {
        // Verify the nonce
        if (isset($_POST['slm_backup_nonce_field']) && wp_verify_nonce($_POST['slm_backup_nonce_field'], 'slm_backup_nonce_action')) {
            // Nonce is valid, proceed with backup
            SLM_Utility::slm_save_backup_to_uploads();
        } else {
            // Nonce is invalid or missing
            die('Security check failed.');  // You can display an error message or handle it as needed
        }
    }

    echo '<div class="wrap">';
    echo '<h2 class="imgh2">' . esc_html__('SLM Plus - Tools', 'slm-plus') . '</h2>';
    echo '<div id="poststuff"><div id="post-body">';

    if (isset($_POST['send_deactivation_request'])) {
        // Verify the nonce
        if (isset($_POST['slm_deactivation_nonce_field']) && wp_verify_nonce($_POST['slm_deactivation_nonce_field'], 'slm_deactivation_nonce_action')) {
            // Nonce is valid, proceed with the deactivation request

            $postURL = esc_url_raw($_POST['slm_deactivation_req_url']);
            $secretKeyForVerification = slm_get_option('lic_verification_secret');
            $data = array('secret_key' => $secretKeyForVerification);

            // Make the POST request using wp_remote_post
            $response = wp_remote_post($postURL, array(
                'method'    => 'POST',
                'body'      => $data,
                'timeout'   => 15, // Optional timeout value
                'headers'   => array('Content-Type' => 'application/x-www-form-urlencoded')
            ));

            // Check for errors in the response
            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                $msg = esc_html__('Request failed: ', 'slm-plus') . esc_html($error_message);
            } else {
                $body = wp_remote_retrieve_body($response);

                // Check for success message in response
                if ($body == "Success") {
                    $msg = esc_html__('Success message returned from the remote host.', 'slm-plus');
                } else {
                    $msg = esc_html__('Unexpected response: ', 'slm-plus') . esc_html($body);
                }
            }

            // Display message
            echo '<div id="message" class="updated fade"><p>';
            echo esc_html__('Request sent to the specified URL!', 'slm-plus');
            echo '<br />' . esc_html($msg);
            echo '</p></div>';
        } else {
            // Nonce is invalid or missing
            echo '<div id="message" class="error fade"><p>' . esc_html__('Security check failed. Invalid nonce.', 'slm-plus') . '</p></div>';
        }
    }


    if (isset($_POST['slm_clear_log'])) {
        // Verify the nonce
        if (isset($_POST['slm_clear_log_nonce_field']) && wp_verify_nonce($_POST['slm_clear_log_nonce_field'], 'slm_clear_log_nonce_action')) {
            // Nonce is valid, proceed with clearing the log

            global $wpdb, $slm_debug_logger;

            // Define the table name using the constant (already assumed to be done securely)
            $table = SLM_TBL_LIC_LOG;

            // Sanitize the table name if it's dynamically passed (for security)
            $table = sanitize_key($table); // sanitize_key ensures a safe table name, although here it's already defined

            // Sanitize and validate other variables if used dynamically

            // Direct query execution for truncating the table
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'")) {  // Check if table exists
                $query = "TRUNCATE TABLE `$table`";  // Backticks are used to prevent issues with reserved SQL keywords
                $wpdb->query($query);  // Direct query execution
            } else {
                // Handle the case where the table doesn't exist
                error_log('Table not found: ' . $table);
            }

            // Reset log files
            $slm_debug_logger->reset_log_file("log.txt");
            $slm_debug_logger->reset_log_file("log-cron-job.txt");

            echo '<div id="message" class="updated fade"><p>' . esc_html__('Log was cleared successfully!', 'slm-plus') . '</p></div>';
        } else {
            // Nonce is invalid or missing
            echo '<div id="message" class="error fade"><p>' . esc_html__('Security check failed. Invalid nonce.', 'slm-plus') . '</p></div>';
        }
    }

?>
    <br />
    <div class="postbox">
        <h3 class="hndle"><label for="title"><?php esc_html_e('Send Deactivation Message for a License', 'slm-plus'); ?></label></h3>
        <div class="inside">
            <form method="post" action="">
                <?php wp_nonce_field('slm_deactivation_nonce_action', 'slm_deactivation_nonce_field'); ?>
                <input name="slm_deactivation_req_url" type="text" size="100" value="<?php echo esc_attr($_POST['slm_deactivation_req_url'] ?? ''); ?>" />
                <div class="submit">
                    <input type="submit" name="send_deactivation_request" value="<?php esc_html_e('Send Request', 'slm-plus'); ?>" class="button" />
                </div>
            </form>
        </div>
    </div>


    <div class="postbox">
        <h3 class="hndle"><label for="title"><?php esc_html_e('Reset Secret Keys', 'slm-plus'); ?></label></h3>
        <div class="inside">
            <p style="color: red;">
                <?php esc_html_e('Warning: Resetting these keys cannot be undone. Any API requests that depend on the current keys will break after they are reset.', 'slm-plus'); ?>
            </p>
            <form method="post" action="" style="display: flex; gap: 10px;">
                <?php wp_nonce_field('slm_reset_secret_keys_nonce_action', 'slm_reset_secret_keys_nonce_field'); ?>

                <!-- Reset Button for Creation Secret Key -->
                <input type="submit" name="reset_creation_secret" value="<?php esc_html_e('Reset Creation Secret Key', 'slm-plus'); ?>" class="button button-secondary" onclick="return confirm('<?php esc_html_e('Are you sure you want to reset the Creation Secret Key?', 'slm-plus'); ?>');" />

                <!-- Reset Button for Verification Secret Key -->
                <input type="submit" name="reset_verification_secret" value="<?php esc_html_e('Reset Verification Secret Key', 'slm-plus'); ?>" class="button button-secondary" onclick="return confirm('<?php esc_html_e('Are you sure you want to reset the Verification Secret Key?', 'slm-plus'); ?>');" />
            </form>

            <?php
            // Display the new keys after reset
            if (isset($_GET['new_creation_secret'])) {
                echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__('New Creation Secret Key:', 'slm-plus') . '</strong> ' . esc_html($_GET['new_creation_secret']) . '</p></div>';
            }
            if (isset($_GET['new_verification_secret'])) {
                echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__('New Verification Secret Key:', 'slm-plus') . '</strong> ' . esc_html($_GET['new_verification_secret']) . '</p></div>';
            }
            ?>
        </div>
    </div>

    <?php
    // Handle the reset action and display new keys.
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['reset_creation_secret']) && check_admin_referer('slm_reset_secret_keys_nonce_action', 'slm_reset_secret_keys_nonce_field')) {
            $new_creation_secret = SLM_Utility::create_secret_keys(); // Generate new creation secret
            update_option('slm_plugin_options', array_merge(get_option('slm_plugin_options', []), ['lic_creation_secret' => $new_creation_secret]));
            wp_redirect(add_query_arg(['slm_notice' => 'creation_key_reset', 'new_creation_secret' => $new_creation_secret], $_SERVER['REQUEST_URI']));
            exit;
        }

        if (isset($_POST['reset_verification_secret']) && check_admin_referer('slm_reset_secret_keys_nonce_action', 'slm_reset_secret_keys_nonce_field')) {
            $new_verification_secret = SLM_Utility::create_secret_keys(); // Generate new verification secret
            update_option('slm_plugin_options', array_merge(get_option('slm_plugin_options', []), ['lic_verification_secret' => $new_verification_secret]));
            wp_redirect(add_query_arg(['slm_notice' => 'verification_key_reset', 'new_verification_secret' => $new_verification_secret], $_SERVER['REQUEST_URI']));
            exit;
        }
    }
    ?>


    <div class="postbox">
        <h3 class="hndle"><label for="title"><?php esc_html_e('Clean Activity Log', 'slm-plus'); ?></label></h3>
        <div class="inside">
            <p><?php esc_html_e('This will clear/reset license keys activities', 'slm-plus'); ?></p>
            <form method="post" action="">
                <div class="submit">
                    <?php wp_nonce_field('slm_clear_log_nonce_action', 'slm_clear_log_nonce_field'); ?>
                    <input type="submit" name="slm_clear_log" value="<?php esc_html_e('Clear Log', 'slm-plus'); ?>" class="button" />
                </div>
            </form>
        </div>
    </div>
    

    <div class="postbox">
        <h3 class="hndle"><label for="title"><?php esc_html_e('Backup Database', 'slm-plus'); ?></label></h3>
        <div class="inside">
            <p><?php esc_html_e('This will create a backup of the database tables related to this plugin and save it to the uploads directory.', 'slm-plus'); ?></p>
            <form method="post" action="">
                <?php wp_nonce_field('slm_backup_nonce_action', 'slm_backup_nonce_field'); ?>
                <div class="submit">
                    <input type="submit" name="slm_backup_db" value="<?php esc_html_e('Create Backup', 'slm-plus'); ?>" class="button" />
                </div>
            </form>

            <?php
            // Display latest backup link if available
            $backup_info = slm_get_option('slm_last_backup_info');
            if (!empty($backup_info)) {
                $backup_url = esc_url($backup_info['url']);
                $backup_date = esc_html($backup_info['date']);
                echo '<p>' . esc_html__('Last backup created on: ', 'slm-plus') . esc_html($backup_date) . ' - <a href="' . esc_url($backup_url) . '">' . esc_html__('Download Backup', 'slm-plus') . '</a></p>';
            }
            ?>
        </div>
    </div>

    <div class="postbox">
        <h3 class="hndle"><label for="title"><?php esc_html_e('Generate License for WooCommerce Orders', 'slm-plus'); ?></label></h3>
        <div class="inside">
            <p class="notice notice-error" style="padding: 10px; margin-top: 5px;">
                <?php esc_html_e('This tool generates bulk licenses for WooCommerce orders placed before the plugin was activated or for orders that lack existing licenses.', 'slm-plus'); ?>
                <strong><?php esc_html_e('Warning:', 'slm-plus'); ?></strong>
                <?php esc_html_e('This action cannot be undone. Please back up your database before proceeding.', 'slm-plus'); ?>
            </p>

            <form id="generate_licenses_form" method="post">
                <?php wp_nonce_field('slm_generate_licenses_nonce', 'slm_generate_licenses_nonce_field'); ?>
                <div class="slm_tools submit">
                    <?php $slm_wc_lic_generator = SLM_API_Utility::get_slm_option('slm_wc_lic_generator'); ?>
                    <table>

                        <tr valign="top">
                            <th scope="row"><label for="slm_product_id"><?php esc_html_e('Product ID', 'slm-plus'); ?></label></th>
                            <td>
                                <input type="text" id="slm_product_id" name="slm_product_id" class="regular-text" placeholder="<?php esc_html_e('Enter Product ID', 'slm-plus'); ?>" required />
                                <p class="description"><?php esc_html_e('Specify the default product ID for license generation.', 'slm-plus'); ?></p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row"><label for="subscription_type"><?php esc_html_e('Subscription Type', 'slm-plus'); ?></label></th>
                            <td>
                                <select id="subscription_type" name="subscription_type" class="regular-select">
                                    <option value="subscription"><?php esc_html_e('Subscription', 'slm-plus'); ?></option>
                                    <option value="lifetime"><?php esc_html_e('Lifetime', 'slm-plus'); ?></option>
                                </select>
                                <p class="description"><?php esc_html_e('Select the type of license for the order.', 'slm-plus'); ?></p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <td>
                                <!-- Generate Licenses Button -->
                                <input type="button" id="generate_licenses" value="<?php esc_html_e('Generate Licenses', 'slm-plus'); ?>" class="button" <?php echo $slm_wc_lic_generator == '1' ? '' : 'disabled'; ?> />
                                <?php if ($slm_wc_lic_generator != '1'): ?>
                                    <!-- Message if option is not enabled -->
                                    <p class="notice notice-info" style="padding: 10px; margin-top: 5px;">
                                        <?php esc_html_e('Please enable the WooCommerce License Generator option to activate the Generate Licenses tool.', 'slm-plus'); ?>
                                    </p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </form>

            <div id="license-generation-result">
                <h4><?php esc_html_e('License Generation Results:', 'slm-plus'); ?></h4>
                <ul id="license-result-list"></ul>
            </div>
        </div>
    </div>


    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#generate_licenses').click(function() {
                $('#license-result-list').html(''); // Clear previous results

                // Collect values from the form fields
                var productID = $('#slm_product_id').val();
                var subscriptionType = $('#subscription_type').val();

                // Validation: Check if fields are empty
                if (!productID) {
                    $('#license-result-list').html('<li><strong>Error:</strong> <?php esc_html_e("Product ID cannot be empty.", "slm-plus"); ?></li>');
                    alert('<?php esc_html_e("Product ID is required.", "slm-plus"); ?>');
                    return; // Stop submission if Product ID is empty
                }
                if (!subscriptionType) {
                    $('#license-result-list').html('<li><strong>Error:</strong> <?php esc_html_e("Subscription Type cannot be empty.", "slm-plus"); ?></li>');
                    alert('<?php esc_html_e("Subscription Type is required.", "slm-plus"); ?>');
                    return; // Stop submission if Subscription Type is empty
                }

                // Prepare data for AJAX request after validation
                var data = {
                    action: 'slm_generate_licenses',
                    security: $('#generate_licenses_form input[name="slm_generate_licenses_nonce_field"]').val(),
                    slm_product_id: productID,
                    subscription_type: subscriptionType
                };

                // Log the full URL and data being sent
                var fullUrl = ajaxurl + '?' + $.param(data);
                //console.log('Full AJAX URL: ' + fullUrl);
                //console.log('Full data being sent: ', JSON.stringify(data, null, 2));

                // Perform AJAX request
                $.post(ajaxurl, data, function(response) {
                    //console.log('AJAX response:', response);
                    if (response.success) {
                        $('#license-result-list').html(response.data.html);
                        alert('<?php esc_html_e('Licenses generated successfully!', 'slm-plus'); ?>');
                    } else {
                        $('#license-result-list').html(response.data.html);
                        alert('<?php esc_html_e('Some licenses failed to generate. Check the response for details.', 'slm-plus'); ?>');
                    }
                }).fail(function(xhr, status, error) {
                    console.error('AJAX error:', status, error);
                    $('#license-result-list').html('<li><strong>Error:</strong> <?php esc_html_e("There was an error processing the request. Please try again.", "slm-plus"); ?></li>');
                    alert('<?php esc_html_e('There was an error processing the request.', 'slm-plus'); ?>');
                });
            });
        });
    </script>

<?php
    echo '</div></div></div>';
}

/**
 * Generates or retrieves a unique hash for the backup directory.
 */
function slm_get_unique_hash()
{
    $hash = slm_get_option('slm_backup_dir_hash');
    if (!$hash) {
        $hash = wp_generate_password(8, false, false); // Generate random 8-character hash
        slm_update_option('slm_backup_dir_hash', $hash);
    }
    return $hash;
}


/**
 * Retrieves an option from the slm_plugin_options.
 */
function slm_get_option($key)
{
    $options = get_option('slm_plugin_options', []);
    return $options[$key] ?? null;
}

/**
 * Updates or adds an option to the slm_plugin_options.
 */
function slm_update_option($key, $value)
{
    $options = get_option('slm_plugin_options', []);
    $options[$key] = $value;
    update_option('slm_plugin_options', $options);
}
