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
                'id'            => '_license_renewal_period_length',
                'label'         => __('Renewal Period Length', 'slmplus'),
                'description'   => __('XX Amount of days, months, or years.', 'slmplus'),
                'type'          => 'text',
                'value'         => get_post_meta($product_id, '_license_renewal_period_length', true) ?: SLM_Helper_Class::slm_get_option('slm_billing_length'),
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
                'value'       => get_post_meta($product_id, '_license_renewal_period_term', true) ?: SLM_Helper_Class::slm_get_option('slm_billing_interval'), // Ensure default value is set to 'years' if empty
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
    
    $_license_renewal_period_length = isset($_POST['_license_renewal_period_length']) ? sanitize_text_field($_POST['_license_renewal_period_length']) : '';
    update_post_meta($post_id, '_license_renewal_period_length', $_license_renewal_period_length);
    
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
            // Run toggleRenewalFields on page load
            toggleRenewalFields();

        // Attach event listener to #_license_type to re-trigger toggle on change
        $("#_license_type").change(function() {
            toggleRenewalFields();
        });

        function toggleRenewalFields() {
            var licType = $("#_license_type").val();
            if (licType === 'lifetime') {
                $('._license_renewal_period_length_field').hide(); // Corrected typo here too
                <?php if ($affect_downloads): ?>
                    $('#_download_limit, #_download_expiry').val('').prop('disabled', true);
                <?php endif; ?>
            } else {
                $('._license_renewal_period_length_field').show();
                <?php if ($affect_downloads): ?>
                    $('#_download_limit, #_download_expiry').prop('disabled', false);
                <?php endif; ?>
            }
        }

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


add_action('add_meta_boxes', 'add_slm_properties_meta_box');
function add_slm_properties_meta_box() {
    add_meta_box(
        'slm_properties_meta_box',
        __('SLM Properties', 'slmplus'),
        'display_slm_properties_meta_box',
        'shop_order',
        'side',
        'default'
    );
}

function display_slm_properties_meta_box($post) {
    $license_key = get_post_meta($post->ID, '_slm_lic_key', true);
    $license_type = get_post_meta($post->ID, '_slm_lic_type', true);

    $order = wc_get_order($post->ID);
    $order_status = $order->get_status();

    // Check if license can be created based on order status and license key existence
    $can_create_license = empty($license_key) && in_array($order_status, ['completed', 'processing']);
    
    // Display license information if a key exists
    if (!empty($license_key)) {
        echo '<p><strong>' . __('License Key:', 'slmplus') . '</strong> ' . esc_html($license_key) . '</p>';
        echo '<p><strong>' . __('License Type:', 'slmplus') . '</strong> ' . esc_html($license_type) . '</p>';
        echo '<p><em>' . __('A license key is already assigned to this order.', 'slmplus') . '</em></p>';

        $license_view_url = esc_url(admin_url('admin.php?page=slm_manage_license&edit_record=' . $license_key));
        echo '<a href="' . $license_view_url . '" class="button button-secondary" target="_blank">' . __('View License', 'slmplus') . '</a>';
    } else if ($can_create_license) {
        // Show the license creation option if no license exists and order is eligible
        echo '<label for="slm_lic_type">' . __('License Type:', 'slmplus') . '</label>';
        echo '<select id="slm_lic_type" name="slm_lic_type" class="postbox">
                <option value="subscription" ' . selected($license_type, 'subscription', false) . '>' . __('Subscription', 'slmplus') . '</option>
                <option value="lifetime" ' . selected($license_type, 'lifetime', false) . '>' . __('Lifetime', 'slmplus') . '</option>
              </select><br><br>';

        echo '<button type="button" class="button button-primary" id="create_license_button">' . __('Create License', 'slmplus') . '</button>';
    } else {
        // Display informational message for new or ineligible orders
        echo '<p><em>' . __('Order must be completed or processing to create a license.', 'slmplus') . '</em></p>';
        echo '<button type="button" class="button button-primary" id="create_license_button" disabled>' . __('Create License', 'slmplus') . '</button>';
    }
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#create_license_button').on('click', function() {
                const licenseType = $('#slm_lic_type').val();
                const orderId = <?php echo esc_js($post->ID); ?>;
                const security = '<?php echo wp_create_nonce('slm_generate_license_nonce'); ?>';
                
                // Fetch user details from the server for validation
                $.post(ajaxurl, {
                    action: 'check_order_user_info',
                    order_id: orderId,
                    security: security
                }, function(userInfoResponse) {
                    if (userInfoResponse.success) {
                        const { last_name, email } = userInfoResponse.data;
                        let proceed = true;

                        // Check for missing details
                        if (!last_name || !email) {
                            proceed = confirm('<?php echo esc_js(__('Warning: The order lacks user information like last name or email. Do you still wish to create the license?', 'slmplus')); ?>');
                        }

                        if (proceed) {
                            if (confirm('<?php echo esc_js(__('Are you sure you want to create a license for this order?', 'slmplus')); ?>')) {
                                $.post(ajaxurl, {
                                    action: 'slm_generate_license_for_order',
                                    order_id: orderId,
                                    lic_type: licenseType,
                                    security: security
                                }, function(response) {
                                    if (response.success) {
                                        location.reload(); 
                                    } else {
                                        alert('<?php echo esc_js(__('License creation failed. Please check the logs.', 'slmplus')); ?>');
                                    }
                                });
                            }
                        }
                    } else {
                        alert('<?php echo esc_js(__('Unable to verify order details. Please try again.', 'slmplus')); ?>');
                    }
                });
            });
        });
    </script>

    <?php
}

