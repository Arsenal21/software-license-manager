<?php
/**
 * Helper
 *
 * @since       1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

function wc_slm_print_pretty($args) {
	echo '<pre>';
	print_r($args);
	echo '</pre>';
}

function wc_slm_log($msg) {
	$log = ABSPATH . DIRECTORY_SEPARATOR . 'slm_log.txt';
	file_put_contents($log, $msg . '
', FILE_APPEND);
}
