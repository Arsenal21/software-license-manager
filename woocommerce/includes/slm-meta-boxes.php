<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die();
}

function slm_register_product_type() {
    if ( class_exists( 'WC_Product' ) && ! class_exists( 'WC_Product_SLM_License' ) ) {
        // WooCommerce 3.0 and above use WC_Product_Simple as the base class
        if ( version_compare( WC()->version, '3.0.0', '>=' ) ) {
            class WC_Product_SLM_License extends WC_Product_Simple {
                protected $product_type = 'slm_license';

                public function __construct( $product = 0 ) {
                    parent::__construct( $product );
                }

                public function get_type() {
                    return 'slm_license';
                }
            }
        } else {
            // Older versions use WC_Product as the base class
            class WC_Product_SLM_License extends WC_Product {
                protected $product_type = 'slm_license';

                public function __construct( $product = 0 ) {
                    parent::__construct( $product );
                }

                public function get_type() {
                    return 'slm_license';
                }
            }
        }
    }
}
add_action('init', 'slm_register_product_type');


function slm_register_product_class($classname, $product_type) {
    if ($product_type == 'slm_license') { 
        $classname = 'WC_Product_SLM_License';
    }
    return $classname;
}
add_filter('woocommerce_product_class', 'slm_register_product_class', 10, 2);


function slm_add_product_type($types) {
    $types['slm_license'] = __('License product', 'slmplus');
    return $types;
    error_log("Saving product type for Product ID: " . $types);

}
add_filter('product_type_selector', 'slm_add_product_type');


/**
 * Add 'License Manager' product option.
 */
function add_wc_slm_data_tab_enabled_product_option($product_type_options) {
    // Check if the current product type is the custom license product type
    if (isset($_GET['product_type']) && $_GET['product_type'] === 'slm_license') {
        $product_type_options['wc_slm_data_tab_enabled'] = array(
            'id'            => '_wc_slm_data_tab_enabled',
            'wrapper_class' => 'show_if_slm_license',
            'label'         => __('License Manager', 'slmplus'),
            'default'       => 'no',
            'description'   => __('Enables the license creation API.', 'slmplus'),
            'type'          => 'checkbox'
        );
    }
    return $product_type_options;
}
add_filter('product_type_options', 'add_wc_slm_data_tab_enabled_product_option', 10);


/**
 * CSS To Add Custom tab Icon
 */
function wcpp_custom_style() {
    ?>
    <style>
        #woocommerce-product-data ul.wc-tabs li.wc_slm_data_tab_options a:before {
            font-family: Dashicons;
            content: "\f160";
        }
    </style>
    <script>
        jQuery(document).ready(function($) {
            // Toggle Visibility for Tab Fields Based on 'License Manager' Checkbox
            $('input#_wc_slm_data_tab_enabled').change(function() {
                var isTabEnabled = $(this).is(':checked');
                $('.show_if_wc_slm_data_tab_enabled').toggle(isTabEnabled);
                $('.hide_if_wc_slm_data_tab_enabled').toggle(!isTabEnabled);
            }).trigger('change'); // Trigger change event to apply changes on page load

            // Toggle License Renewal Period Fields Based on License Type
            $('#_license_type').change(function() {
                if ($(this).val() === 'lifetime') {
                    $('._license_renewal_period_field, ._license_renewal_period_term_field').hide();
                    $('#_license_renewal_period_lenght').val('onetime').prop("disabled", true);
                } else {
                    $('._license_renewal_period_field, ._license_renewal_period_term_field').show();
                    $('#_license_renewal_period_lenght').val('').prop("disabled", false);
                }
            }).trigger('change'); // Trigger change to apply initial setting on load
        });
    </script>
    <?php
}
add_action('admin_head', 'wcpp_custom_style');

/**
 * Add Custom WooCommerce Product Data Tab
 */
function wc_slm_add_tab($wc_slm_data_tabs) {
    $wc_slm_data_tabs['wc_slm_data_tab'] = array(
        'label'     => __('License Info', 'slmplus'),
        'target'    => 'wc_slm_meta',
        'class'     => array('show_if_slm_license', 'show_if_wc_slm_data_tab_enabled'),
    );
    return $wc_slm_data_tabs;
}
add_filter('woocommerce_product_data_tabs', 'wc_slm_add_tab');

/**
 * Custom WooCommerce Data Panel
 */
