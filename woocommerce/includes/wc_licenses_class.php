<?php

/**
 * @author Michel Velis <michel@epikly.com>
 * @link   https://github.com/michelve/software-license-manager
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die();
}

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
                        <tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-completed order">
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


                                <button type="button" class="btn btn-default lic-view-details-btn" data-toggle="modal" data-target="#licModal_<?php echo $license_info->id; ?>">
                                    View
                                </button>

                            </td>
                        </tr>
                        <tr>

                            <td colspan="5">
                                <div>

                                    <?php
                                    global $wpdb;
                                    $detailed_license_info =  $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "lic_key_tbl WHERE `id` = '" . $license_info->id . "' ORDER BY `id` LIMIT 0,1000;", ARRAY_A);
                                    ?>
                                    <div>

                                        <!-- Modal -->
                                        <div class="modal fade" id="licModal_<?php echo $license_info->id; ?>" tabindex="-1" role="dialog" aria-labelledby="licModal_<?php echo $license_info->id; ?>Label" aria-hidden="true">
                                            <div class="modal-dialog modal-md modal-dialog-centered" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="Label"><?php echo esc_html__('License Key:', 'softwarelicensemanager'); ?> <span class="badge badge-dark"><?php echo $license_info->license_key; ?></span></h5>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">

                                                        <ul class="nav nav-tabs npm" id="myLics" role="tablist">
                                                            <li class="nav-item">
                                                                <a class="nav-link active" id="lic-info-<?php echo $license_info->id; ?>-tab" data-toggle="tab" href="#lic-info-<?php echo $license_info->id; ?>" role="tab" aria-controls="lic-info-<?php echo $license_info->id; ?>" aria-selected="true"><?php echo esc_html__('License Information', 'softwarelicensemanager'); ?></a>
                                                            </li>

                                                            <li class="nav-item">
                                                                <a class="nav-link" id="lic-devices-<?php echo $license_info->id; ?>-tab" data-toggle="tab" href="#lic-devices-<?php echo $license_info->id; ?>" role="tab" aria-controls="lic-devices-<?php echo $license_info->id; ?>" aria-selected="false"><?php echo esc_html__('Activations', 'softwarelicensemanager'); ?></a>
                                                            </li>

                                                            <li class="nav-item">
                                                                <a class="nav-link" id="lic-code-<?php echo $license_info->id; ?>-tab" data-toggle="tab" href="#lic-code-<?php echo $license_info->id; ?>" role="tab" aria-controls="lic-code-<?php echo $license_info->id; ?>" aria-selected="false"><?php echo esc_html__('Copy License', 'softwarelicensemanager'); ?></a>
                                                            </li>
                                                        </ul>

                                                        <div class="tab-content" id="MyLicDetails">
                                                            <div class="tab-pane fade show active" id="lic-info-<?php echo $license_info->id; ?>" role="tabpanel" aria-labelledby="lic-info-<?php echo $license_info->id; ?>-tab">

                                                                <div class="card" style="width: 18rem;">
                                                                    <div class="card-header">
                                                                        <?php echo esc_html__('License information', 'softwarelicensemanager'); ?>
                                                                    </div>
                                                                    <ul class="list-group list-group-flush lic-group-details">
                                                                        <li class="list-group-item"><?php echo esc_html__('Expiration', 'softwarelicensemanager'); ?> <span><time datetime="<?php echo $license_info->date_expiry; ?>"><?php echo $license_info->date_expiry; ?></time></span></li>
                                                                        <li class="list-group-item"><?php echo esc_html__('Allowed devices', 'softwarelicensemanager'); ?> <span><?php echo $license_info->max_allowed_devices; ?></span></li>
                                                                        <li class="list-group-item"><?php echo esc_html__('Allowed Domains', 'softwarelicensemanager'); ?> <span><?php echo $license_info->max_allowed_domains; ?></span></li>
                                                                        <li class="list-group-item"><?php echo esc_html__('License type', 'softwarelicensemanager'); ?> <span class="badge badge-pill badge-info"><?php echo $license_info->lic_type; ?></span></li>
                                                                        <li class="list-group-item"><?php echo esc_html__('Date renewed', 'softwarelicensemanager'); ?> <span><?php echo $license_info->date_renewed; ?></span></li>
                                                                        <li class="list-group-item"><?php echo esc_html__('Activation Date', 'softwarelicensemanager'); ?> <span><?php echo $license_info->date_activated; ?></span></li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                            <div class="clear"></div>

                                                            <div class="tab-pane fade" id="lic-devices-<?php echo $license_info->id; ?>" role="tabpanel" aria-labelledby="lic-devices-<?php echo $license_info->id; ?>-tab">
                                                                <div class="row" style="width: 100%;">
                                                                    <div class="slm-activated-on domains-list col-md-6">
                                                                        <?php SLM_Utility::get_license_activation($license_info->license_key, SLM_TBL_LIC_DOMAIN, 'Domains', 'Domains', $allow_domain_removal); ?>
                                                                    </div>

                                                                    <div class="slm-activated-on domains-list col-md-6">
                                                                        <?php SLM_Utility::get_license_activation($license_info->license_key, SLM_TBL_LIC_DEVICES, 'Devices', 'Devices', $allow_domain_removal); ?>
                                                                    </div>

                                                                </div>
                                                                <div class="clear"></div>
                                                                <div class="slm_ajax_msg"></div>
                                                                <div class="clear"></div>
                                                            </div>
                                                            <div class="clear"></div>

                                                            <div class="tab-pane fade" id="lic-code-<?php echo $license_info->id; ?>" role="tabpanel" aria-labelledby="lic-code-<?php echo $license_info->id; ?>-tab">
                                                                <div class="row">

                                                                    <div class="col-md-12 lic-copy-code">
                                                                        <pre style="max-height: 200px;">
                                                                            <?php
                                                                            $license_key_json_data  = json_encode($detailed_license_info, JSON_PRETTY_PRINT);
                                                                            echo esc_html__($license_key_json_data);
                                                                            ?>
                                                                        </pre>

                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer lic-details-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
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
        if ($allow_domain_removal == true && is_user_logged_in()) :
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
