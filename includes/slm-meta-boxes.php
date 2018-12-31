<?php

// Author: Michel Velis
// Author URI: http://pilotkit.co
// Since: 4.4.0
// from: https://gist.github.com/JeroenSormani/6b710d079386d096f932

add_filter('woocommerce_product_data_tabs', 'wc_slm_add_tab' );
add_action('woocommerce_process_product_meta_simple', 'wc_slm_save_data');
add_action('woocommerce_product_data_panels', 'wc_slm_data_panel');
/**
 * Add 'License' product option
 */
function add_wc_slm_data_tab_enabled_product_option( $product_type_options ) {
    $product_type_options['wc_slm_data_tab_enabled'] = array(
        'id'            => '_wc_slm_data_tab_enabled',
        'wrapper_class' => 'show_if_simple show_if_variable',
        'label'         => __( 'License Manager', 'woocommerce' ),
        'default'       => 'no',
        'description'   => __( 'Enables the license creation api.', 'woocommerce' )
    );
    return $product_type_options;
}
add_filter( 'product_type_options', 'add_wc_slm_data_tab_enabled_product_option' );


/** CSS To Add Custom tab Icon */
function wcpp_custom_style() {
    ?><style>
       #woocommerce-product-data ul.wc-tabs li.wc_slm_data_tab_options a:before { font-family: Dashicons; content: "\f160"; }
    </style>

    <script>
        jQuery( document ).ready( function( $ ) {
            $( 'input#_wc_slm_data_tab_enabled' ).change( function() {
                var is_wc_slm_data_tab_enabled = $( 'input#_wc_slm_data_tab_enabled:checked' ).size();
                // console.log( is_wc_slm_data_tab_enabled );
                $( '.show_if_wc_slm_data_tab_enabled' ).hide();
                $( '.hide_if_wc_slm_data_tab_enabled' ).hide();
                if ( is_wc_slm_data_tab_enabled ) {
                    $( '.hide_if_wc_slm_data_tab_enabled' ).hide();
                }
                if ( is_wc_slm_data_tab_enabled ) {
                    $( '.show_if_wc_slm_data_tab_enabled' ).show();
                }
            });
            $( 'input#_wc_slm_data_tab_enabled' ).trigger( 'change' );
        });
    </script><?php
}
add_action( 'admin_head', 'wcpp_custom_style' );

function wc_slm_add_tab( $wc_slm_data_tabs ) {
    $wc_slm_data_tabs['wc_slm_data_tab'] = array(
        'label'     => __('Licensing', 'woocommerce' ),
        'target'    => 'wc_slm_meta',
        'class'     => array( 'show_if_simple', 'show_if_wc_slm_data_tab_enabled'),
    );

    return $wc_slm_data_tabs;
}

function wc_slm_data_panel() {
    global $post;
    ?>
    <div id='wc_slm_meta' class='panel woocommerce_options_panel' >

    <?php ?>
    <div class='options_group' >
    <?php
    woocommerce_wp_text_input(
        array(
            'id'            => '_domain_licenses',
            'label'         => __( 'Domain Licenses', 'woocommerce' ),
            'placeholder'   => '0',
            'desc_tip'      => 'true',
            'type'          => 'number',
            'description'   => __( 'Enter the allowed amount of domains this license can have (websites).', 'woocommerce' )
        )
    );
    woocommerce_wp_text_input(
        array(
            'id'            => '_devices_licenses',
            'label'         => __( 'Devices Licenses', 'woocommerce' ),
            'placeholder'   => '0',
            'desc_tip'      => 'true',
            'type'          => 'number',
            'description'   => __( 'Enter the allowed amount of devices this license can have (computers, mobile, etc).', 'woocommerce' )
        )
    );
    woocommerce_wp_text_input(
        array(
            'id'            => '_license_renewal_period',
            'label'         => __( 'Renewal period ', 'woocommerce' ),
            'placeholder'   => '0',
            'desc_tip'      => 'true',
            'description'   => __( 'License renewal period(yearly) , enter 0 for lifetime.', 'woocommerce' )
        )
    );
    woocommerce_wp_select(
        array(
            'id'            => '_license_type',
            'label'         => __( 'License Type', 'woocommerce' ),
            'placeholder'   => 'Select one',
            'desc_tip'      => 'true',
            'description'   => __( 'type of license: subscription base or lifetime', 'woocommerce' ),
            'options'       => array(
                'none'      => __( 'Select one', 'woocommerce' ),
                'subscription'   => __( 'subscription', 'woocommerce' ),
                'lifetime'       => __( 'lifetime', 'woocommerce' ),
            )
        )
    );
    woocommerce_wp_text_input(
        array(
            'id'            => '_license_current_version',
            'label'         => __( 'Current Version', 'woocommerce' ),
            'placeholder'   => '0.0.0',
            'desc_tip'      => 'true',
            'description' => __( 'Enter the current version of your application, theme, or plug-in', 'woocommerce' )
        )
    );
    ?>
    </div>
    </div><?php
}

/** Hook callback function to save custom fields information */
function wc_slm_save_data($post_id) {
    $_domain_licenses = $_POST['_domain_licenses'];
    if (!empty($_domain_licenses)) {
        update_post_meta($post_id, '_domain_licenses', esc_attr($_domain_licenses));
    }

    $is_wc_slm_data_tab_enabled = isset( $_POST['_wc_slm_data_tab_enabled'] ) ? 'yes' : 'no';
    update_post_meta( $post_id, '_wc_slm_data_tab_enabled', $is_wc_slm_data_tab_enabled );

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
}