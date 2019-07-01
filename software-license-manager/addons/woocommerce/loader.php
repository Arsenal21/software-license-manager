<?php


//ini_set( 'error_log', plugin_dir_path(__FILE__).'debug.log' );

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('write_log')) {
    function write_log ( $log )  {
        if ( true === WP_DEBUG ) {
            if ( is_array( $log ) || is_object( $log ) ) {
                error_log( print_r( $log, true ) );
            } else {
                error_log( $log );
            }
        }
    }
}

if (!class_exists('WC_SLM')) {

	class WC_SLM {
		private static $instance;
		public static function instance() {
			if (!self::$instance) {
				self::$instance = new WC_SLM();
				self::$instance->setup_constants();
				self::$instance->includes();
				self::$instance->load_textdomain();
			}

			return self::$instance;
		}
		private function setup_constants() {

			// Plugin version
			define('WC_SLM_VER', '1.0.8');

			// Plugin path
			define('WC_SLM_DIR', plugin_dir_path(__FILE__));
			// Plugin URL
			define('WC_SLM_URL', plugin_dir_url(__FILE__));
			// SLM Credentials
			$api_url 				= str_replace(array('http://'), array('https://'), rtrim(get_option('wc_slm_api_url'), '/'));

			// get api settinsg from options table
			$slm_settings 			= get_option('slm_plugin_options');
			$lic_creation_secret 	= $slm_settings['lic_creation_secret'];
			$lic_key_prefix 		= $slm_settings['lic_prefix'];

			define('WC_SLM_API_URL', 	$api_url);
			define('WC_SLM_API_SECRET', get_option('wc_slm_api_secret'));
			define('KEY_API',  			$lic_creation_secret);
			define('KEY_API_PREFIX',  	$lic_key_prefix);
// write_log(KEY_API);
// write_log(KEY_API_PREFIX);
		}

		private function includes() {

			// Get out if WC is not active
			if (!function_exists('WC')) {
				return;
			}

			// log files and scripts
			require_once WC_SLM_DIR . 'includes/helper.php';

			if (is_admin()) {
				require_once WC_SLM_DIR . 'includes/meta-boxes.php';
				require_once WC_SLM_DIR . 'includes/settings.php';
			}

			// purchases and emails
			require_once WC_SLM_DIR . 'includes/emails.php';
			require_once WC_SLM_DIR . 'includes/purchase.php';
		}

		public function load_textdomain() {

		}

		public static function activation() {
			// nothing
		}

		public static function uninstall() {
			// nothing
		}
	}

	function WC_SLM_load() {

		return WC_SLM::instance();
	}

	register_activation_hook(__FILE__, array('WC_SLM', 'activation'));
	register_uninstall_hook(__FILE__, array('WC_SLM', 'uninstall'));
	add_action('plugins_loaded', 'WC_SLM_load');
}