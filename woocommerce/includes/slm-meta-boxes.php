<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die();
}

function slm_register_product_type()
{
    if (class_exists('WC_Product') && ! class_exists('WC_Product_SLM_License')) {
        // WooCommerce 3.0 and above use WC_Product_Simple as the base class
        if (version_compare(WC()->version, '3.0.0', '>=')) {
            class WC_Product_SLM_License extends WC_Product_Simple
            {
                protected $product_type = 'slm_license';

                public function __construct($product = 0)
                {
                    parent::__construct($product);
                }

                public function get_type()
                {
                    return 'slm_license';
                }
            }
        } else {
            // Older versions use WC_Product as the base class
            class WC_Product_SLM_License extends WC_Product
            {
                protected $product_type = 'slm_license';

                public function __construct($product = 0)
                {
                    parent::__construct($product);
                }

                public function get_type()
                {
                    return 'slm_license';
                }
            }
        }
    }
}
add_action('init', 'slm_register_product_type');


function slm_register_product_class($classname, $product_type)
{
    if ($product_type == 'slm_license') {
        $classname = 'WC_Product_SLM_License';
    }
    return $classname;
}
add_filter('woocommerce_product_class', 'slm_register_product_class', 10, 2);


function slm_add_product_type($types)
{
    $types['slm_license'] = __('License product', 'slm-plus');
    return $types;
    error_log("Saving product type for Product ID: " . $types);
}
add_filter('product_type_selector', 'slm_add_product_type');


/**
 * Add 'License Manager' product option.
 */
function add_wc_slm_data_tab_enabled_product_option($product_type_options)
{
    // Check if the current product type is the custom license product type
    if (isset($_GET['product_type']) && $_GET['product_type'] === 'slm_license') {
        $product_type_options['wc_slm_data_tab_enabled'] = array(
            'id'            => '_wc_slm_data_tab_enabled',
            'wrapper_class' => 'show_if_slm_license',
            'label'         => __('License Manager', 'slm-plus'),
            'default'       => 'no',
            'description'   => __('Enables the license creation API.', 'slm-plus'),
            'type'          => 'checkbox'
        );
    }
    return $product_type_options;
}
add_filter('product_type_options', 'add_wc_slm_data_tab_enabled_product_option', 10);


/**
 * CSS To Add Custom tab Icon
 */
