<?php

if (!class_exists('WOO_SLM')) {
	class WOO_SLM {
		private static $instance;
		public static function instance() {
			if (!self::$instance) {
				self::$instance = new WOO_SLM();
				self::$instance->includes();
			}
			return self::$instance;
		}

		private function includes() {
			require_once SLM_WOO . 'includes/class.php';
			require_once SLM_WOO . 'includes/purchase.php';
		}

	}
	return WOO_SLM::instance();
}