<?php
/**
 * Settings
 *
 * @since       1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Create the section beneath the products tab
 **/
add_filter('woocommerce_get_sections_products', 'wc_slm_section');
function wc_slm_section($sections) {

	$sections['wc_slm'] = __('License Manager', 'wc-slm');
	return $sections;

}

/**
 * Add settings to the specific section we created before
 */
add_filter('woocommerce_get_settings_products', 'wc_slm_settings', 10, 2);
function wc_slm_settings($settings, $current_section) {
	/**
	 * Check the current section is what we want
	 **/
	if ($current_section == 'wc_slm') {
		$settings_slm = array();
		// Add Title to the Settings
		$settings_slm[] = array('name' => __('Software License Manager Settings', 'wc-slm'), 'type' => 'title', 'desc' => '', 'id' => 'wcslider');

		// API URL Option filed
		$settings_slm[] = array(
			'name' => __('API URL', 'wc-slm'),
			'desc_tip' => '',
			'id' => 'wc_slm_api_url',
			'type' => 'text',
			'desc' => 'Enter without http://',
		);

		// Secret Key
		$settings_slm[] = array(
			'name' => __('Secret Key', 'wc-slm'),
			'desc_tip' => '',
			'id' => 'wc_slm_api_secret',
			'type' => 'text',
			'desc' => '',
		);

		$settings_slm[] = array('type' => 'sectionend', 'id' => 'wcslider');
		return $settings_slm;

		/**
		 * If not, return the standard settings
		 **/
	} else {
		return $settings;
	}
}