function wcpp_custom_style()
{
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
function wc_slm_add_tab($wc_slm_data_tabs)
{
    $wc_slm_data_tabs['wc_slm_data_tab'] = array(
        'label'     => __('License Info', 'slm-plus'),
        'target'    => 'wc_slm_meta',
        'class'     => array('show_if_slm_license', 'show_if_wc_slm_data_tab_enabled'),
    );
    return $wc_slm_data_tabs;
}
add_filter('woocommerce_product_data_tabs', 'wc_slm_add_tab');

/**
 * Custom WooCommerce Data Panel
 */
function wc_slm_data_panel()
{
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
                'label'         => __('Domain Licenses', 'slm-plus'),
                'placeholder'   => SLM_Helper_Class::slm_get_option('default_max_domains'),
                'desc_tip'      => 'true',
                'value'         => $value,
                'type'          => 'number',
                'custom_attributes' => array('step' => 'any', 'min' => 0),
                'description'   => __('Enter the allowed number of domains this license can have (websites).', 'slm-plus')
            ));

            // Device Licenses Input
            $value = get_post_meta($product_id, '_devices_licenses', true);
            $value = ($value === '') ? SLM_Helper_Class::slm_get_option('default_max_devices') : $value;

            woocommerce_wp_text_input(array(
                'id'            => '_devices_licenses',
                'label'         => __('Devices Licenses', 'slm-plus'),
                'placeholder'   => SLM_Helper_Class::slm_get_option('default_max_devices'),
                'desc_tip'      => 'true',
                'value'         => $value,
                'type'          => 'number',
                'custom_attributes' => array('step' => 'any', 'min' => 0),
                'description'   => __('Enter the allowed number of devices this license can have (computers, mobile, etc).', 'slm-plus')
            ));

            // Item Reference Field (if enabled)
            if (!empty($slm_options['slm_multiple_items']) && $slm_options['slm_multiple_items'] == 1) {
                woocommerce_wp_text_input(array(
                    'id'            => '_license_item_reference',
                    'label'         => __('Item Reference', 'slm-plus'),
                    'placeholder'   => __("Software's item reference", 'slm-plus'),
                    'desc_tip'      => 'true',
                    'description'   => __('Enter the item reference of your application, theme, or plug-in. The license will be then bound to this exact software.', 'slm-plus')
                ));
            }

            // License Type Dropdown
            woocommerce_wp_select(array(
                'id'            => '_license_type',
                'label'         => __('License Type', 'slm-plus'),
                'desc_tip'      => 'true',
                'description'   => __('Type of license: subscription base or lifetime', 'slm-plus'),
                'options'       => array(
                    'none'          => __('Select one', 'slm-plus'),
                    'subscription'  => __('Subscription', 'slm-plus'),
                    'lifetime'      => __('Lifetime', 'slm-plus'),
                )
            ));

            // License Renewal Period Length
            woocommerce_wp_text_input(array(
                'id'            => '_license_renewal_period_length',
                'label'         => __('Renewal Period Length', 'slm-plus'),
                'description'   => __('XX Amount of days, months, or years.', 'slm-plus'),
                'type'          => 'text',
                'value'         => get_post_meta($product_id, '_license_renewal_period_length', true) ?: SLM_Helper_Class::slm_get_option('slm_billing_length'),
            ));


            // License Renewal Period Term Dropdown
            woocommerce_wp_select(array(
                'id'            => '_license_renewal_period_term',
                'label'         => __('Expiration Term', 'slm-plus'),
                'placeholder'   => 'select time frame',
                'description'   => __('Choose between days, months, or years', 'slm-plus'),
                'options'       => array(
                    'days'      => __('Day(s)', 'slm-plus'),
                    'months'    => __('Month(s)', 'slm-plus'),
                    'years'     => __('Year(s)', 'slm-plus'),
                    'onetime'   => __('One Time', 'slm-plus'),
                ),
                'value'       => get_post_meta($product_id, '_license_renewal_period_term', true) ?: SLM_Helper_Class::slm_get_option('slm_billing_interval'), // Ensure default value is set to 'years' if empty
            ));

            echo '<div class="clear"><hr></div>';

            // Current Version Input
            woocommerce_wp_text_input(array(
                'id'            => '_license_current_version',
                'label'         => __('Current Version', 'slm-plus'),
                'placeholder'   => '0.0.0',
                'desc_tip'      => 'true',
                'description'   => __('Enter the current version of your application, theme, or plug-in', 'slm-plus')
            ));

            // Until Version Input
            woocommerce_wp_text_input(array(
                'id'            => '_license_until_version',
                'label'         => __('Until Version', 'slm-plus'),
                'placeholder'   => '0.0.0',
                'desc_tip'      => 'true',
                'description'   => __('Enter the version until support expires.', 'slm-plus')
            ));
            ?>
        </div>
    </div>
<?php
}
add_action('woocommerce_product_data_panels', 'wc_slm_data_panel');



