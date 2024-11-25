<?php

/**
 * Handles SLM License Templates and WooCommerce Overrides
 * 
 * @package SLM_Plus
 */

// Action to load SLM license template on single product pages
add_action('woocommerce_single_product_summary', 'slm_license_template', 60);

function slm_license_template() {
    global $product;

    // Ensure $product is a valid WooCommerce product object
    if (!$product || !is_a($product, 'WC_Product')) {
        return;
    }

    // Get the product type with backward compatibility handling
    $product_type = method_exists($product, 'get_type') ? $product->get_type() : $product->product_type;

    // Check if the product type is 'slm_license'
    if ($product_type === 'slm_license') {
        $template_path = SLM_WOO;

        // Detect if the request includes a renew_license_key
        $is_renewal = isset($_GET['renew_license_key']) && !empty($_GET['renew_license_key']);
        $renew_license_key = $is_renewal ? sanitize_text_field($_GET['renew_license_key']) : '';

        // Pass renewal status and key as variables to the template
        wc_get_template(
            'single-product/add-to-cart/slm_license.php',
            array(
                'is_renewal' => $is_renewal,
                'renew_license_key' => $renew_license_key,
            ),
            '',
            trailingslashit($template_path)
        );
    }
}



/**
 * Override default WooCommerce templates and template parts from plugin.
 * 
 * E.g.
 * Override template 'woocommerce/loop/result-count.php' with 'my-plugin/woocommerce/loop/result-count.php'.
 * Override template part 'woocommerce/content-product.php' with 'my-plugin/woocommerce/content-product.php'.
 *
 * Note: We used folder name 'woocommerce' in plugin to override all woocommerce templates and template parts.
 * You can change it as per your requirement.
 */
// Override Template Part's.
add_filter( 'wc_get_template_part',             'slm_override_woocommerce_template_part', 10, 3 );
// Override Template's.
add_filter( 'woocommerce_locate_template',      'slm_override_woocommerce_template', 10, 3 );
/**
 * Template Part's
 *
 * @param  string $template Default template file path.
 * @param  string $slug     Template file slug.
 * @param  string $name     Template file name.
 * @return string           Return the template part from plugin.
 */
function slm_override_woocommerce_template_part( $template, $slug, $name ) {
    // UNCOMMENT FOR @DEBUGGING
    // echo '<pre>';
    // echo 'template: ' . $template . '<br/>';
    // echo 'slug: ' . $slug . '<br/>';
    // echo 'name: ' . $name . '<br/>';
    // echo '</pre>';
    // Template directory.
    // E.g. /wp-content/plugins/my-plugin/woocommerce/
    $template_directory = untrailingslashit( plugin_dir_path( __FILE__ ) ) . 'woocommerce/';
    if ( $name ) {
        $path = $template_directory . "{$slug}-{$name}.php";
    } else {
        $path = $template_directory . "{$slug}.php";
    }
    return file_exists( $path ) ? $path : $template;
}
/**
 * Template File
 *
 * @param  string $template      Default template file  path.
 * @param  string $template_name Template file name.
 * @param  string $template_path Template file directory file path.
 * @return string                Return the template file from plugin.
 */
function slm_override_woocommerce_template( $template, $template_name, $template_path ) {
    // UNCOMMENT FOR @DEBUGGING
    // echo '<pre>';
    // echo 'template: ' . $template . '<br/>';
    // echo 'template_name: ' . $template_name . '<br/>';
    // echo 'template_path: ' . $template_path . '<br/>';
    // echo '</pre>';
    // Template directory.
    // E.g. /wp-content/plugins/my-plugin/woocommerce/
    $template_directory = untrailingslashit( plugin_dir_path( __FILE__ ) ) . 'woocommerce/';
    $path = $template_directory . $template_name;
    return file_exists( $path ) ? $path : $template;
}



// Load template for the specific page
add_filter('page_template', 'slm_load_license_cart_template');
function slm_load_license_cart_template($page_template) {
    if (get_page_template_slug() == 'page-license-cart.php') {
        $page_template = SLM_TEMPLATES . 'page-license-cart.php';
    }
    return $page_template;
}

/**
 * Add "License Cart" template to the Page Attributes template dropdown.
 */
add_filter('theme_page_templates', 'slm_add_license_cart_template', 10, 4);
function slm_add_license_cart_template($post_templates, $wp_theme, $post, $post_type) {
    // Add the custom template to the dropdown
    $post_templates['page-license-cart.php'] = __('License Cart', 'slm-plus');
    return $post_templates;
}
