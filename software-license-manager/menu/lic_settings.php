<?php

function wp_lic_mgr_settings_menu() {

    echo '<div class="wrap">';
    echo '<h2>WP License Manager Settings v' . WP_LICENSE_MANAGER_VERSION . '</h2>';
    echo '<div id="poststuff"><div id="post-body">';

    wp_lic_mgr_general_settings();

    echo '</div></div>';
    echo '</div>';
}

function wp_lic_mgr_general_settings() {

    if (isset($_POST['slm_save_settings'])) {

        if (!is_numeric($_POST["default_max_domains"])) {//Set it to one by default if incorrect value is entered
            $_POST["default_max_domains"] = '1';
        }

        $options = array(
            'lic_creation_secret' => trim($_POST["lic_creation_secret"]),
            'lic_prefix' => trim($_POST["lic_prefix"]),
            'default_max_domains' => trim($_POST["default_max_domains"]),
            'lic_verification_secret' => trim($_POST["lic_verification_secret"]),
            'enable_debug' => isset($_POST['enable_debug']) ? '1':'',
        );
        update_option('slm_plugin_options', $options);
        
        echo '<div id="message" class="updated fade"><p>';        
        echo 'Options Updated!';
        echo '</p></div>';
    }

    $options = get_option('slm_plugin_options');

    $secret_key = $options['lic_creation_secret'];
    if (empty($secret_key)) {
        $secret_key = uniqid('', true);
    }
    $secret_verification_key = $options['lic_verification_secret'];
    if (empty($secret_verification_key)) {
        $secret_verification_key = uniqid('', true);
    }
    ?>
    <p>For information, updates and detailed documentation, please visit the <a href="https://www.tipsandtricks-hq.com" target="_blank">License Manager Documentation</a> page.</p>

    <div class="postbox">
        <h3><label for="title">Quick Usage Guide</label></h3>
        <div class="inside">

            <p>1. First register a key at purchase time.</p>
            <p>2. Add the code so at activation time it asks for the key.</p>
            <p>3. Integrate the real time online key verification part.</p>
        </div></div>

    <form method="post" action="">

        <div class="postbox">
            <h3><label for="title">General License Manager Settings</label></h3>
            <div class="inside">
                <table class="form-table">

                    <tr valign="top">
                        <th scope="row">Secret Key for License Creation</th>
                        <td><input type="text" name="lic_creation_secret" value="<?php echo $secret_key; ?>" size="40" />
                            <br />This secret key will be used to authenticate any license creation request. You can change it with something random.</td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Secret Key for License Verification Requests</th>
                        <td><input type="text" name="lic_verification_secret" value="<?php echo $secret_verification_key; ?>" size="40" />
                            <br />This secret key will be used to authenticate any license verification request from customer's site. Important! Do not change this value once your customers start to use your product(s)!</td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">License Key Prefix</th>
                        <td><input type="text" name="lic_prefix" value="<?php echo $options['lic_prefix']; ?>" size="40" />
                            <br />You can optionaly specify a prefix for the license keys. This prefix will be added to the uniquely generated license keys.</td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Maximum Allowed Domains</th>
                        <td><input type="text" name="default_max_domains" value="<?php echo $options['default_max_domains']; ?>" size="6" />
                            <br />Maximum number of domains which each license is valid for (default value).</td>
                    </tr>

                </table>
            </div></div>

        <div class="postbox">
            <h3><label for="title">Debugging and Testing Settings</label></h3>
            <div class="inside">
                <table class="form-table">

                    <tr valign="top">
                        <th scope="row">Enable Debug Logging</th>
                        <td><input name="enable_debug" type="checkbox"<?php if ($options['enable_debug'] != '') echo ' checked="checked"'; ?> value="1"/>
                            &nbsp;<a href="<?php echo WP_LICENSE_MANAGER_URL. '/logs/log.txt'; ?>" target="_blank">View Log File</a>
                            <p class="description">If checked, debug output will be written to log files (keep it disabled unless you are troubleshooting).</p>                            
                        </td>
                    </tr>

                </table>
            </div></div>

        <div class="submit">
            <input type="submit" class="button-primary" name="slm_save_settings" value=" <?php _e('Update Options', 'slm'); ?>" />
        </div>
    </form>
    <?php
}