/** Hook callback function to save custom fields information */
function wc_slm_save_data($post_id)
{
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

function slm_license_admin_custom_js()
{
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
function add_slm_properties_meta_box()
{
    add_meta_box(
        'slm_properties_meta_box',
        __('SLM Properties', 'slm-plus'),
        'display_slm_properties_meta_box',
        'shop_order',
        'side',
        'default'
    );
}

function display_slm_properties_meta_box($post)
{

    global $wpdb;

    $order = wc_get_order($post->ID);
    if (!$order) {
        echo '<p><em>' . esc_html__('Order not found.', 'slm-plus') . '</em></p>';
        return;
    }

    $order_id = $order->get_id();
    $order_status = $order->get_status();

    // Fetch license details from the database
    $license_data = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT license_key, lic_type FROM " . SLM_TBL_LICENSE_KEYS . " WHERE wc_order_id = %d LIMIT 1",
            $order_id
        )
    );

    $license_key = $license_data->license_key ?? '';
    $license_type = $license_data->lic_type ?? '';

    // Determine if a new license can be created based on the order status
    $can_create_license = empty($license_key) && in_array($order_status, ['completed', 'processing']);

    // Display license information if it exists
    if (!empty($license_key)) {
        $license_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM " . SLM_TBL_LICENSE_KEYS . " WHERE license_key = %s LIMIT 1",
                $license_key
            )
        );

        if ($license_id) {
            echo '<p><strong>' . esc_html__('License Key:', 'slm-plus') . '</strong> ' . esc_html($license_key) . '</p>';
            echo '<p><strong>' . esc_html__('License Type:', 'slm-plus') . '</strong> ' . esc_html($license_type) . '</p>';
            echo '<p><em>' . esc_html__('A license key is already assigned to this order.', 'slm-plus') . '</em></p>';

            // Link to view the license using its ID
            $license_view_url = esc_url(admin_url('admin.php?page=slm_manage_license&edit_record=' . $license_id));
            echo '<a href="' . esc_url($license_view_url) . '" class="button button-secondary" target="_blank">' . esc_html__('View License', 'slm-plus') . '</a>';
        } else {
            echo '<p><em>' . esc_html__('License information could not be retrieved.', 'slm-plus') . '</em></p>';
        }
    }
    elseif ($can_create_license) {
        // Show license creation options for eligible orders
        echo '<label for="slm_lic_type">' . esc_html__('License Type:', 'slm-plus') . '</label>';
        echo '<select id="slm_lic_type" name="slm_lic_type" class="postbox">
                <option value="subscription" ' . selected($license_type, 'subscription', false) . '>' . esc_html__('Subscription', 'slm-plus') . '</option>
                <option value="lifetime" ' . selected($license_type, 'lifetime', false) . '>' . esc_html__('Lifetime', 'slm-plus') . '</option>
              </select><br><br>';

        echo '<button type="button" class="button button-primary" id="create_license_button">' . esc_html__('Create License', 'slm-plus') . '</button>';
    } else {
        // Informational message for ineligible orders
        echo '<p><em>' . esc_html__('Order must be completed or processing to create a license.', 'slm-plus') . '</em></p>';
        echo '<button type="button" class="button button-primary" id="create_license_button" disabled>' . esc_html__('Create License', 'slm-plus') . '</button>';
    }

?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#create_license_button').on('click', function() {
                const licenseType = $('#slm_lic_type').val();
                const orderId = <?php echo esc_js($post->ID); ?>;
                const security = '<?php echo esc_js(wp_create_nonce('slm_generate_license_nonce')); ?>';

                // Fetch user details from the server for validation
                $.post(ajaxurl, {
                    action: 'check_order_user_info',
                    order_id: orderId,
                    security: security
                }, function(userInfoResponse) {
                    if (userInfoResponse.success) {
                        const {
                            last_name,
                            email
                        } = userInfoResponse.data;
                        let proceed = true;

                        // Check for missing details
                        if (!last_name || !email) {
                            proceed = confirm('<?php echo esc_js(__('Warning: The order lacks user information like last name or email. Do you still wish to create the license?', 'slm-plus')); ?>');
                        }

                        if (proceed) {
                            if (confirm('<?php echo esc_js(__('Are you sure you want to create a license for this order?', 'slm-plus')); ?>')) {
                                $.post(ajaxurl, {
                                    action: 'slm_generate_license_for_order',
                                    order_id: orderId,
                                    lic_type: licenseType,
                                    security: security
                                }, function(response) {
                                    if (response.success) {
                                        location.reload();
                                    } else {
                                        alert('<?php echo esc_js(__('License creation failed. Please check the logs.', 'slm-plus')); ?>');
                                    }
                                });
                            }
                        }
                    } else {
                        alert('<?php echo esc_js(__('Unable to verify order details. Please try again.', 'slm-plus')); ?>');
                    }
                });
            });
        });
    </script>

<?php
}

