<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

// Display Fields
add_action('woocommerce_product_options_general_product_data', 'slm_custom_general_fields');

// Save Fields
add_action('woocommerce_process_product_meta', 'slm_custom_general_fields_save');

function slm_custom_general_fields() {
	global $woocommerce, $post;

	$post_id = $post->ID;
	$slm_licensing_enabled = get_post_meta($post_id, '_slm_licensing_enabled', true) ? true : false;
	$slm_sites_allowed = esc_attr(get_post_meta($post_id, 'amount_of_licenses', true));
    $slm_devices_allowed = esc_attr(get_post_meta($post_id, 'slm_devices_allowed', true));
	$_slm_licensing_renewal_period = esc_attr(get_post_meta($post_id, '_slm_licensing_renewal_period', true));
	$slm_display = $slm_licensing_enabled ? '' : ' style="display:none;"';


	if (trim($_slm_licensing_renewal_period) == '') {
		$_slm_licensing_renewal_period = 0;
	}

	?>

    <script type="text/javascript">jQuery( document ).ready( function($) {
            $( "#_slm_licensing_enabled" ).on( "click",function() {
                // TODO: Improve toggle handling and prevent double display
                $( ".wc-slm-variable-toggled-hide" ).toggle();
                $( ".wc-slm-toggled-hide" ).toggle();
            })
        });
    </script>

    <p class="form-field">
        <input type="checkbox" name="_slm_licensing_enabled" id="_slm_licensing_enabled" value="1" <?php echo checked(true, $slm_licensing_enabled, false); ?> />
        <label for="_slm_licensing_enabled"><?php _e('Enable licensing for this download.', 'wc-slm');?></label>
    </p>

    <div <?php echo $slm_display; ?> class="wc-slm-toggled-hide">
		<p class="form-field">
			<label for="_slm_licensing_renewal_period"><?php _e('license renewal period(yearly) , enter 0 for lifetime.', 'wc-slm');?></label>
			<input type="number" name="_slm_licensing_renewal_period" id="_slm_licensing_renewal_period" value="<?php echo $_slm_licensing_renewal_period; ?>"  />
		</p>
        <p class="form-field">
            <label for="amount_of_licenses"><?php _e('Number of Licenses (domain)', 'wc-slm');?></label>
            <input type="number" name="amount_of_licenses" class="small-text" value="<?php echo $slm_sites_allowed; ?>" />
        </p>

        <p class="form-field">
            <label for="slm_devices_allowed"><?php _e('Number of Licenses (devices)', 'wc-slm');?></label>
            <input type="number" name="slm_devices_allowed" class="small-text" value="<?php echo $slm_sites_allowed; ?>" />
        </p>

    </div>
    <?php

}
function slm_custom_general_fields_save($post_id) {
	// Textarea
	$wc_slm_licensing_enabled           = $_POST['_slm_licensing_enabled'];
	$wc_amount_of_licenses              = $_POST['amount_of_licenses'];
    $wc_slm_devices_allowed             = $_POST['slm_devices_allowed'];
	$_slm_licensing_renewal_period      = $_POST['_slm_licensing_renewal_period'];

	if (!empty($wc_slm_licensing_enabled)) {
		update_post_meta($post_id, '_slm_licensing_enabled', esc_html($wc_slm_licensing_enabled));
	}

	if (!empty($wc_amount_of_licenses)) {
		update_post_meta($post_id, 'amount_of_licenses', esc_html($wc_amount_of_licenses));
	}

    if (!empty($wc_slm_devices_allowed)) {
        update_post_meta($post_id, 'slm_devices_allowed', esc_html($woocommerce_slm_devices_allowed));
    }

	if (!empty($_slm_licensing_renewal_period)) {
		update_post_meta($post_id, '_slm_licensing_renewal_period', esc_html($_slm_licensing_renewal_period));
	}
}