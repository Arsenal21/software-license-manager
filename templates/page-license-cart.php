<?php
/**
 * Template Name: License Cart
 * Template Post Type: page
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include WordPress header
get_header();

/**
 * Retrieve and set the renewal license key.
 *
 * @return string|null The renewal license key, if available.
 */
function get_renew_license_key() {
    // Check if the license key is passed via the URL
    if (isset($_GET['renew_license_key']) && !empty($_GET['renew_license_key'])) {
        $renew_license_key = sanitize_text_field($_GET['renew_license_key']);
        if (class_exists('WooCommerce') && WC()->session) {
            WC()->session->set('renew_license_key', $renew_license_key);
        }
        SLM_Helper_Class::write_log("Renewal license key set from URL: {$renew_license_key}");
        return $renew_license_key;
    }

    // Retrieve license key from session
    if (class_exists('WooCommerce') && WC()->session) {
        $renew_license_key = WC()->session->get('renew_license_key');
        if (!empty($renew_license_key)) {
            SLM_Helper_Class::write_log("Renewal license key retrieved from session: {$renew_license_key}");
            return $renew_license_key;
        }
    }

    SLM_Helper_Class::write_log("No renewal license key found in URL or session.");
    return null;
}

// Determine if it's a renewal
$renew_license_key = get_renew_license_key();
$is_renewal = !empty($renew_license_key);

?>

<div class="license-checkout-container">
    <h1><?php esc_html_e('License Checkout', 'slm-plus'); ?></h1>

    <?php if (class_exists('WooCommerce') && WC()->cart && WC()->cart->is_empty()) : ?>
        <!-- Show empty cart message -->
        <div class="woocommerce-info">
            <?php echo esc_html__('Your cart is currently empty.', 'slm-plus'); ?>
        </div>

        <!-- Show license product suggestions -->
        <div class="license-product-suggestions">
            <h2><?php esc_html_e('Recommended License Products', 'slm-plus'); ?></h2>
            <ul class="products">
                <?php
                // Query for license products
                $args = array(
                    'post_type'      => 'product',
                    'posts_per_page' => 5, // Number of products to display
                    'tax_query'      => array(
                        array(
                            'taxonomy' => 'product_type',
                            'field'    => 'slug',
                            'terms'    => 'slm_license',
                        ),
                    ),
                );

                $license_products = new WP_Query($args);

                if ($license_products->have_posts()) :
                    while ($license_products->have_posts()) : $license_products->the_post();
                        global $product;

                        // Ensure the product is valid and contains license data
                        if ($product && $product->is_type('slm_license')) :
                            ?>
                            <li class="product">
                                <a href="<?php the_permalink(); ?>">
                                    <?php if (has_post_thumbnail()) : ?>
                                        <?php the_post_thumbnail('woocommerce_gallery_thumbnail'); // Use smaller WooCommerce thumbnail size ?>
                                    <?php endif; ?>
                                    <h3><?php the_title(); ?></h3>
                                    <span class="price"><?php echo $product->get_price_html(); ?></span>
                                </a>
                                <a href="<?php echo esc_url('?add-to-cart=' . $product->get_id()); ?>" class="button add-to-cart">
                                    <?php esc_html_e('Add to Cart', 'slm-plus'); ?>
                                </a>
                            </li>
                        <?php
                        endif;
                    endwhile;
                    wp_reset_postdata();
                else :
                    ?>
                    <p><?php esc_html_e('No license products available at the moment.', 'slm-plus'); ?></p>
                <?php endif; ?>
            </ul>


        </div>
    <?php else : ?>
        <?php if ($is_renewal) : ?>
            <!-- Show renewal message -->
            <div class="renewal-info woocommerce-info">
                <p>
                    <?php echo esc_html__('You are renewing the license key:', 'slm-plus'); ?>
                    <strong><?php echo esc_html($renew_license_key); ?></strong>
                </p>
                <p><?php echo esc_html__('Please proceed to complete your renewal.', 'slm-plus'); ?></p>
            </div>
        <?php else : ?>
            <!-- Show new license purchase message -->
            <div class="new-license-info woocommerce-info">
                <?php echo esc_html__('You are purchasing a new license.', 'slm-plus'); ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="woocommerce-cart">
        <?php echo do_shortcode('[woocommerce_checkout]'); ?>
    </div>
</div>

<?php
// Include WordPress footer
get_footer();