function wc_slm_data_panel() {
    global $post;
    $product_id = get_the_ID();
    $slm_options = get_option('slm_plugin_options');
    ?>
    <div id='wc_slm_meta' class='panel woocommerce_options_panel'>
        <div class='options_group'>
            <?php

            // Domain Licenses Input
            $value = get_post_meta($product_id, '_domain_licenses', true);
            $value = ($value === '') ? SLM_Helper_Class::slm_get_option('default_max_domains') : $value;

            woocommerce_wp_text_input(array(
                'id'            => '_domain_licenses',
                'label'         => __('Domain Licenses', 'slmplus'),
                'placeholder'   => SLM_Helper_Class::slm_get_option('default_max_domains'),
                'desc_tip'      => 'true',
                'value'         => $value,
                'type'          => 'number',
                'custom_attributes' => array('step' => 'any', 'min' => 0),
                'description'   => __('Enter the allowed number of domains this license can have (websites).', 'slmplus')
            ));

            // Device Licenses Input
            $value = get_post_meta($product_id, '_devices_licenses', true);
            $value = ($value === '') ? SLM_Helper_Class::slm_get_option('default_max_devices') : $value;

            woocommerce_wp_text_input(array(
                'id'            => '_devices_licenses',
                'label'         => __('Devices Licenses', 'slmplus'),
                'placeholder'   => SLM_Helper_Class::slm_get_option('default_max_devices'),
                'desc_tip'      => 'true',
                'value'         => $value,
                'type'          => 'number',
                'custom_attributes' => array('step' => 'any', 'min' => 0),
                'description'   => __('Enter the allowed number of devices this license can have (computers, mobile, etc).', 'slmplus')
            ));

            // Item Reference Field (if enabled)
            if (!empty($slm_options['slm_multiple_items']) && $slm_options['slm_multiple_items'] == 1) {
                woocommerce_wp_text_input(array(
                    'id'            => '_license_item_reference',
                    'label'         => __('Item Reference', 'slmplus'),
                    'placeholder'   => __("Software's item reference"),
                    'desc_tip'      => 'true',
                    'description'   => __('Enter the item reference of your application, theme, or plug-in. The license will be then bound to this exact software.', 'slmplus')
                ));
            }

            // License Type Dropdown
            woocommerce_wp_select(array(
                'id'            => '_license_type',
                'label'         => __('License Type', 'slmplus'),
                'desc_tip'      => 'true',
                'description'   => __('Type of license: subscription base or lifetime', 'slmplus'),
                'options'       => array(
                    'none'          => __('Select one', 'slmplus'),
                    'subscription'  => __('Subscription', 'slmplus'),
                    'lifetime'      => __('Lifetime', 'slmplus'),
                )
            ));

            // License Renewal Period Length
            woocommerce_wp_text_input(array(
                'id'            => '_license_renewal_period_lenght',
                'label'         => __('Renewal Period Length', 'slmplus'),
                'description'   => __('Amount of days, months, or years.', 'slmplus'),
                'type'          => 'text', // Change 'number' to 'text'
                'value'         => get_post_meta($product_id, '_license_renewal_period_lenght', true) ?: '1', // Fallback to '1' if empty
            ));

            

            // License Renewal Period Term Dropdown
            woocommerce_wp_select(array(
                'id'            => '_license_renewal_period_term',
                'label'         => __('Expiration Term', 'slmplus'),
                'placeholder'   => 'select time frame',
                'description'   => __('Choose between days, months, or years', 'slmplus'),
                'options'       => array(
                    'days'      => __('Day(s)', 'slmplus'),
                    'months'    => __('Month(s)', 'slmplus'),
                    'years'     => __('Year(s)', 'slmplus'),
                    'onetime'   => __('One Time', 'slmplus'),
                ),
                'value'       => get_post_meta($product_id, '_license_renewal_period_term', true) ?: 'years', // Ensure default value is set to 'years' if empty
            ));

            echo '<div class="clear"><hr></div>';

            // Current Version Input
            woocommerce_wp_text_input(array(
                'id'            => '_license_current_version',
                'label'         => __('Current Version', 'slmplus'),
                'placeholder'   => '0.0.0',
                'desc_tip'      => 'true',
                'description'   => __('Enter the current version of your application, theme, or plug-in', 'slmplus')
            ));

            // Until Version Input
            woocommerce_wp_text_input(array(
                'id'            => '_license_until_version',
                'label'         => __('Until Version', 'slmplus'),
                'placeholder'   => '0.0.0',
                'desc_tip'      => 'true',
                'description'   => __('Enter the version until support expires.', 'slmplus')
            ));
            ?>
        </div>
    </div>
    <?php
}
add_action('woocommerce_product_data_panels', 'wc_slm_data_panel');



