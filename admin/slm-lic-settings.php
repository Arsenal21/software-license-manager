<?php

if (!defined('WPINC')) {
    die;
}


function slm_settings_menu()
{

    echo '<div class="wrap">';
    echo '<h1>Settings - Software License Manager</h1>
    <hr class="wp-header-end">';
    echo '<div id="poststuff"><div id="post-body">';

    slm_general_settings();

    echo '</div></div>';
    echo '</div>';
}

function slm_general_settings()
{

    if (isset($_REQUEST['slm_reset_log'])) {
        $slm_logger = new SLM_Debug_Logger();
        global $slm_debug_logger;
        $slm_debug_logger->reset_log_file("log.txt");
        $slm_debug_logger->reset_log_file("log-cron-job.txt");
        echo '<div id="message" class="updated fade"><p>Debug log files have been reset!</p></div>';
    }

    if (isset($_POST['slm_save_settings'])) {

        if (!is_numeric($_POST["default_max_domains"])) {
            //Set it to one by default if incorrect value is entered
            $_POST["default_max_domains"] = '2';
        }
        if (!is_numeric($_POST["default_max_devices"])) {
            //Set it to one by default if incorrect value is entered
            $_POST["default_max_devices"] = '2';
        }

        $options = array(
            'lic_creation_secret'       => trim($_POST["lic_creation_secret"]),
            'lic_prefix'                => trim($_POST["lic_prefix"]),
            'default_max_domains'       => trim($_POST["default_max_domains"]),
            'default_max_devices'       => trim($_POST["default_max_devices"]),
            'lic_verification_secret'   => trim($_POST["lic_verification_secret"]),
            'enable_auto_key_expiry'    => isset($_POST['enable_auto_key_expiry']) ? '1' : '',
            'enable_debug'              => isset($_POST['enable_debug']) ? '1' : '',
            'slm_woo'                   => isset($_POST['slm_woo']) ? '1' : '',
            'slm_woo_downloads'         => isset($_POST['slm_woo_downloads']) ? '1' : '',
            'slm_wpestores'             => isset($_POST['slm_wpestores']) ? '1' : '',
            'slm_dl_manager'            => isset($_POST['slm_dl_manager']) ? '1' : '',
        );
        update_option('slm_plugin_options', $options);

        echo '<div id="message" class="updated fade"><p>';
        echo 'Options Updated!';
        echo '</p></div>';
    }

    $options = get_option('slm_plugin_options');

    $secret_key = $options['lic_creation_secret'];
    if (empty($secret_key)) {
        //$secret_key = md5(uniqid('', true));
        $secret_key = SLM_Utility::create_secret_keys();
    }

    $secret_verification_key = $options['lic_verification_secret'];
    if (empty($secret_verification_key)) {
        //$secret_verification_key = md5(uniqid('', true));
        $secret_verification_key = SLM_Utility::create_secret_keys();
    }

    ?>


    <form method="post" action="">

        <div class="postbox">
            <h3 class="hndle"><label for="title">General settings</label></h3>
            <div class="inside">
                <table class="form-table">

                    <tr valign="top">
                        <th scope="row">Secret Key for License Creation</th>
                        <td><input type="text" name="lic_creation_secret" value="<?php echo $secret_key; ?>" size="40" />
                            <p class="description">This secret key will be used to authenticate any license creation request. You can change it with something random.</p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Secret Key for License Verification Requests</th>
                        <td><input type="text" name="lic_verification_secret" value="<?php echo $secret_verification_key; ?>" size="40" />
                            <p class="description">This secret key will be used to authenticate any license verification request from customer's site. Important! Do not change this value once your customers start to use your product(s)!</p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">License Key Prefix</th>
                        <td><input type="text" name="lic_prefix" value="<?php echo $options['lic_prefix']; ?>" size="40" />
                            <p class="description">You can optionaly specify a prefix for the license keys. This prefix will be added to the uniquely generated license keys.</p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Maximum Allowed Domains</th>
                        <td><input type="text" name="default_max_domains" value="<?php echo $options['default_max_domains']; ?>" size="6" />
                            <p class="description">Maximum number of domains/installs which each license is valid for (default value).</p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Maximum Allowed Devices</th>
                        <td><input type="text" name="default_max_devices" value="<?php echo $options['default_max_devices']; ?>" size="6" />
                            <p class="description">Maximum number of devices which each license is valid for (default value).</p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Auto Expire License Keys</th>
                        <td><input name="enable_auto_key_expiry" type="checkbox" <?php if (isset($options['enable_auto_key_expiry']) && $options['enable_auto_key_expiry'] != '') echo ' checked="checked"'; ?> value="1" />Enable auto expiration
                            <p class="description">When enabled, it will automatically set the status of a license key to "Expired" when the expiry date value of the key is reached. It doesn't remotely deactivate a key. It simply changes the status of the key in your database to expired.</p>
                        </td>
                    </tr>


                </table>
            </div>


        </div>

        <div class="postbox">
            <h3 class="hndle"><label for="title">Integrations</label></h3>
            <div class="inside">
                <table class="form-table">

                    <tr valign="top">
                        <th scope="row">Woocommerce Support</th>
                        <td>
                            <input name="slm_woo" type="checkbox" <?php if ($options['slm_woo'] != '') echo ' checked="checked"'; ?> value="1" />
                            A fully customizable, open source eCommerce platform built for WordPress.</td>

                        <td>
                            <input name="slm_woo_downloads" type="checkbox" <?php if ($options['slm_woo_downloads'] != '') echo ' checked="checked"'; ?> value="1" />
                            Disable woocommerce download page. Proccess downloads though license order info page.</td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Download Manager Support</th>
                        <td>
                            <input name="slm_dl_manager" type="checkbox" <?php if ($options['slm_dl_manager'] != '') echo ' checked="checked"'; ?> value="1" />
                            Download Manager Plugin – Adds a simple download manager to your WordPress blog.
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">WP eStores Support</th>
                        <td>
                            <input name="slm_wpestores" type="checkbox" <?php if ($options['slm_wpestores'] != '') echo ' checked="checked"'; ?> value="1" />
                            WordPress eStore Plugin – Complete Solution to Sell Digital Products from Your WordPress Blog Securely

                        </td>
                    </tr>

                </table>
            </div>
        </div>

        <div class="postbox">
            <h3 class="hndle"><label for="title">Debugging settings</label></h3>
            <div class="inside">
                <table class="form-table">

                    <tr valign="top">
                        <th scope="row">Enable Debug Logging</th>
                        <td><input name="enable_debug" type="checkbox" <?php if ($options['enable_debug'] != '') echo ' checked="checked"'; ?> value="1" />
                            <p class="description">If checked, debug output will be written to log files (keep it disabled unless you are troubleshooting).</p>
                            - View debug log file by clicking <a href="<?php echo SLM_URL . '/public/logs/log.txt'; ?>" target="_blank">here</a>..
                            - Reset debug log file by clicking <a href="admin.php?page=slm_settings&slm_reset_log=1" target="_blank">here</a>.
                        </td>
                    </tr>

                </table>
            </div>
        </div>

        <div class="submit">
            <input type="submit" class="button-primary" name="slm_save_settings" value=" <?php _e('Update Options', 'slm'); ?>" />
        </div>
    </form>
<?php
}
