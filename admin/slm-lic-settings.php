<?php

if (!defined('WPINC')) {
    die;
}

function slm_settings_menu()
{
    slm_general_settings();
}

function slm_general_settings()
{
?>
    <?php

    $options = get_option('slm_plugin_options');

    if (isset($_POST['slm_save_settings'])) {
        // Sanitize and validate numeric values for default max domains and devices
        $default_max_domains = max((int) $_POST['default_max_domains'], 2);
        $default_max_devices = max((int) $_POST['default_max_devices'], 2);
    
        // Validate and sanitize version number format (e.g., 1.0.1)
        $license_until_version = !empty($_POST['license_until_version']) && preg_match('/^\d+(\.\d+)*$/', $_POST['license_until_version']) 
            ? sanitize_text_field($_POST['license_until_version']) 
            : '2.0';
    
        $license_current_version = !empty($_POST['license_current_version']) && preg_match('/^\d+(\.\d+)*$/', $_POST['license_current_version']) 
            ? sanitize_text_field($_POST['license_current_version']) 
            : '1.0';
    
        // Sanitize billing length
        $slm_billing_length = sanitize_text_field($_POST['slm_billing_length']);
    
        // Validate billing interval to ensure it's either 'days', 'months', or 'years'
        $allowed_intervals = ['days', 'months', 'years'];
        $slm_billing_interval = in_array($_POST['slm_billing_interval'], $allowed_intervals) 
            ? $_POST['slm_billing_interval'] 
            : 'years'; // Default to 'years' if invalid
    
        // Prepare options array for updating
        $options = array(
            'lic_creation_secret'            => trim($_POST['lic_creation_secret']),
            'lic_prefix'                     => trim($_POST['lic_prefix']),
            'default_max_domains'            => $default_max_domains,
            'default_max_devices'            => $default_max_devices,
            'lic_verification_secret'        => trim($_POST['lic_verification_secret']),
            'enable_auto_key_expiration'     => isset($_POST['enable_auto_key_expiration']),
            'enable_debug'                   => isset($_POST['enable_debug']),
            'slm_woo'                        => isset($_POST['slm_woo']),
            'slm_wc_lic_generator'           => isset($_POST['slm_wc_lic_generator']),
            'slm_woo_downloads'              => isset($_POST['slm_woo_downloads']),
            'slm_woo_affect_downloads'       => isset($_POST['slm_woo_affect_downloads']),
            'slm_stats'                      => isset($_POST['slm_stats']),
            'slm_adminbar'                   => isset($_POST['slm_adminbar']),
            // 'slm_conflictmode'               => isset($_POST['slm_conflictmode']),
            // 'slm_front_conflictmode'         => isset($_POST['slm_front_conflictmode']),
            'slm_wpestores'                  => isset($_POST['slm_wpestores']),
            'slm_dl_manager'                 => isset($_POST['slm_dl_manager']),
            'slm_multiple_items'             => isset($_POST['slm_multiple_items']),
            'allow_user_activation_removal'  => isset($_POST['allow_user_activation_removal']),
            'expiration_reminder_text'       => sanitize_text_field($_POST['expiration_reminder_text']),
            'license_until_version'          => $license_until_version,
            'license_current_version'        => $license_current_version,
            'slm_billing_length'             => $slm_billing_length,
            'slm_billing_interval'           => $slm_billing_interval,
        );
    
        // Update the options in the database
        update_option('slm_plugin_options', $options);
        echo '<div id="message" class="updated fade"> <p>' . esc_html__('Options updated!', 'slm-plus') . '</p> </div>';
    }

    $secret_key = !empty($options['lic_creation_secret']) ? $options['lic_creation_secret'] : SLM_Utility::create_secret_keys();
    $secret_verification_key = !empty($options['lic_verification_secret']) ? $options['lic_verification_secret'] : SLM_Utility::create_secret_keys();
    $tab = isset($_REQUEST['tab']) ? $_REQUEST['tab'] : 'general_settings';

    ?>
    <div class="wrap">
        <div class="poststuff">
            <div id="post-body" class="metabox-holder columns-2">

            
            <h1><?php esc_html_e('SLM Plus - Settings', 'slm-plus'); ?> </h1>

            <div id="icon-options-general" class="icon32"></div>
            <div class="nav-tab-wrapper">
                <?php $base_url = admin_url('admin.php?page=slm_settings'); ?>
                <a href="<?php echo esc_url($base_url); ?>" class="nav-tab <?php echo esc_attr($tab === 'general_settings' ? 'nav-tab-active' : ''); ?>">
                    <?php esc_html_e('General', 'slm-plus'); ?>
                </a>

                <a href="<?php echo esc_url(add_query_arg('tab', 'integrations', $base_url)); ?>" class="nav-tab <?php echo esc_attr($tab === 'integrations' ? 'nav-tab-active' : ''); ?>">
                    <?php esc_html_e('Integrations', 'slm-plus'); ?>
                </a>

                <a href="<?php echo esc_url(add_query_arg('tab', 'debug', $base_url)); ?>" class="nav-tab <?php echo esc_attr($tab === 'debug' ? 'nav-tab-active' : ''); ?>">
                    <?php esc_html_e('Debugging', 'slm-plus'); ?>
                </a>

                <a href="<?php echo esc_url(add_query_arg('tab', 'emails', $base_url)); ?>" class="nav-tab <?php echo esc_attr($tab === 'emails' ? 'nav-tab-active' : ''); ?>">
                    <?php esc_html_e('Emails', 'slm-plus'); ?>
                </a>

            </div>

            <style> .hidepanel { display: none; } .showpanel { display: block !important } #wpbody-content { padding-bottom: 8px; ; } </style>

            <div class="postbox">
                <div class="insie" style=" padding: 16px; ">
                    <form method="post" action="" class="wrap">
                        <div class="general_settings hidepanel <?php echo ($tab == 'general_settings') ? 'showpanel' : '' ?>">
                            <table class="form-table">
                                <tr valign="top">
                                    <th scope="row"><?php esc_html_e('Secret Key for License Creation', 'slm-plus'); ?></th>
                                    <td><textarea name="lic_creation_secret" rows="2" cols="50" readonly><?php echo esc_textarea($secret_key); ?></textarea>
                                        <p class=" description"><?php esc_html_e('This secret key will be used to authenticate any license creation request. You can change it with something random.', 'slm-plus'); ?></p>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php esc_html_e('Secret Key for License Verification Requests', 'slm-plus'); ?></th>
                                    <td><textarea name="lic_verification_secret" rows="2" cols="50" readonly><?php echo esc_textarea($secret_verification_key); ?></textarea>                                    </textarea>
                                        <p class="description"><?php esc_html_e('This secret key will be used to authenticate any license verification request from customer\'s site. Important! Do not change this value once your customers start to use your product(s)!', 'slm-plus'); ?></p>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php esc_html_e('License Key Prefix', 'slm-plus'); ?></th>
                                    <td><input type="text" name="lic_prefix" value="<?php echo esc_attr($options['lic_prefix']); ?>" size="6" />
                                        <p class="description"><?php esc_html_e('You can optionaly specify a prefix for the license keys. This prefix will be added to the uniquely generated license keys.', 'slm-plus'); ?></p>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php esc_html_e('Maximum Allowed Devices', 'slm-plus'); ?></th>
                                    <td><input type="text" name="default_max_devices" value="<?php echo esc_attr($options['default_max_devices']); ?>" size="6" />
                                        <p class="description"><?php esc_html_e('Maximum number of devices which each license is valid for (default value).', 'slm-plus'); ?></p>
                                    </td>
                                </tr>

                                <tr valign="top">
                                    <th scope="row"><?php esc_html_e('Maximum Allowed Domains', 'slm-plus'); ?></th>
                                    <td><input type="text" name="default_max_domains" value="<?php echo esc_attr($options['default_max_domains']); ?>" size="6" />
                                        <p class="description"><?php esc_html_e('Maximum number of domains which each license is valid for (default value).', 'slm-plus'); ?></p>
                                    </td>
                                </tr>

                                <tr valign="top">
                                    <th scope="row"><?php esc_html_e('Support Until Ver.', 'slm-plus'); ?></th>
                                    <td><input type="text" name="license_until_version" value="<?php echo esc_attr($options['license_until_version']); ?>" size="6" />
                                        <p class="description"><?php esc_html_e('This is used to enable bulk license generation for WooCommerce orders placed before the plugin was active or for orders that do not already contain licenses (default setting).', 'slm-plus'); ?></p>
                                    </td>
                                </tr>

                                <tr valign="top">
                                    <th scope="row"><?php esc_html_e('Current Version', 'slm-plus'); ?></th>
                                    <td><input type="text" name="license_current_version" value="<?php echo esc_attr($options['license_current_version']); ?>" size="6" />
                                        <p class="description"><?php esc_html_e('This is used to enable bulk license generation for WooCommerce orders placed before the plugin was active or for orders that do not already contain licenses (default setting:).', 'slm-plus'); ?></p>
                                    </td>
                                </tr>

                                <?php
                                    $slm_billing_length = !empty($options['slm_billing_length']) ? $options['slm_billing_length'] : '1'; // Default to 1 if not set
                                    $slm_billing_interval = !empty($options['slm_billing_interval']) ? $options['slm_billing_interval'] : 'years'; // Default to 'years' if not set
                                ?>

                                <tr>
                                    <th scope="row"><label for="slm_billing_length"><?php esc_html_e('Billing Length', 'slm-plus'); ?></label></th>
                                    <td><input name="slm_billing_length" type="text" id="slm_billing_length" value="<?php echo esc_attr($slm_billing_length); ?>" class="regular-text" />
                                    <p class="description"><?php esc_html_e('This is used to enable bulk license generation for WooCommerce orders placed before the plugin was active or for orders that do not already contain licenses (default setting:).', 'slm-plus'); ?></p></td>
                                </tr>

                                <tr valign="top">
                                    <th scope="row"><label for="slm_billing_interval"><?php esc_html_e('Expiration Term', 'slm-plus'); ?></label></th>
                                    <td>
                                        <select name="slm_billing_interval" id="slm_billing_interval" class="regular-text">
                                            <option value="days" <?php selected($slm_billing_interval, 'days'); ?>><?php esc_html_e('Day(s)', 'slm-plus'); ?></option>
                                            <option value="months" <?php selected($slm_billing_interval, 'months'); ?>><?php esc_html_e('Month(s)', 'slm-plus'); ?></option>
                                            <option value="years" <?php selected($slm_billing_interval, 'years'); ?>><?php esc_html_e('Year(s)', 'slm-plus'); ?></option>
                                        </select>
                                        <p class="description"><?php esc_html_e('Frequency period: in days, months, or years', 'slm-plus'); ?></p>
                                        <p class="description"><?php esc_html_e('This is used to enable bulk license generation for WooCommerce orders placed before the plugin was active or for orders that do not already contain licenses (default setting:).', 'slm-plus'); ?></p>
                                    </td>
                                </tr>


                                <tr valign="top">
                                    <th scope="row"><?php esc_html_e('Auto Expire License Keys', 'slm-plus'); ?></th>
                                    <td><input name="enable_auto_key_expiration" type="checkbox" <?php if (isset($options['enable_auto_key_expiration']) && $options['enable_auto_key_expiration'] != '') echo ' checked="checked"'; ?> value="1" />
                                        <?php esc_html_e('Enable auto expiration ', 'slm-plus'); ?>
                                        <p class="description"><?php esc_html_e(' When enabled, it will automatically set the status of a license key to "Expired" when the expiry date value  of the key is reached. It doesn\'t remotely deactivate a key. It simply changes the status of the key in your database to expired.', 'slm-plus'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e('General settings', 'slm-plus'); ?></th>
                                    <td>
                                        <input name="slm_stats" type="checkbox" <?php if ($options['slm_stats'] != '') echo ' checked="checked"'; ?> value="1" />
                                        <?php esc_html_e('Enable stats in licenses overview page.', 'slm-plus'); ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"></th>
                                    <td>
                                        <input name="slm_adminbar" type="checkbox" <?php if ($options['slm_adminbar'] != '') echo ' checked="checked"'; ?> value="1" />
                                        <?php esc_html_e('Enable admin bar shortcut link', 'slm-plus'); ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><?php esc_html_e('Multiple items validation', 'slm-plus'); ?></th>
                                    <td>
                                        <input name="slm_multiple_items" type="checkbox" <?php if ($options['slm_multiple_items'] != '') echo ' checked="checked"'; ?> value="1" />
                                        <?php esc_html_e('Enable verification of Item reference.', 'slm-plus'); ?>
                                        <p class="description"><?php esc_html_e("When enabled, there will be another field in Licenced product - Item reference. This field should correspond to the API parameter item_reference of your software.", 'slm-plus'); ?></p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><?php esc_html_e('User permissions', 'slm-plus'); ?></th>
                                    <td>
                                        <input name="allow_user_activation_removal" type="checkbox" <?php if ($options['allow_user_activation_removal'] != '') echo ' checked="checked"'; ?> value="1" />
                                        <?php esc_html_e('Allow users to remove domains/devices in My account.', 'slm-plus'); ?>
                                        <p class="description"><?php esc_html_e("When enabled, users will be able to remove registered domains or devices in their account.", 'slm-plus'); ?></p>
                                    </td>
                                </tr>

                            </table>
                        </div>

                        <div class="integrations hidepanel <?php echo ($tab == 'integrations') ? 'showpanel' : '' ?>">
                            <div class="inside">
                                <h3><?php esc_html_e('WooCommerce Settings', 'slm-plus'); ?> </h3>
                                <table class="form-table">

                                    <tr valign="top">
                                        <th scope="row"> <?php esc_html_e('WooCommerce', 'slm-plus'); ?></th>
                                        <td>
                                            <input name="slm_woo" type="checkbox" <?php if ($options['slm_woo'] != '') echo ' checked="checked"'; ?> value="1" />
                                            <?php esc_html_e('Enable WooCommerce Support (A fully customizable, open source eCommerce platform built for WordPress.)', 'slm-plus'); ?>
                                        </td>
                                    </tr>

                                    <tr valign="top">
                                        <th scope="row"> </th>
                                        <td>
                                            <input name="slm_wc_lic_generator" type="checkbox" <?php if ($options['slm_wc_lic_generator'] != '') echo ' checked="checked"'; ?> value="1" />
                                            <?php esc_html_e('Enable WooCommerce Order License Generator', 'slm-plus'); ?>
                                            <p class="notice notice-warning" style="padding: 10px; margin-top: 5px;">
                                                <?php esc_html_e('This tool generates bulk licenses for WooCommerce orders placed before the plugin was activated or for orders that lack existing licenses.', 'slm-plus'); ?>
                                                <strong><?php esc_html_e('Warning:', 'slm-plus'); ?></strong>
                                                <?php esc_html_e('This action cannot be undone. Please back up your database before proceeding.', 'slm-plus'); ?>
                                            </p>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row"></th>
                                        <td>
                                            <input name="slm_woo_downloads" type="checkbox" <?php if ($options['slm_woo_downloads'] != '') echo ' checked="checked"'; ?> value="1" />
                                            <?php esc_html_e('Disable WooCommerce download page. Process downloads though license order info page.', 'slm-plus'); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"></th>
                                        <td>
                                            <input name="slm_woo_affect_downloads" type="checkbox" <?php if ($options['slm_woo_affect_downloads'] != '') echo ' checked="checked"'; ?> value="1" />
                                            <?php esc_html_e('Enable WooCommerce downloads expiration. Downloads will expire together with corresponding license.', 'slm-plus'); ?>
                                        </td>
                                    </tr>
                                </table>

                                <!-- <h3><?php // esc_html_e('WP eStores', 'slm-plus'); ?> </h3>
                                <table class="form-table">
                                    <tr valign="top">
                                        <th scope="row"> <?php //_e('WP eStores', 'slm-plus'); ?></th>
                                        <td>
                                            <input name="slm_wpestores" type="checkbox" <?php //if ($options['slm_wpestores'] != '') echo ' checked="checked"'; ?> value="1" />
                                            <?php //_e('Enable WordPress eStore Plugin Support.', 'slm-plus'); ?>
                                        </td>
                                    </tr>
                                </table> -->
                            </div>
                        </div>

                        <div class="debug hidepanel <?php echo ($tab == 'debug') ? 'showpanel' : '' ?>">
                            <div class=" inside">
                                <table class="form-table">
                                    <tr valign="top">
                                        <th scope="row"> <?php echo esc_html__('Enable Debug Logging', 'slm-plus'); ?></th>
                                        <td>
                                            <p class="description"><input name="enable_debug" type="checkbox" <?php if ($options['enable_debug'] != '') echo ' checked="checked"'; ?> value="1" />
                                                <?php echo esc_html__('If checked, debug output will be written to log files.', 'slm-plus'); ?></p>
                                        </td>
                                    </tr>

                                    <tr valign="top">
                                        <th scope="row"> <?php echo esc_html__('SLM Plus Version', 'slm-plus'); ?></th>
                                        <td>
                                            <p class="description"><?php echo esc_html(SLM_VERSION); ?></p>
                                        </td>
                                    </tr>

                                    <tr valign="top">
                                        <th scope="row"> <?php echo esc_html__('SLM Databse Version', 'slm-plus'); ?></th>
                                        <td>
                                            <p class="description"> <?php  echo esc_html(SLM_DB_VERSION);?> </p>
                                        </td>
                                    </tr>

                                    <tr valign="top">
                                        <th scope="row"> <?php echo esc_html__('SLM Rewrite Version', 'slm-plus'); ?></th>
                                        <td>
                                            <p class="description"> <?php  echo esc_html(SLM_REWRITE_VERSION);?> </p>
                                        </td>
                                    </tr>


                                </table>
                            </div>
                        </div>

                        <div class="emails hidepanel <?php echo ($tab == 'emails') ? 'showpanel' : '' ?>">
                            <div class=" inside">
                                <table class="form-table">
                                    <tr valign="top">
                                        <th scope="row"> <?php esc_html_e('Expiration reminder', 'slm-plus'); ?></th>
                                        <td>
                                            <textarea name="expiration_reminder_text" id="expiration_reminder_text" cols="80" rows="20"> <?php echo esc_html($options['expiration_reminder_text']); ?> </textarea>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="submit">
                            <input type="submit" class="button-primary" name="slm_save_settings" value=" <?php esc_html_e('Update Options', 'slm-plus'); ?>" />
                        </div>
                    </form>
                </div>
            </div> 
        </div>
        </div>
    <?php
}
