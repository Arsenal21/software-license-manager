<?php

add_action('woocommerce_single_product_summary', 'slm_license_template', 60);
function slm_license_template()
{
    global $product;
    if ('slm_license' == $product->get_type()) {
        $template_path = SLM_WOO . 'templates/';
        // Load the template
        wc_get_template(
            'single-product/add-to-cart/slm_license.php',
            '',
            '',
            trailingslashit($template_path)
        );
    }
}