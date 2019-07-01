<?php

/**
 * @author Michel Velis <michel@epikly.com>
 * @link   https://github.com/michelve/software-license-manager
 */


class SLM_Woo_Account
{
    public static $endpoint = 'my-licenses';
    public function __construct()
    {
        // Actions used to insert a new endpoint in the WordPress.
        add_action('init', array($this, 'add_endpoints'));
        add_filter('query_vars', array($this, 'add_query_vars'), 0);
        // Change the My Accout page title.
        add_filter('the_title', array($this, 'endpoint_title'));
        // Insering your new tab/page into the My Account page.
        add_filter('woocommerce_account_menu_items', array($this, 'slm_woo_menu_list'));
        add_action('woocommerce_account_' . self::$endpoint .  '_endpoint', array($this, 'endpoint_content'));
    }

    public function getActiveUser($action)
    {
        $info           = '';
        $current_user   = wp_get_current_user();
        if ($action == 'email') {
            $info = esc_html($current_user->user_email);
        }
        if ($action == 'id') {
            $info =  esc_html($current_user->ID);
        }
        return $info;
    }

    public function add_endpoints()
    {
        add_rewrite_endpoint(self::$endpoint, EP_ROOT | EP_PAGES);
    }

    public function add_query_vars($vars)
    {
        $vars[] = self::$endpoint;
        return $vars;
    }

    public function endpoint_title($title)
    {
        global $wp_query;
        $is_endpoint = isset($wp_query->query_vars[self::$endpoint]);
        if ($is_endpoint && !is_admin() && is_main_query() && in_the_loop() && is_account_page()) {
            // New page title.
            $title = __('My Licenses', 'softwarelicensemanager');
            remove_filter('the_title', array($this, 'endpoint_title'));
        }
        return $title;
    }

    public function slm_woo_menu_list($items)
    {
        // Remove the logout menu item.
        $logout = $items['customer-logout'];
        unset($items['customer-logout']);

        // Insert your custom endpoint.
        $items[self::$endpoint] = __('My Licenses', 'softwarelicensemanager');

        // Insert back the logout item.
        $items['customer-logout'] = $logout;
        return $items;
    }

    public function endpoint_content()
    {
        global $wpdb;
        $class_ = 0;
        $class_id_ = 0;

        // get user email
        $wc_billing_email = get_user_meta(get_current_user_id(), 'billing_email', true);
        $result = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "lic_key_tbl WHERE email LIKE '%" . SLM_Woo_Account::getActiveUser('email') . "%' OR email LIKE '%" . $wc_billing_email . "%' ORDER BY `email` DESC LIMIT 0,1000");