/** Hook callback function to save custom fields information */
function wc_slm_save_data($post_id) {
    // Sanitize and save domain licenses
    $_domain_licenses = isset($_POST['_domain_licenses']) ? intval($_POST['_domain_licenses']) : 0;
    update_post_meta($post_id, '_domain_licenses', $_domain_licenses);

    // Save the tab enable option
    $is_wc_slm_data_tab_enabled = isset($_POST['_wc_slm_data_tab_enabled']) ? 'yes' : 'no';
    update_post_meta($post_id, '_wc_slm_data_tab_enabled', $is_wc_slm_data_tab_enabled);

    // Save the item reference
    $_license_item_reference = isset($_POST['_license_item_reference']) ? sanitize_text_field($_POST['_license_item_reference']) : '';
    update_post_meta($post_id, '_license_item_reference', empty($_license_item_reference) ? 'default' : $_license_item_reference);

    // Save license type
    $_license_type = isset($_POST['_license_type']) ? sanitize_text_field($_POST['_license_type']) : '';
    update_post_meta($post_id, '_license_type', $_license_type);

    // Sanitize and save device licenses
    $_devices_licenses = isset($_POST['_devices_licenses']) ? intval($_POST['_devices_licenses']) : 0;
    update_post_meta($post_id, '_devices_licenses', $_devices_licenses);

    // Handle license renewal period
    
    $_license_renewal_period_lenght = isset($_POST['_license_renewal_period_lenght']) ? sanitize_text_field($_POST['_license_renewal_period_lenght']) : '';
    update_post_meta($post_id, '_license_renewal_period_lenght', $_license_renewal_period_lenght);
    
    // Handle license renewal period term
    if (isset($_POST['_license_renewal_period_term'])) {
        $_license_renewal_period_term = sanitize_text_field($_POST['_license_renewal_period_term']);
        if ($_license_type == 'lifetime' && $_license_renewal_period_term !== 'onetime') {
            $_license_renewal_period_term = 'onetime';
        }
        update_post_meta($post_id, '_license_renewal_period_term', $_license_renewal_period_term);
    }

    // Save current version
    $_license_current_version = isset($_POST['_license_current_version']) ? sanitize_text_field($_POST['_license_current_version']) : '';
    update_post_meta($post_id, '_license_current_version', $_license_current_version);

    // Save until version
    $_license_until_version = isset($_POST['_license_until_version']) ? sanitize_text_field($_POST['_license_until_version']) : '';
    update_post_meta($post_id, '_license_until_version', $_license_until_version);
}

add_action('woocommerce_process_product_meta', 'wc_slm_save_data');

function slm_license_admin_custom_js() {
    if ('product' !== get_post_type()) {
        return;
    }

    $slm_options = get_option('slm_plugin_options', array());
    $affect_downloads = isset($slm_options['slm_woo_affect_downloads']) && $slm_options['slm_woo_affect_downloads'] === '1';

    ?>
    <script type='text/javascript'>
        jQuery(document).ready(function($) {
            // For Price tab
            function toggleRenewalFields() {
                var licType = $("#_license_type").val();
                if (licType === 'lifetime') {
                    $('._license_renewal_period_lenght_field').hide();
                    <?php if ($affect_downloads): ?>
                        $('#_download_limit, #_download_expiry').val('').prop('disabled', true);
                    <?php endif; ?>
                } else {
                    $('._license_renewal_period_lenght_field').show();
                    <?php if ($affect_downloads): ?>
                        $('#_download_limit, #_download_expiry').prop('disabled', false);
                    <?php endif; ?>
                }
            }

            // Initial trigger to properly show/hide fields based on saved values
            toggleRenewalFields();

            // Handle license type change
            $('#_license_type').on('change', function() {
                toggleRenewalFields();
            });

            // Handle changes on download limit and expiry if downloads are affected
            <?php if ($affect_downloads): ?>
                $('#_download_limit, #_download_expiry').on('change', function() {
                    if ($('#_license_type').val() === 'lifetime') {
                        $(this).val('').prop('disabled', true);
                    }
                });
            <?php endif; ?>

            // Set product data tabs to be visible if license type
            $('.product_data_tabs .general_tab').addClass('show_if_slm_license').show();
            $("label[for='_virtual'], label[for='_downloadable']").addClass('show_if_slm_license').show();
            $(".show_if_external").addClass('hide_if_slm_license').hide();
            $('#general_product_data .pricing').addClass('show_if_slm_license slm-display').show();
            $("#_virtual, #_downloadable").prop("checked", true);

            // For Inventory tab
            $('.inventory_options').addClass('show_if_slm_license').show();
            $('#inventory_product_data ._manage_stock_field, #inventory_product_data ._sold_individually_field').closest('.options_group').addClass('show_if_slm_license').show();
            $('.shipping_options, .marketplace-suggestions_options').addClass('hide_if_slm_license').hide();
            
            // Trigger change event for wc_slm_data_tab_enabled to ensure fields are visible
            $('input#_wc_slm_data_tab_enabled').trigger('change');
        });
    </script>
    <?php
}

add_action('admin_footer', 'slm_license_admin_custom_js');