add_action('wp_ajax_slm_generate_license_for_order', 'slm_generate_license_for_order_callback');
function slm_generate_license_for_order_callback() {
    check_ajax_referer('slm_generate_license_nonce', 'security');

    global $wpdb;
    $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : null;
    $lic_type = isset($_POST['lic_type']) ? sanitize_text_field($_POST['lic_type']) : 'subscription';

    if (!$order_id) {
        wp_send_json_error(['message' => __('Invalid order ID', 'slmplus')]);
    }

    $order = wc_get_order($order_id);
    if (!$order || !in_array($order->get_status(), ['completed', 'processing'])) {
        wp_send_json_error(['message' => __('Order must be completed or processing to create a license', 'slmplus')]);
    }

    // Gather required order details
    $first_name     = $order->get_billing_first_name();
    $last_name      = $order->get_billing_last_name();
    $email          = $order->get_billing_email();
    $purchase_id    = $order->get_id();
    $txn_id         = $order->get_transaction_id();
    $company_name   = $order->get_billing_company();
    $date_created   = $order->get_date_created()->date('Y-m-d');
    $user_id        = $order->get_user_id();
    $product_ref    = $order->get_items() ? $order->get_items()[0]->get_name() : ''; // Using the first item name for simplicity

    $slm_billing_length    = SLM_API_Utility::get_slm_option('slm_billing_length');
    $slm_billing_interval  = SLM_API_Utility::get_slm_option('slm_billing_interval');

    $date_expiry = $lic_type === 'lifetime' ? date('Y-m-d', strtotime("+120 years", strtotime($date_created))) :
        date('Y-m-d', strtotime("+$slm_billing_length $slm_billing_interval", strtotime($date_created)));

    // License data array, using all fields from original
    $license_data = [
        'slm_action'            => 'slm_create_new',
        'lic_status'            => 'pending',
        'lic_type'              => $lic_type,
        'first_name'            => $first_name,
        'last_name'             => $last_name,
        'email'                 => $email,
        'purchase_id_'          => $purchase_id,
        'txn_id'                => $txn_id,
        'company_name'          => $company_name,
        'max_allowed_domains'   => SLM_DEFAULT_MAX_DOMAINS,
        'max_allowed_devices'   => SLM_DEFAULT_MAX_DEVICES,
        'date_created'          => $date_created,
        'date_expiry'           => $date_expiry,
        'product_ref'           => $product_ref,
        'current_ver'           => SLM_API_Utility::get_slm_option('license_current_version'),
        'until'                 => SLM_API_Utility::get_slm_option('license_until_version'),
        'subscr_id'             => $user_id,
        'item_reference'        => $order_id,
        'slm_billing_length'    => $slm_billing_length,
        'slm_billing_interval'  => $slm_billing_interval,
        'secret_key'            => KEY_API
    ];

    $response = wp_remote_post(SLM_API_URL, [
        'method'    => 'POST',
        'body'      => $license_data,
        'timeout'   => 45,
        'sslverify' => false,
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => __('API request failed', 'slmplus')]);
    }

    $body = wp_remote_retrieve_body($response);
    $api_response = json_decode($body, true);

    if ($api_response && isset($api_response['result']) && $api_response['result'] === 'success') {
        $license_key = sanitize_text_field($api_response['key']);
        update_post_meta($order_id, '_slm_lic_key', $license_key);
        update_post_meta($order_id, '_slm_lic_type', $lic_type); // Save the license type

        $order->add_order_note(
            sprintf(__('License Key generated: %s', 'slmplus'), $license_key)
        );

        wp_send_json_success(['message' => __('License created successfully', 'slmplus')]);
    } else {
        wp_send_json_error(['message' => __('License creation failed', 'slmplus')]);
    }
}

add_action('wp_ajax_check_order_user_info', 'check_order_user_info_callback');
function check_order_user_info_callback() {
    check_ajax_referer('slm_generate_license_nonce', 'security');

    $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : null;
    if (!$order_id) {
        wp_send_json_error(['message' => __('Invalid order ID', 'slmplus')]);
    }

    $order = wc_get_order($order_id);
    if ($order) {
        $last_name = $order->get_billing_last_name();
        $email = $order->get_billing_email();
        wp_send_json_success(['last_name' => $last_name, 'email' => $email]);
    } else {
        wp_send_json_error(['message' => __('Order not found', 'slmplus')]);
    }
}