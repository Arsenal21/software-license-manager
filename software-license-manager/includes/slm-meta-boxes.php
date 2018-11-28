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






























// First Register the Tab by hooking into the 'woocommerce_product_data_tabs' filter
add_filter( 'woocommerce_product_data_tabs', 'add_my_custom_product_data_tab' );
function add_my_custom_product_data_tab( $product_data_tabs ) {
    $product_data_tabs['my-custom-tab'] = array(
        'label' => __( 'License', 'woocommerce' ),
        'target' => 'my_custom_product_data',
        'class' => array( 'show_if_simple', 'show_if_variable' ),
    );
    return $product_data_tabs;
}





// functions you can call to output text boxes, select boxes, etc.
add_action('woocommerce_product_data_panels', 'woocom_custom_product_data_fields');

function woocom_custom_product_data_fields() {
    global $post;

    // Note the 'id' attribute needs to match the 'target' parameter set above
    ?> <div id = 'my_custom_product_data'
    class = 'panel woocommerce_options_panel' > <?php
        ?> <div class = 'options_group' > <?php
              // Text Field
  woocommerce_wp_text_input(
    array(
      'id' => '_text_field',
      'label' => __( 'Custom Text Field', 'woocommerce' ),
      'wrapper_class' => 'show_if_simple', //show_if_simple or show_if_variable
      'placeholder' => 'Custom text field',
      'desc_tip' => 'true',
      'description' => __( 'Enter the custom value here.', 'woocommerce' )
    )
  );

  // Number Field
  woocommerce_wp_text_input(
    array(
      'id' => '_number_field',
      'label' => __( 'Custom Number Field', 'woocommerce' ),
      'placeholder' => '',
      'description' => __( 'Enter the custom value here.', 'woocommerce' ),
      'type' => 'number',
      'custom_attributes' => array(
         'step' => 'any',
         'min' => '15'
      )
    )
  );

  // Checkbox
  woocommerce_wp_checkbox(
    array(
      'id' => '_checkbox',
      'label' => __('Custom Checkbox Field', 'woocommerce' ),
      'description' => __( 'Check me!', 'woocommerce' )
    )
  );

  // Select
  woocommerce_wp_select(
    array(
      'id' => '_select',
      'label' => __( 'Custom Select Field', 'woocommerce' ),
      'options' => array(
         'one' => __( 'Custom Option 1', 'woocommerce' ),
         'two' => __( 'Custom Option 2', 'woocommerce' ),
        'three' => __( 'Custom Option 3', 'woocommerce' )
      )
    )
  );

  // Textarea
  woocommerce_wp_textarea_input(
     array(
       'id' => '_textarea',
       'label' => __( 'Custom Textarea', 'woocommerce' ),
       'placeholder' => '',
       'description' => __( 'Enter the value here.', 'woocommerce' )
     )
 );
        ?> </div>

    </div><?php
}

/** Hook callback function to save custom fields information */
function woocom_save_proddata_custom_fields($post_id) {
    // Save Text Field
    $text_field = $_POST['_text_field'];
    if (!empty($text_field)) {
        update_post_meta($post_id, '_text_field', esc_attr($text_field));
    }

    // Save Number Field
    $number_field = $_POST['_number_field'];
    if (!empty($number_field)) {
        update_post_meta($post_id, '_number_field', esc_attr($number_field));
    }
    // Save Textarea
    $textarea = $_POST['_textarea'];
    if (!empty($textarea)) {
        update_post_meta($post_id, '_textarea', esc_html($textarea));
    }

    // Save Select
    $select = $_POST['_select'];
    if (!empty($select)) {
        update_post_meta($post_id, '_select', esc_attr($select));
    }

    // Save Checkbox
    $checkbox = isset($_POST['_checkbox']) ? 'yes' : 'no';
    update_post_meta($post_id, '_checkbox', $checkbox);

    // Save Hidden field
    $hidden = $_POST['_hidden_field'];
    if (!empty($hidden)) {
        update_post_meta($post_id, '_hidden_field', esc_attr($hidden));
    }
}

add_action( 'woocommerce_process_product_meta_simple', 'woocom_save_proddata_custom_fields'  );

// You can uncomment the following line if you wish to use those fields for "Variable Product Type"
//add_action( 'woocommerce_process_product_meta_variable', 'woocom_save_proddata_custom_fields'  );