add_action('wp_ajax_slm_generate_license_for_order', 'slm_generate_license_for_order_callback');
function slm_generate_license_for_order_callback()
{
    // Validate nonce for security
    check_ajax_referer('slm_generate_license_nonce', 'security');

    global $wpdb;

    $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : null;
    $lic_type = isset($_POST['lic_type']) ? sanitize_text_field($_POST['lic_type']) : 'subscription';

    if (!$order_id) {
        wp_send_json_error(['message' => __('Invalid order ID', 'slm-plus')]);
    }

    // Fetch the WooCommerce order
    $order = wc_get_order($order_id);
    if (!$order || !in_array($order->get_status(), ['completed', 'processing'])) {
        wp_send_json_error(['message' => __('Order must be completed or processing to create a license', 'slm-plus')]);
    }

    // Fetch necessary details from the order
    $first_name = $order->get_billing_first_name();
    $last_name = $order->get_billing_last_name();
    $email = $order->get_billing_email();
    $txn_id = $order->get_transaction_id();
    $company_name = $order->get_billing_company();
    $date_created = $order->get_date_created() ? $order->get_date_created()->date('Y-m-d') : current_time('mysql');
    $user_id = $order->get_user_id();

    // Default values from options
    $slm_billing_length = SLM_API_Utility::get_slm_option('slm_billing_length');
    $slm_billing_interval = SLM_API_Utility::get_slm_option('slm_billing_interval');
    $default_domains = SLM_DEFAULT_MAX_DOMAINS;
    $default_devices = SLM_DEFAULT_MAX_DEVICES;

    // Determine expiration date
    $date_expiry = $lic_type === 'lifetime' 
        ? date('Y-m-d', strtotime('+120 years', strtotime($date_created)))
        : date('Y-m-d', strtotime("+$slm_billing_length $slm_billing_interval", strtotime($date_created)));

    $licenses = [];
    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();
        $product = wc_get_product($product_id);

        if ($product && $product->is_type('slm_license')) {
            // Fetch custom fields for the license
            $product_data = [
                'current_ver' => get_post_meta($product_id, '_license_current_version', true),
                'until_ver' => get_post_meta($product_id, '_license_until_version', true),
                'max_devices' => get_post_meta($product_id, '_devices_licenses', true) ?: $default_devices,
                'max_domains' => get_post_meta($product_id, '_domain_licenses', true) ?: $default_domains,
                'item_reference' => get_post_meta($product_id, '_license_item_reference', true),
            ];

            // Generate a new license key
            $new_license_key = slm_get_license(KEY_API_PREFIX);

            // Insert the new license into the database
            $wpdb->insert(SLM_TBL_LICENSE_KEYS, [
                'license_key' => $new_license_key,
                'wc_order_id' => $order_id,
                'product_ref' => $product_id,
                'txn_id' => $order_id,
                'purchase_id_' => $order_id,
                'subscr_id' => $user_id,
                'item_reference' => $product_data['item_reference'],
                'max_allowed_domains' => intval($product_data['max_domains']),
                'max_allowed_devices' => intval($product_data['max_devices']),
                'date_created' => $date_created,
                'date_expiry' => $date_expiry,
                'slm_billing_length' => intval($slm_billing_length),
                'slm_billing_interval' => sanitize_text_field($slm_billing_interval),
                'current_ver' => sanitize_text_field($product_data['current_ver']),
                'until' => sanitize_text_field($product_data['until_ver']),
                'lic_type' => sanitize_text_field($lic_type),
                'email' => sanitize_email($email),
                'first_name' => sanitize_text_field($first_name),
                'last_name' => sanitize_text_field($last_name),
                'company_name' => sanitize_text_field($company_name),
                'lic_status' => 'pending',
            ]);

            // Add the license key to the order note
            $order->add_order_note(sprintf(__('License Key generated: %s', 'slm-plus'), $new_license_key));
            
            // Collect license info for the response
            $licenses[] = [
                'license_key' => $new_license_key,
                'product_name' => $product->get_name(),
            ];
        }
    }

    // Save the order after updating
    $order->save();

    // Send success response with license information
    if (!empty($licenses)) {
        wp_send_json_success([
            'message' => __('License created successfully', 'slm-plus'),
            'licenses' => $licenses,
        ]);
    } else {
        wp_send_json_error(['message' => __('No licenses were generated', 'slm-plus')]);
    }
}


add_action('wp_ajax_check_order_user_info', 'check_order_user_info_callback');
function check_order_user_info_callback(){
    check_ajax_referer('slm_generate_license_nonce', 'security');

    $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : null;
    if (!$order_id) {
        wp_send_json_error(['message' => __('Invalid order ID', 'slm-plus')]);
    }

    $order = wc_get_order($order_id);
    if ($order) {
        $last_name = $order->get_billing_last_name();
        $email = $order->get_billing_email();
        wp_send_json_success(['last_name' => $last_name, 'email' => $email]);
    }
    else {
        wp_send_json_error(['message' => __('Order not found', 'slm-plus')]);
    }
}
