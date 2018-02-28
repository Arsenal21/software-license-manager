<?php

/**
* @author Michel Velis <michel@epikly.com>
* @link   https://github.com/michelve/software-license-manager
*/


function getActiveUser($action) {
    $info           = '';
    $current_user   = wp_get_current_user();

    if ($action == 'email') {
        $info = esc_html( $current_user->user_email);
    }
    if ($action == 'id') {
        $info =  esc_html( $current_user->ID );
    }
    return $info;
}

class Epikly_Woo_Account {

    /**
     * Custom endpoint name.
     *
     * @var string
     */
    public static $endpoint = 'my-licenses';

    /**
     * Plugin actions.
     */
    public function __construct() {
        // Actions used to insert a new endpoint in the WordPress.
        add_action( 'init', array( $this, 'add_endpoints' ) );
        add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );

        // Change the My Accout page title.
        add_filter( 'the_title', array( $this, 'endpoint_title' ) );

        // Insering your new tab/page into the My Account page.
        add_filter( 'woocommerce_account_menu_items', array( $this, 'new_menu_items' ) );
        add_action( 'woocommerce_account_' . self::$endpoint .  '_endpoint', array( $this, 'endpoint_content' ) );
    }

    /**
     * Register new endpoint to use inside My Account page.
     *
     * @see https://developer.wordpress.org/reference/functions/add_rewrite_endpoint/
     */
    public function add_endpoints() {
        add_rewrite_endpoint( self::$endpoint, EP_ROOT | EP_PAGES );
    }

    /**
     * Add new query var.
     *
     * @param array $vars
     * @return array
     */
    public function add_query_vars( $vars ) {
        $vars[] = self::$endpoint;

        return $vars;
    }

    /**
     * Set endpoint title.
     *
     * @param string $title
     * @return string
     */
    public function endpoint_title( $title ) {
        global $wp_query;

        $is_endpoint = isset( $wp_query->query_vars[ self::$endpoint ] );

        if ( $is_endpoint && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {
            // New page title.
            $title = __( 'My Licenses', 'woocommerce' );

            remove_filter( 'the_title', array( $this, 'endpoint_title' ) );
        }

        return $title;
    }

    /**
     * Insert the new endpoint into the My Account menu.
     *
     * @param array $items
     * @return array
     */
    public function new_menu_items( $items ) {
        // Remove the logout menu item.
        $logout = $items['customer-logout'];
        unset( $items['customer-logout'] );

        // Insert your custom endpoint.
        $items[ self::$endpoint ] = __( 'My Licenses', 'woocommerce' );

        // Insert back the logout item.
        $items['customer-logout'] = $logout;

        return $items;
    }

    /**
     * Endpoint HTML content.
     */
    public function endpoint_content() {

        global $wpdb;
        $wc_billing_email = get_user_meta( get_current_user_id(), 'billing_email', true );
        $result = $wpdb->get_results ( "SELECT * FROM ". $wpdb->prefix."lic_key_tbl WHERE email LIKE '%".getActiveUser('email')."%' OR email LIKE '%".$wc_billing_email."%' ORDER BY `email` DESC LIMIT 0,1000" );

        $result_array = $wpdb->get_results ( "SELECT * FROM ". $wpdb->prefix."lic_key_tbl WHERE email LIKE '%".getActiveUser('email')."%' OR email LIKE '%".$wc_billing_email."%' ORDER BY `email` DESC LIMIT 0,1000", ARRAY_A );

        $table_start = '
        <header class="entry-header">
                <h2 class="entry-title" itemprop="name">My Licenses</h2>
        </header>
        <table class="shop_table shop_table_responsive licenses_list my_account_orders"> <thead> <tr> <th class="licenses_id">ID</th> <th class="licenses_status">Status</th> <th class="licenses_product">License Key</th> <th class="licenses_recurring">Expiration</th><th class="view_license_info">Info</th> </tr> </thead> <tbody>';
        $table_end = '</tbody> </table>';


        echo $table_start;
        foreach ( $result as $license_info ) : ?>
            <tr class="licenses_list">
                <td data-title="ID" class="licenses_id"><?php echo $license_info->txn_id; ?></td>
                <td data-title="Status" class="licenses_status"><?php echo $license_info->lic_status; ?></td>
                <td data-title="Products" class="licenses_product"><?php echo $license_info->license_key; ?></td>
                <td data-title="Recurring" class="licenses_recurring"><?php echo $license_info->date_expiry; ?></td>
                <td data-title="view_license_info" class="view_license_info"> <a href="#view" data-licensekey="<?php echo $license_info->license_key; ?>" class="btn btn-default">view</a></td>
            </tr>
            <tr class="license_info_detailed" style="display: none">
                <td colspan="5">
                    <?php

                        $detailed_domain_info =  $wpdb->get_results ( "SELECT * FROM ".$wpdb->prefix."lic_reg_domain_tbl WHERE `lic_key` = '".$license_info->license_key."' ORDER BY `lic_key_id` LIMIT 0,1000;", ARRAY_A);

                        $detailed_devices_info =  $wpdb->get_results ( "SELECT * FROM ".$wpdb->prefix."lic_reg_devices_tbl WHERE `lic_key` = '".$license_info->license_key."' ORDER BY `lic_key_id` LIMIT 0,1000;", ARRAY_A);

                        // var_dump($detailed_domain_info);
                        // var_dump($detailed_devices_info);

                        foreach($detailed_domain_info as $domain_info){
                            echo "<h5>Domain(s)</h5>";
                            if (isset($domain_info["lic_key"])){
                                echo '<p>'.$domain_info["registered_domain"].'</p>';
                            }
                            else {
                                echo "no data available";
                            }
                        }

                        foreach($detailed_devices_info as $devices_info){
                            echo "<h5>Device(s)</h5>";
                            if (isset($devices_info["lic_key"])){
                                echo '<p>'.$devices_info["registered_devices"].'</p>';
                            }
                            else {
                                echo "no data available";
                            }
                        }


                    ?>


                </td>
            </tr>
        <?php endforeach;
        echo $table_end;

        $licenses_status_array = array();
        foreach ( $result_array as $license_is_active ) {
            $licenses_status_array[] = $license_is_active["lic_status"];
        }

        //print_r($licenses_status_array);

        // check if Download Manager is active
        if( function_exists( 'add_wdm_settings_tab' ) ) {
            if (in_array("pending", $licenses_status_array) || in_array("active", $licenses_status_array)) {
                echo '
                <div class="clear"></div>
                <header class="entry-header">
                    <h2 class="entry-title" itemprop="name">My Downloads</h2>
                </header>'
                ;
                echo do_shortcode('[wpdm_all_packages]');
            }
            else {
                echo " <p> No active subscriptions found. Renew or reactivate your subscription. </p> ";
            }
        }
    }

    /**
     * Plugin install action.
     * Flush rewrite rules to make our custom endpoint available.
     */
    public static function install() {
        flush_rewrite_rules();
    }
}

new Epikly_Woo_Account();

// Flush rewrite rules on plugin activation.
register_activation_hook( __FILE__, array( 'Epikly_Woo_Account', 'install' ) );
