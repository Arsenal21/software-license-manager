<?php

// Author: Michel Velis
// Author URI: http://pilotkit.co
// Since: 4.4.0
// from: https://gist.github.com/JeroenSormani/6b710d079386d096f932

add_filter('woocommerce_product_data_tabs', 'wc_slm_add_tab');
add_action('woocommerce_process_product_meta', 'wc_slm_save_data');
add_action('woocommerce_product_data_panels', 'wc_slm_data_panel');
//add_filter('product_type_options', 'add_wc_slm_data_tab_enabled_product_option'); //legacy
add_action('init', 'slm_register_product_type');
add_filter('product_type_selector', 'slm_add_product_type');
add_action('admin_footer', 'slm_license_admin_custom_js');
/**
 * Add 'License' product option
 */
function add_wc_slm_data_tab_enabled_product_option($product_type_options)
{
    $product_type_options['wc_slm_data_tab_enabled'] = array(
        'id'            => '_wc_slm_data_tab_enabled',
        'wrapper_class' => 'show_if_slm_license',
        'label'         => __('License Manager', 'softwarelicensemanager'),
        'default'       => 'no',
        'description'   => __('Enables the license creation api.', 'softwarelicensemanager')
    );
    return $product_type_options;
}


/** CSS To Add Custom tab Icon */
function wcpp_custom_style()
{
    ?><style>
        #woocommerce-product-data ul.wc-tabs li.wc_slm_data_tab_options a:before {
            font-family: Dashicons;
            content: "\f160";
        }
    </style>

    <script>
        jQuery(document).ready(function($) {
            jQuery('input#_wc_slm_data_tab_enabled').change(function() {

                var is_wc_slm_data_tab_enabled = jQuery('input#_wc_slm_data_tab_enabled:checked').size();
                // console.log( is_wc_slm_data_tab_enabled );
                jQuery('.show_if_wc_slm_data_tab_enabled').hide();
                jQuery('.hide_if_wc_slm_data_tab_enabled').hide();
                if (is_wc_slm_data_tab_enabled) {
                    jQuery('.hide_if_wc_slm_data_tab_enabled').hide();
                }
                if (is_wc_slm_data_tab_enabled) {
                    jQuery('.show_if_wc_slm_data_tab_enabled').show();
                }
            });
            jQuery('input#_wc_slm_data_tab_enabled').trigger('change');
        });

        jQuery(document).ready(function() {
            jQuery('#_license_type').change(function() {
                if (jQuery(this).val() == 'lifetime') {
                    jQuery('#_license_renewal_period').val('0');
                }
            });
        });
    </script><?php
        }
        add_action('admin_head', 'wcpp_custom_style');

        function wc_slm_add_tab($wc_slm_data_tabs)
        {
            $wc_slm_data_tabs['wc_slm_data_tab'] = array(
                'label'     => __('Licensing', 'softwarelicensemanager'),
                'target'    => 'wc_slm_meta',
                'class'     => array('show_if_slm_license', 'show_if_wc_slm_data_tab_enabled'),
            );

            return $wc_slm_data_tabs;
        }

        function wc_slm_data_panel()
        {
            global $post;
            ?>
    <div id='wc_slm_meta' class='panel woocommerce_options_panel'>

        <?php ?>
        <div class='options_group'>
            <?php
            woocommerce_wp_text_input(
                array(
                    'id'            => '_domain_licenses',
                    'label'         => __('Domain Licenses', 'softwarelicensemanager'),
                    'placeholder'   => '0',
                    'desc_tip'      => 'true',
                    'type'          => 'number',
                    'description'   => __('Enter the allowed amount of domains this license can have (websites).', 'softwarelicensemanager')
                )
            );
            woocommerce_wp_text_input(
                array(
                    'id'            => '_devices_licenses',
                    'label'         => __('Devices Licenses', 'softwarelicensemanager'),
                    'placeholder'   => '0',
                    'desc_tip'      => 'true',
                    'type'          => 'number',
                    'description'   => __('Enter the allowed amount of devices this license can have (computers, mobile, etc).', 'softwarelicensemanager')
                )
            );
            woocommerce_wp_select(
                array(
                    'id'            => '_license_type',
                    'label'         => __('License Type', 'softwarelicensemanager'),
                    'placeholder'   => 'Select one',
                    'desc_tip'      => 'true',
                    'description'   => __('type of license: subscription base or lifetime', 'softwarelicensemanager'),
                    'options'       => array(
                        'none'      => __('Select one', 'softwarelicensemanager'),
                        'subscription'   => __('subscription', 'softwarelicensemanager'),
                        'lifetime'       => __('lifetime', 'softwarelicensemanager'),
                    )
                )
            );
            woocommerce_wp_text_input(
                array(
                    'id'            => '_license_renewal_period',
                    'label'         => __('Renewal period ', 'softwarelicensemanager'),
                    'placeholder'   => '0',
                    'desc_tip'      => 'true',
                    'description'   => __('License renewal period(yearly) , enter 0 for lifetime.', 'softwarelicensemanager')
                )
            );

            woocommerce_wp_text_input(
                array(
                    'id'            => '_license_current_version',
                    'label'         => __('Current Version', 'softwarelicensemanager'),
                    'placeholder'   => '0.0.0',
                    'desc_tip'      => 'true',
                    'description' => __('Enter the current version of your application, theme, or plug-in', 'softwarelicensemanager')
                )
            );

            woocommerce_wp_text_input(
                array(
                    'id'            => '_license_until_version',
                    'label'         => __('Until Version', 'softwarelicensemanager'),
                    'placeholder'   => '0.0.0',
                    'desc_tip'      => 'true',
                    'description' => __('Enter the version until support expires.', 'softwarelicensemanager')
                )
            );
            ?>
        </div>
    </div><?php
    }

    /** Hook callback function to save custom fields information */
    function wc_slm_save_data($post_id)
    {
        // _domain_licenses
        // _devices_licenses
        // _license_type
        // _license_current_version
        // _license_until_version

        $_domain_licenses = $_POST['_domain_licenses'];
        if (!empty($_domain_licenses)) {
            update_post_meta($post_id, '_domain_licenses', esc_attr($_domain_licenses));
        }

        $is_wc_slm_data_tab_enabled = isset($_POST['_wc_slm_data_tab_enabled']) ? 'yes' : 'no';
        update_post_meta($post_id, '_wc_slm_data_tab_enabled', $is_wc_slm_data_tab_enabled);

        $_license_type = $_POST['_license_type'];
        if (!empty($_license_type)) {
            update_post_meta($post_id, '_license_type', esc_attr($_license_type));
        }

        $_devices_licenses = $_POST['_devices_licenses'];
        if (!empty($_devices_licenses)) {
            update_post_meta($post_id, '_devices_licenses', esc_attr($_devices_licenses));
        }

        $_license_renewal_period = $_POST['_license_renewal_period'];
        if (!empty($_license_renewal_period)) {
            update_post_meta($post_id, '_license_renewal_period', esc_attr($_license_renewal_period));
        }

        $_license_current_version = $_POST['_license_current_version'];
        if (!empty($_license_current_version)) {
            update_post_meta($post_id, '_license_current_version', esc_attr($_license_current_version));
        }

        $_license_until_version = $_POST['_license_until_version'];
        if (!empty($_license_until_version)) {
            update_post_meta($post_id, '_license_until_version', esc_attr($_license_until_version));
        }
    }


    function slm_register_product_type()
    {
        class WC_Product_SLM_License extends WC_Product
        {
            public function __construct($product)
            {
                $this->product_type = 'slm_license';
                parent::__construct($product);
            }
        }
    }

    function slm_add_product_type($types)
    {
        $types['slm_license'] = __('License product', 'softwarelicensemanager');
        return $types;
    }
    function slm_license_admin_custom_js()
    {
        if ('product' != get_post_type()) :
            return;
        endif;
        ?>
    <script type='text/javascript'>
        jQuery(document).ready(function() {
            //for Price tab
            jQuery('.product_data_tabs .general_tab').addClass('show_if_slm_license').show();
            jQuery('#general_product_data .pricing').addClass('show_if_slm_license').show();
            //for Inventory tab
            jQuery('.inventory_options').addClass('show_if_slm_license').show();
            jQuery('#inventory_product_data ._manage_stock_field').addClass('show_if_slm_license').show();
            jQuery('#inventory_product_data ._sold_individually_field').parent().addClass('show_if_slm_license').show();
            jQuery('#inventory_product_data ._sold_individually_field').addClass('show_if_slm_license').show();

            jQuery('.shipping_options').addClass('hide_if_slm_license').hide();
            jQuery('.marketplace-suggestions_options').addClass('hide_if_slm_license').hide();
            jQuery('input#_wc_slm_data_tab_enabled').trigger('change');
        });
    </script>
<?php
}
