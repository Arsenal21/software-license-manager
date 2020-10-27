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
            $info = esc_html__($current_user->user_email);
        }
        if ($action == 'id') {
            $info =  esc_html__($current_user->ID);
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
        global $wpdb, $wp_query;
        $slm_options = get_option('slm_plugin_options');
        $allow_domain_removal = $slm_options['allow_user_activation_removal'] == 1 ? true : false;
        $class_ = 0;
        $class_id_ = 0;
        $get_user_info = $get_user_email = '';
        // get user billing email
        $wc_billing_email = get_user_meta(get_current_user_id(), 'billing_email', true);

        // if wp billing is empty
        if ($wc_billing_email == '') {
            $wc_billing_email   = get_userdata(get_current_user_id())->user_email;
        }
        $result = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "lic_key_tbl WHERE email='" . $wc_billing_email . "' ORDER BY `email` DESC LIMIT 0,1000");
        $slm_hide = '';

        if (empty($result)) {
?>
            <div class="woocommerce-Message woocommerce-Message--info woocommerce-info">
                <a class="woocommerce-Button button" href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>"><?php echo esc_html__('Browse products', 'softwarelicensemanager'); ?>
                </a>
                <?php echo esc_html__('No licenses available yet.', 'softwarelicensemanager'); ?>
            </div>
        <?php
            $slm_hide = 'style="display:none"';
        }
        ?>

        <?php
        if (SLM_Helper_Class::slm_get_option('slm_front_conflictmode') == 1) : ?>
            <?php
            add_action('wp_footer', function () {
                wp_enqueue_style('slm-bootstrap', esc_url("https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css"), array(), '', 'all');
                wp_enqueue_script('slm-bootstrap', esc_url('https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js'), array(), '', true);
            });
            ?>
        <?php endif; ?>




        <!-- Button trigger modal -->
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal">
            Launch demo modal
        </button>

        <!-- Modal -->
        <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        ...
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary">Save changes</button>
                    </div>
                </div>
            </div>
        </div>








        <div class="woocommerce-slm-content" <?php echo esc_html__($slm_hide); ?>>
            <table id="slm_licenses_table" class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table" style="border-collapse:collapse;">
                <thead>
                    <tr>
                        <th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number"><?php echo esc_html__('Order', 'softwarelicensemanager'); ?></th>
                        <th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number"><?php echo esc_html__('Status', 'softwarelicensemanager'); ?></th>
                        <th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number"><?php echo esc_html__('Product', 'softwarelicensemanager'); ?></th>
                        <th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number"><?php echo esc_html__('License key', 'softwarelicensemanager'); ?></th>
                        <th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number"><?php echo esc_html__('Renews on', 'softwarelicensemanager'); ?></th>
                        <th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number"><?php echo esc_html__('Info', 'softwarelicensemanager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($result as $license_info) : ?>
                        <tr data-toggle="collapse" data-target=".demo<?php echo $class_++; ?>" class="woocommerce-orders-table__row woocommerce-orders-table__row--status-completed order">
                            <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-number slm-order" data-title="<?php echo __('Order', 'softwarelicensemanager'); ?>"><a href="<?php echo get_home_url() . '/my-account/view-order/' . $license_info->purchase_id_; ?>">#<?php echo $license_info->purchase_id_; ?></a></td>

                            <td class="slm-status" data-title="<?php echo esc_html__('Status', 'softwarelicensemanager'); ?>">
                                <?php $key_status = $license_info->lic_status; ?>
                                <div class="slm-key-status"> <span class="key-status <?php echo $key_status; ?>"><?php echo $key_status; ?></span>
                                </div>
                            </td>

                            <td class="slm-product-reference" data-title="<?php echo esc_html__('Product', 'softwarelicensemanager'); ?>">
                                <?php
                                $product_id     = $license_info->product_ref;
                                $product_name   = get_the_title($product_id);

                                if (!empty($product_name) && isset($product_name)) {
                                    echo '<a href="' . get_permalink($product_id) . '"> ' . $product_name . '</a>';
                                }
                                ?>
                            </td>

                            <td class="slm-key" data-title="<?php echo esc_html__('License Key', 'softwarelicensemanager'); ?>"><?php echo $license_info->license_key; ?></td>

                            <td class="slm-renewal" data-title="<?php echo esc_html__('Renews on', 'softwarelicensemanager'); ?>">
                                <?php
                                $expiration = new DateTime($license_info->date_expiry);
                                $today      = new DateTime();

                                if ($license_info->lic_type == 'subscription' && $license_info->date_expiry != '0000-00-00') {
                                    if ($expiration < $today) {
                                        echo "<span style='color: red'><strong>" . esc_html__('Expired') . "</strong></span>";
                                    } else {
                                        echo $license_info->date_expiry;
                                    }
                                } else {
                                    echo esc_html__('Lifetime');
                                }
                                ?>
                            </td>
                            <td class="slm-view" data-title="<?php echo esc_html__('view', 'softwarelicensemanager'); ?>">
                                <a href="" class="woocommerce-button button view">
                                    <?php echo esc_html__('view', 'softwarelicensemanager'); ?>
                                </a>
                            </td>
                        </tr>
                        <tr class="parent">

                            <td colspan="5" class="hiddenRow">
                                <div class="collapse demo<?php echo $class_id_++; ?> slm-shadow">
                                    <div class="slm_ajax_msg"></div>
                                    <?php
                                    global $wpdb;
                                    $detailed_license_info =  $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "lic_key_tbl WHERE `license_key` = '" . $license_info->license_key . "' ORDER BY `id` LIMIT 0,1000;", ARRAY_A);
                                    ?>
                                    <div class="row" style="padding: 16px;">
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th data-title=<?php echo esc_html__('Expiration', 'softwarelicensemanager'); ?> class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number"><?php echo esc_html__('Expiration', 'softwarelicensemanager'); ?></th>
                                                    <th data-title=<?php echo esc_html__('Allowed devices', 'softwarelicensemanager'); ?> class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number"><?php echo esc_html__('Allowed devices', 'softwarelicensemanager'); ?></th>
                                                    <th data-title=<?php echo esc_html__('Allowed Domains', 'softwarelicensemanager'); ?> class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number"><?php echo esc_html__('Allowed Domains', 'softwarelicensemanager'); ?></th>
                                                    <th data-title=<?php echo esc_html__('License type', 'softwarelicensemanager'); ?> class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number"><?php echo esc_html__('License type', 'softwarelicensemanager'); ?></th>
                                                    <th data-title=<?php echo esc_html__('Date renewed', 'softwarelicensemanager'); ?> class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number"><?php echo esc_html__('Date renewed', 'softwarelicensemanager'); ?></th>
                                                    <th data-title=<?php echo esc_html__('Activation Date', 'softwarelicensemanager'); ?> class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number"><?php echo esc_html__('Activation Date', 'softwarelicensemanager'); ?></th>
                                                </tr>
                                            </thead>
                                            <tr>
                                                <td data-title=<?php echo __('Expiration', 'softwarelicensemanager'); ?> class="slm-expiration"><time datetime="<?php echo $license_info->date_expiry; ?>"><?php echo $license_info->date_expiry; ?></time></td>
                                                <td data-title=<?php echo esc_html__('Allowed Devices', 'softwarelicensemanager'); ?>><?php echo $license_info->max_allowed_devices; ?></td>
                                                <td data-title=<?php echo esc_html__('Allowed Domains', 'softwarelicensemanager'); ?>><?php echo $license_info->max_allowed_domains; ?></td>
                                                <td data-title=<?php echo esc_html__('License type', 'softwarelicensemanager'); ?>><?php echo $license_info->lic_type; ?></td>
                                                <td data-title=<?php echo esc_html__('Date renewed', 'softwarelicensemanager'); ?>><?php echo $license_info->date_renewed; ?></td>
                                                <td data-title=<?php echo esc_html__('Activation date', 'softwarelicensemanager'); ?>><?php echo $license_info->date_activated; ?></td>
                                            </tr>
                                        </table>

                                        <br>
                                        <div class="row" style="width: 100%;">
                                            <div class="slm-activated-on domains-list col-md-6">
                                                <?php SLM_Utility::get_license_activation($license_info->license_key, SLM_TBL_LIC_DOMAIN, esc_html__('Domains', 'softwarelicensemanager'), $allow_domain_removal); ?>
                                            </div>

                                            <div class="slm-activated-on domains-list col-md-6">
                                                <?php SLM_Utility::get_license_activation($license_info->license_key, SLM_TBL_LIC_DEVICES, esc_html__('Devices', 'softwarelicensemanager'), $allow_domain_removal); ?>
                                            </div>
                                        </div>
                                        <div class="clear"></div>

                                        <div class="row slm-export">
                                            <?php
                                            $license_key_json_data  = json_encode(array_values($detailed_license_info));
                                            ?>
                                            <div class="col-md-12 slm-action-export">
                                                <input type="button" id="export-lic-key" data-licdata='<?php echo esc_html__($license_key_json_data); ?>' value="<?php echo esc_html__('Export license', 'softwarelicensemanager'); ?>" class="btn btn-secondary slm-button" />
                                            </div>
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
        if ($allow_domain_removal == true) :
        ?>

            <script>
                jQuery(document).ready(function() {
                    jQuery('.deactivate_lic_key').click(function(event) {
                        var id = jQuery(this).attr("id");
                        var activation_type = jQuery(this).attr('data-activation_type');
                        var class_name = '.lic-entry-' + id;

                        jQuery(this).text('Removing');
                        jQuery.get('<?php echo esc_url(home_url('/')); ?>' + 'wp-admin/admin-ajax.php?action=del_activation&id=' + id + '&activation_type=' + activation_type, function(data) {
                            if (data == 'success') {
                                jQuery(class_name).remove();
                                jQuery('.slm_ajax_msg').html('<div class="alert alert-primary" role="alert"><?php echo esc_html__('License key was deactivated!', 'softwarelicensemanager'); ?></div>');
                            } else {
                                jQuery('.slm_ajax_msg').html('<div class="alert alert-danger" role="alert"> <?php echo esc_html__('License key was not deactivated!', 'softwarelicensemanager'); ?></div>');
                            }
                        });
                    });
                });
            </script>
<?php
        endif;
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
