<?php

add_action('woocommerce_single_product_summary', 'slm_license_template', 60);

function slm_license_template()
{
    global $product;

    // Ensure $product is a valid WooCommerce product object
    if (!$product || !is_a($product, 'WC_Product')) {
        return;
    }

    // Get the product type with backward compatibility handling
    $product_type = '';
    if (method_exists($product, 'get_type')) {
        $product_type = $product->get_type();
    } else {
        $product_type = $product->product_type; // For older versions
    }

    // Check if the product type is 'slm_license'
    if ($product_type === 'slm_license') {
        $template_path = SLM_WOO . 'templates/';

        // Ensure the template path exists, if not fall back to plugin's default template directory
        if (!file_exists($template_path . 'single-product/add-to-cart/slm_license.php')) {
            // If template is not found, provide a fallback to default WooCommerce template directory or a custom one
            $template_path = plugin_dir_path(__FILE__) . 'templates/';
        }

        // Load the template
        wc_get_template(
            'single-product/add-to-cart/slm_license.php',
            array(),
            '',
            trailingslashit($template_path)
        );
    }
}
