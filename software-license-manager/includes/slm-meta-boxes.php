<?php

// Author: Michel Velis
// Author URI: http://pilotkit.co
// Since: 3.0.0

// Add Variation Custom fields

//Display Fields in admin on product edit screen
add_action( 'woocommerce_product_after_variable_attributes', 'woo_variable_fields', 10, 3 );

//Save variation fields values
add_action( 'woocommerce_save_product_variation', 'save_variation_fields', 10, 2 );

// Create new fields for variations
function woo_variable_fields( $loop, $variation_data, $variation ) {

  echo '<div class="variation-custom-fields">';

      // License Field
      woocommerce_wp_text_input(
        array(
          'id'          => 'amount_of_licenses['. $loop .']',
          'label'       => __( 'Number of Licenses (domain)', 'woocommerce' ),
          'placeholder' => '1-20',
          'desc_tip'    => true,
          'wrapper_class' => 'form-row form-row-first',
          'description' => __( 'Ideal for themes, plugins, and websites', 'woocommerce' ),
          'value'       => get_post_meta($variation->ID, 'amount_of_licenses', true)
        )
      );

  echo "</div>";

  echo '<div class="variation-custom-fields">';

      // License Field
      woocommerce_wp_text_input(
        array(
          'id'          => 'amount_of_licenses_devices['. $loop .']',
          'label'       => __( 'Number of Licenses (devices)', 'woocommerce' ),
          'placeholder' => '1-20',
          'desc_tip'    => true,
          'wrapper_class' => 'form-row form-row-first',
          'description' => __( 'Ideal for software and apps.', 'woocommerce' ),
          'value'       => get_post_meta($variation->ID, 'amount_of_licenses_devices', true)
        )
      );

  echo "</div>";

}

/** Save new fields for variations */
function save_variation_fields( $variation_id, $i) {

    // License Field
    $text_field = stripslashes( $_POST['amount_of_licenses'][$i] );
    update_post_meta( $variation_id, 'amount_of_licenses', esc_attr( $text_field ) );

    $text_field = stripslashes( $_POST['amount_of_licenses_devices'][$i] );
    update_post_meta( $variation_id, 'amount_of_licenses_devices', esc_attr( $text_field ) );

}