        $result_array = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "lic_key_tbl WHERE email LIKE '%" . SLM_Woo_Account::getActiveUser('email') . "%' OR email LIKE '%" . $wc_billing_email . "%' ORDER BY `email` DESC LIMIT 0,1000", ARRAY_A);

        $get_subscription = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "postmeta WHERE meta_value = '273' LIMIT 0,1000", ARRAY_A);
        $lic_order_id = array();

        ?>

    <div class="woocommerce-slm-content">
        <table id="slm_licenses_table" class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table" style="border-collapse:collapse;">
            <thead>
                <tr>
                    <th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number">Order</th>
                    <th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number">Status</th>
                    <th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number">License Key</th>
                    <th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number">Expiration</th>
                    <th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number">Info</th>
                </tr>
            </thead>
            <tbody>
                <?php
                global $wp_query;

                foreach ($result as $license_info) : ?>
                    <?php
                    global $wpdb;

                    $get_subscription = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "postmeta WHERE `meta_value` = '" . $license_info->purchase_id_ . "' LIMIT 0,1000;", ARRAY_A);
                    ?>

                    <tr data-toggle="collapse" data-target=".demo<?php echo $class_++; ?>" class="woocommerce-orders-table__row woocommerce-orders-table__row--status-completed order">
                        <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-number slm-order"><a href="<?php echo get_site_url() . '/my-account/view-order/' . $license_info->purchase_id_; ?>">#<?php echo $license_info->purchase_id_; ?></a></td>
                        <td class="slm-status">
                            <?php
                            $key_status = $license_info->lic_status;
                            ?>
                            <div class="slm-key-status"> <span class="key-status <?php echo $key_status; ?>"><?php echo $key_status; ?></span></div>
                        </td>
                        <td class="slm-key"><?php echo $license_info->license_key; ?></td>
                        <td class="slm-expiration"><time datetime="<?php echo $license_info->date_expiry; ?>"><?php echo $license_info->date_expiry; ?></time></td>
                        <td class="slm-view"><a href="#" class="woocommerce-button button view">View Info</a></td>
                    </tr>
                    <tr class="parent">
                        <td colspan="5" class="hiddenRow">
                            <div class="collapse demo<?php echo $class_id_++; ?>">
                                <?php
                                global $wpdb;

                                $detailed_license_info =  $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "lic_key_tbl WHERE `license_key` = '" . $license_info->license_key . "' ORDER BY `id` LIMIT 0,1000;", ARRAY_A);

                                $detailed_domain_info =  $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "lic_reg_domain_tbl WHERE `lic_key` = '" . $license_info->license_key . "' ORDER BY `lic_key_id` LIMIT 0,1000;", ARRAY_A);

                                $detailed_devices_info =  $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "lic_reg_devices_tbl WHERE `lic_key` = '" . $license_info->license_key . "' ORDER BY `lic_key_id` LIMIT 0,1000;", ARRAY_A);
                                ?>

                                <div class="row pt-3">
                                    <div class="domains-list col-md-6">
                                        <h5>Domain(s)</h5>
                                        <ul class="list-unstyled">
                                            <?php
                                            // var_dump($detailed_domain_info);
                                            // var_dump($detailed_devices_info);
                                            if (count($detailed_domain_info) == 0) {
                                                echo "<li>no data available</li>";
                                            } else {
                                                foreach ($detailed_domain_info as $domain_info) {

                                                    if (isset($domain_info["lic_key"]) && !empty($domain_info["lic_key"])) {
                                                        echo '<li> <a href="http://' . $domain_info["registered_domain"] . '" target="_blank">' . $domain_info["registered_domain"] . '</a></li>';
                                                    } else {
                                                        echo "<li>no data available</li>";
                                                    }
                                                }
                                            }
                                            $out                    = array_values($detailed_license_info);
                                            $license_key_json_data  = json_encode($out);

                                            ?>

                                        </ul>
                                    </div>
                                    <div class="devices-list col-md-6">
                                        <h5>Device(s)</h5>
                                        <ul class="list-unstyled">
                                            <?php
                                            if (count($detailed_domain_info) == 0) {
                                                echo "<li>no data available</li>";
                                            } else {
                                                foreach ($detailed_devices_info as $devices_info) {
                                                    if (isset($devices_info["lic_key"]) && !empty($devices_info["lic_key"])) {
                                                        echo '<li>' . $devices_info["registered_devices"] . '</li>';
                                                    } else {
                                                        echo "<li>no data available</li>";
                                                    }
                                                }
                                            }
                                            ?>
                                        </ul>
                                    </div>
                                </div>
                                <div class="clear"></div>

                                <!-- <div class="view-order"> <a href="<?php ?>">View Order #<?php ?></a> </div> -->
                                <div class="lic-actions border-top mt-5 pt-5">

                                    <div class="download-files row">
                                        <?php
                                        // woo download integration
                                        $slm_woo_order_id       = $license_info->purchase_id_;
                                        $slm_woo_product_id     = $license_info->product_ref;
                                        $woo_download_db        = 'woocommerce_downloadable_product_permissions';

                                        $get_woo_downloads =  $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . $woo_download_db . " WHERE `product_id` = '" . $slm_woo_product_id . "' ORDER BY `product_id` LIMIT 0,1000;", ARRAY_A);

                                        if ($license_info->lic_status == 'active' || $license_info->lic_status == 'pending') {

                                            echo '<div class="col-md-6"> <input type="button" id="export-lic-key" data-licdata="' . $license_key_json_data . '" value="Export license" class="btn btn-secondary" /> </div>';

                                            foreach ($get_woo_downloads as $download) {
                                                $get_files = get_post_meta($slm_woo_product_id, '_downloadable_files', true);
                                                $file_name = $get_files[$download["download_id"]]['name'];
                                                //var_dump($my_meta);

                                                if (isset($download["order_key"]) && !empty($download["order_key"]) && $download["user_id"] == SLM_Woo_Account::getActiveUser('id')) {
                                                    echo '<div class="col-md-6"> <a href="' . get_site_url() . '?download_file=' . $download["product_id"] . '&order=' . $download["order_key"] . '&email=' . $download["user_email"] . '&key=' . $download["download_id"] . '" class="btn btn-secondary"> Download ' . $file_name . ' </a></div>';
                                                }
                                            }
                                        } else {
                                            echo '<p class="alert alert-danger"> You are not allowed to view/download files. Renew or upgarde your license.</p>';
                                        } ?>
                                    </div>
                                </div>
                                <div class="clear"></div>

                            </div>
                        </td>
                    </tr>
                <?php endforeach;
            ?>
            </tbody>
        </table>
    </div>
    <?php

    $licenses_status_array = array();
    foreach ($result_array as $license_is_active) {
        $licenses_status_array[] = $license_is_active["lic_status"];
    }




    if (null !== SLM_Helper_Class::slm_get_option('slm_dl_manager') && SLM_Helper_Class::slm_get_option('slm_dl_manager') == 1) {
        //print_r($licenses_status_array);
        // check if Download Manager is active
        if (function_exists('add_wdm_settings_tab')) {
            if (in_array("pending", $licenses_status_array) || in_array("active", $licenses_status_array)) {
                echo ' <div class="clear"></div> <header class="entry-header"> <h2 class="entry-title" itemprop="name">My Downloads</h2> </header>';
                echo do_shortcode('[wpdm_all_packages]');
            } else {
                echo " <p> No active subscriptions found. Renew or reactivate your subscription. </p> ";
            }
        }
    }
}

/**
 * Plugin install action.
 * Flush rewrite rules to make our custom endpoint available.
 */
public static function install()
{
    flush_rewrite_rules();
}
}
new SLM_Woo_Account();

// Flush rewrite rules on plugin activation.
register_activation_hook(__FILE__, array('SLM_Woo_Account', 'install'));
