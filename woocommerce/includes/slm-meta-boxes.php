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
add_filter( 'woocommerce_product_class', 'slm_register_product_class', 10, 2 );
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
                    jQuery('._license_renewal_period_field').hide();
                    jQuery('#_license_renewal_period').val('onetime');
                    jQuery('#_license_renewal_period').prop("disabled", true);
                    jQuery('._license_renewal_period_term_field').hide();
                } else {
                    jQuery('._license_renewal_period_field').show();
                    jQuery('._license_renewal_period_term_field').show();
                    jQuery('#_license_renewal_period').val('');
                    jQuery('#_license_renewal_period').prop("disabled", false);
                }
            });
        });
    </script>
    <?php
            }
            add_action('admin_head', 'wcpp_custom_style');
            function wc_slm_add_tab($wc_slm_data_tabs){
                $wc_slm_data_tabs['wc_slm_data_tab'] = array(
                    'label'     => __('Licensing', 'softwarelicensemanager'),
                    'target'    => 'wc_slm_meta',
                    'class'     => array('show_if_slm_license', 'show_if_wc_slm_data_tab_enabled'),
                );
                return $wc_slm_data_tabs;
            }

            function wc_slm_data_panel(){
                global $post;
                $product_id=get_the_ID();
                $slm_options = get_option('slm_plugin_options');
    ?>
    <div id='wc_slm_meta' class='panel woocommerce_options_panel'>
        <?php ?>
        <div class='options_group'>
            <?php
                $value = get_post_meta($product_id, '_domain_licenses',true);
                if($value === ''){
                    $value =  SLM_Helper_Class::slm_get_option('default_max_domains');
                }
                woocommerce_wp_text_input(
                    array(
                        'id'            => '_domain_licenses',
                        'label'         => __('Domain Licenses', 'softwarelicensemanager'),
                        'placeholder'   => SLM_Helper_Class::slm_get_option('default_max_domains'),
                        'desc_tip'      => 'true',
                        'value'         => $value,
                        'type'          => 'number',
                        'custom_attributes' => array(
 					        'step' 	=> 'any',
 					        'min'	=> 0,
                        ),
                        'description'   => __('Enter the allowed amount of domains this license can have (websites).', 'softwarelicensemanager')
                    )
                );
                $value = get_post_meta($product_id, '_devices_licenses',true);
                if($value === ''){
                    $value =  SLM_Helper_Class::slm_get_option('default_max_devices');
                }
                woocommerce_wp_text_input(
                    array(
                        'id'            => '_devices_licenses',
                        'label'         => __('Devices Licenses', 'softwarelicensemanager'),
                        'placeholder'   => SLM_Helper_Class::slm_get_option('default_max_devices'),
                        'desc_tip'      => 'true',
                        'value'         => $value,
                        'type'          => 'number',
                        'custom_attributes' => array(
 					        'step' 	=> 'any',
 					        'min'	=> 0,
                        ),
                        'description'   => __('Enter the allowed amount of devices this license can have (computers, mobile, etc).', 'softwarelicensemanager')
                    )
                );
                if ($slm_options['slm_multiple_items']==1){
                    woocommerce_wp_text_input(
                        array(
                            'id'            => '_license_item_reference',
                            'label'         => __('Item reference', 'softwarelicensemanager'),
                            'placeholder'   => "Software's item reference",
                            'desc_tip'      => 'true',
                            'description' => __('Enter the item reference of your application, theme, or plug-in. The licence will be then bound to this exact software.', 'softwarelicensemanager')
                        )
                    );
                }
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

                echo '<hr>';
                woocommerce_wp_text_input(
                    array(
                        'id'            => '_license_renewal_period',
                        'label'         => __('Renewal period lenght ', 'softwarelicensemanager'),
                        'placeholder'   => '0',
                        'description'   => __('Amount of days or months or years', 'softwarelicensemanager'),
                    )
                );

                woocommerce_wp_select(
                    array(
                        'id'            => '_license_renewal_period_term',
                        'label'         => __('Expiration term', 'softwarelicensemanager'),
                        'placeholder'   => 'days',
                        'description'   => __('Choose between days or months or years', 'softwarelicensemanager'),
                        'options'       => array(
                            'days'      => __('Day(s)', 'softwarelicensemanager'),
                            'months'    => __('Month(s)', 'softwarelicensemanager'),
                            'years'     => __('Year(s)', 'softwarelicensemanager'),
                            'onetime'   => __('One Time', 'softwarelicensemanager'),
                        )
                    )
                );
                echo '<div class="clear"><hr></div>';

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
                update_post_meta($post_id, '_domain_licenses', esc_attr($_domain_licenses));

                $is_wc_slm_data_tab_enabled = isset($_POST['_wc_slm_data_tab_enabled']) ? 'yes' : 'no';
                update_post_meta($post_id, '_wc_slm_data_tab_enabled', $is_wc_slm_data_tab_enabled);

                $_license_item_reference = $_POST['_license_item_reference'];
                if (!empty($_license_item_reference)) {
                    update_post_meta($post_id, '_license_item_reference', esc_attr($_license_item_reference));
                }
                else {
                    update_post_meta($post_id, '_license_item_reference', esc_attr('default'));
                }

                $_license_type = $_POST['_license_type'];
                if (!empty($_license_type)) {
                    update_post_meta($post_id, '_license_type', esc_attr($_license_type));
                }

                $_devices_licenses = $_POST['_devices_licenses'];
                update_post_meta($post_id, '_devices_licenses', esc_attr($_devices_licenses));

                $_license_renewal_period = $_POST['_license_renewal_period'];
                if (!empty($_license_renewal_period)  && $_license_type == 'lifetime') {
                    update_post_meta($post_id, '_license_renewal_period', esc_attr('0'));
                }
                else {
                    update_post_meta($post_id, '_license_renewal_period', esc_attr($_license_renewal_period));
                }

                $_license_renewal_period_term = $_POST['_license_renewal_period_term'];
                if (!empty($_license_renewal_period_term) && $_license_type == 'lifetime') {
                    update_post_meta($post_id, '_license_renewal_period_term', esc_attr('onetime'));
                }
                else {
                    update_post_meta($post_id, '_license_renewal_period_term', esc_attr($_license_renewal_period_term));
                }

                $_license_current_version = $_POST['_license_current_version'];
                update_post_meta($post_id, '_license_current_version', esc_attr($_license_current_version));

                $_license_until_version = $_POST['_license_until_version'];
                update_post_meta($post_id, '_license_until_version', esc_attr($_license_until_version));
            }

            function slm_register_product_type(){
                class WC_Product_SLM_License extends WC_Product{
                    public function __construct($product){
                        $this->product_type = 'slm_license';
                        parent::__construct($product);
                    }
                }
            }
 
            function slm_register_product_class( $classname, $product_type ) {
                if ( $product_type == 'slm_license' ) { 
                    $classname = 'WC_Product_SLM_License';
                }
                return $classname;
            }

            function slm_add_product_type($types){
                $types['slm_license'] = __('License product', 'softwarelicensemanager');
                return $types;
            }
            function slm_license_admin_custom_js(){
                if ('product' != get_post_type()) :
                    return;
                endif;
                $slm_options = get_option('slm_plugin_options');
                $affect_downloads = $slm_options['slm_woo_affect_downloads']==1 ? true : false; 
                ?>
    <script type='text/javascript'>
        jQuery(document).ready(function() {
            //for Price tab
            $lic_type = jQuery("#_license_type").val();

            if ($lic_type == 'lifetime') {
                console.log('yes lifetime');
                jQuery('._license_renewal_period_field').hide();
                jQuery('._license_renewal_period_term_field').hide();
                <?php
                if($affect_downloads == true):
                ?>
                jQuery('#_download_limit').val('');
                jQuery('#_download_expiry').val('');
                <?php
                endif;
                ?>
            }
            else {
                console.log('no - is subscription based');
                jQuery('._license_renewal_period_field').show();
                jQuery('._license_renewal_period_term_field').show();
            }

            <?php
            if($affect_downloads == true):
            ?>
            jQuery('#_download_limit').on('change', function() {
                if (jQuery('#_license_type').find(":selected").val() == 'lifetime'){
                    jQuery(this).val('');
                }
            });
            jQuery('#_download_expiry').on('change', function() {
                if (jQuery('#_license_type').find(":selected").val() == 'lifetime'){
                    jQuery(this).val('');
                }
            });
            <?php
            endif;
            ?>
            jQuery('#_license_type').on('change', function() {
                if (jQuery(this).find(":selected").val() == 'lifetime') {
                    <?php
                    if($affect_downloads == true):
                    ?>
                    jQuery('#_download_expiry').val('');
                    jQuery('#_download_limit').val('');
                    <?php
                    endif;
                    ?>
                    jQuery('._license_renewal_period_field').hide();
                    jQuery('._license_renewal_period_term_field').hide();
                }
            });
            jQuery('.product_data_tabs .general_tab').addClass('show_if_slm_license').show();
            //options_group show_if_downloadable hidden
            //jQuery('.options_group').addClass('show_if_slm_license').show();
            jQuery("label[for='_virtual']").addClass('show_if_slm_license').show();
            jQuery("label[for='_downloadable']").addClass('show_if_slm_license').show();
            jQuery(".show_if_external").addClass('hide_if_slm_license').hide();
            jQuery('#general_product_data .pricing').addClass('show_if_slm_license slm-display').show();
            jQuery("#_virtual").prop("checked", true);
            jQuery("#_downloadable").prop("checked", true);
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
