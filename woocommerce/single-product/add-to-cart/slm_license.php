<?php
/**
 * Simple product add to cart
 * Template for handling SLM licenses (renewals and new).
 */

defined('ABSPATH') || exit;

global $product;

if (!$product->is_purchasable()) {
    return;
}

echo wc_get_stock_html($product); // Display stock status if applicable.

if ($product->is_in_stock()) :
    // Determine if this is a renewal (renew_license_key present in URL).
    $is_renewal = isset($_GET['renew_license_key']) && !empty($_GET['renew_license_key']);
    $renew_license_key = $is_renewal ? sanitize_text_field($_GET['renew_license_key']) : '';
    ?>

    <?php do_action('woocommerce_before_add_to_cart_form'); ?>

    <form class="cart" action="<?php echo esc_url(apply_filters('woocommerce_add_to_cart_form_action', $product->get_permalink())); ?>" method="post" enctype='multipart/form-data'>
        <?php do_action('woocommerce_before_add_to_cart_button'); ?>

        <div class="license-product-details">
            <?php if ($is_renewal) : ?>
                <!-- Display renewal information -->
                <p class="license-renewal-info">
                    <?php echo esc_html__('You are renewing the license for:', 'slm-plus'); ?>
                    <strong><?php echo esc_html($renew_license_key); ?></strong>
                </p>
                <!-- Add hidden field for renew_license_key -->
                <input type="hidden" name="renew_license_key" value="<?php echo esc_attr($renew_license_key); ?>">
                <input type="hidden" name="quantity" value="1">
            <?php else : ?>
                <!-- Display quantity input for new licenses -->
                <?php
                do_action('woocommerce_before_add_to_cart_quantity');
                woocommerce_quantity_input(
                    array(
                        'min_value'   => apply_filters('woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product),
                        'max_value'   => apply_filters('woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product),
                        'input_value' => isset($_POST['quantity']) ? wc_stock_amount(wp_unslash($_POST['quantity'])) : $product->get_min_purchase_quantity(), // CSRF ok.
                    )
                );
                do_action('woocommerce_after_add_to_cart_quantity');
                ?>
            <?php endif; ?>
        </div>

        <button type="submit" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>" class="single_add_to_cart_button button alt<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>">
            <?php echo esc_html($is_renewal ? __('Renew License', 'slm-plus') : $product->single_add_to_cart_text()); ?>
        </button>

        <?php do_action('woocommerce_after_add_to_cart_button'); ?>
    </form>

    <?php do_action('woocommerce_after_add_to_cart_form'); ?>

<?php endif; ?>
