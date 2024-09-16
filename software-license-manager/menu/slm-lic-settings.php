<?php

function wp_lic_mgr_settings_menu() {

	echo '<div class="wrap">';
	echo '<h2>WP License Manager Settings v' . esc_html( WP_LICENSE_MANAGER_VERSION ) . '</h2>';
	echo '<div id="poststuff"><div id="post-body">';

	wp_lic_mgr_general_settings();

	echo '</div></div>';
	echo '</div>';
}

function wp_lic_mgr_general_settings() {

	if ( isset( $_REQUEST['slm_reset_log'] ) ) {
		check_admin_referer( 'slm_reset_debug_log', 'slm_reset_debug_log_nonce' );
		//$slm_logger = new SLM_Debug_Logger();
		global $slm_debug_logger;
		$slm_debug_logger->reset_log_file();
		$slm_debug_logger->reset_log_file( 'log-cron-job.txt' );
		echo '<div id="message" class="updated fade"><p>Debug log files have been reset!</p></div>';
	}

	if ( isset( $_POST['slm_save_settings'] ) ) {

		//Check nonce
		check_admin_referer( 'slm_settings_nonce_action', 'slm_settings_nonce_val' );

		$default_max_domains = filter_input( INPUT_POST, 'default_max_domains', FILTER_SANITIZE_NUMBER_INT );
		$default_max_domains = empty( $default_max_domains ) ? 1 : $default_max_domains;

        $lic_creation_secret = isset( $_POST['lic_creation_secret'] ) ? sanitize_text_field( stripslashes ( $_POST['lic_creation_secret'] ) ) : '';

        $lic_prefix = isset( $_POST['lic_prefix'] ) ? sanitize_text_field( stripslashes ( $_POST['lic_prefix'] ) ) : '';
		$lic_prefix = empty( $lic_prefix ) ? '' : SLM_Utility::sanitize_strip_trim_slm_text( $lic_prefix );

        $lic_verification_secret = isset( $_POST['lic_verification_secret'] ) ? sanitize_text_field( stripslashes ( $_POST['lic_verification_secret'] ) ) : '';

		$curr_opts = get_option( 'slm_plugin_options' );

		$options = array(
			'lic_creation_secret'     => $lic_creation_secret,
			'lic_prefix'              => $lic_prefix,
			'default_max_domains'     => $default_max_domains,
			'lic_verification_secret' => $lic_verification_secret,
			'enable_auto_key_expiry'  => isset( $_POST['enable_auto_key_expiry'] ) ? '1' : '',
			'enable_debug'            => isset( $_POST['enable_debug'] ) ? '1' : '',
		);

		$options = array_merge( $curr_opts, $options );

		update_option( 'slm_plugin_options', $options );

		echo '<div id="message" class="updated fade"><p>';
		echo 'Options Updated!';
		echo '</p></div>';
	}

	$options = get_option( 'slm_plugin_options' );

	$secret_key = isset($options['lic_creation_secret']) && !empty($options['lic_creation_secret']) ? $options['lic_creation_secret'] : '';
	if ( empty( $secret_key ) ) {
		$secret_key = uniqid( '', true );
	}
	$secret_verification_key = isset($options['lic_verification_secret']) && !empty($options['lic_verification_secret']) ? $options['lic_verification_secret'] : '';
	if ( empty( $secret_verification_key ) ) {
		$secret_verification_key = uniqid( '', true );
	}

    $lic_prefix = isset($options['lic_prefix']) && !empty($options['lic_prefix']) ? $options['lic_prefix'] : '';
    $default_max_domains = isset($options['default_max_domains']) && !empty($options['default_max_domains']) ? $options['default_max_domains'] : '';
    $enable_auto_key_expiry = isset($options['enable_auto_key_expiry']) && !empty($options['enable_auto_key_expiry']) ? 'checked="checked"' : '';
    $enable_debug_checked = isset($options['enable_debug']) && !empty($options['enable_debug']) ? 'checked="checked"' : '';
	?>
	<p>For information, updates and documentation, please visit the <a href="https://www.tipsandtricks-hq.com/software-license-manager-plugin-for-wordpress" target="_blank">License Manager Documentation</a> page.</p>

	<div class="postbox">
		<h3 class="hndle"><label for="title">Quick Usage Guide</label></h3>
		<div class="inside">

			<p>1. First register a key at purchase time.</p>
			<p>2. Add the code so at activation time it asks for the key.</p>
			<p>3. Integrate the real time online key verification part.</p>
		</div></div>

	<form method="post" action="">
		<?php wp_nonce_field( 'slm_settings_nonce_action', 'slm_settings_nonce_val' ); ?>

		<div class="postbox">
			<h3 class="hndle"><label for="title">General License Manager Settings</label></h3>
			<div class="inside">
				<table class="form-table">

					<tr valign="top">
						<th scope="row">Secret Key for License Creation</th>
						<td><input type="text" name="lic_creation_secret" value="<?php echo esc_attr( $secret_key ); ?>" size="40" />
							<br />This secret key will be used to authenticate any license creation request. You can change it with something random.</td>
					</tr>

					<tr valign="top">
						<th scope="row">Secret Key for License Verification Requests</th>
						<td><input type="text" name="lic_verification_secret" value="<?php echo esc_attr( $secret_verification_key ); ?>" size="40" />
							<br />This secret key will be used to authenticate any license verification request from customer's site. Important! Do not change this value once your customers start to use your product(s)!</td>
					</tr>

					<tr valign="top">
						<th scope="row">License Key Prefix</th>
						<td><input type="text" name="lic_prefix" value="<?php echo esc_attr( $lic_prefix ); ?>" size="40" />
							<br />You can optionally specify a prefix for the license keys. This prefix will be added to the uniquely generated license keys.</td>
					</tr>

					<tr valign="top">
						<th scope="row">Maximum Allowed Domains</th>
						<td><input type="text" name="default_max_domains" value="<?php echo esc_attr( $default_max_domains ); ?>" size="6" />
							<br />Maximum number of domains/installs which each license is valid for (default value).</td>
					</tr>

					<tr valign="top">
						<th scope="row">Auto Expire License Keys</th>
						<td><input name="enable_auto_key_expiry" type="checkbox" <?php echo esc_attr($enable_auto_key_expiry) ?> value="1"/>
							<p class="description">When enabled, it will automatically set the status of a license key to "Expired" when the expiry date value of the key is reached.
								It doesn't remotely deactivate a key. It simply changes the status of the key in your database to expired.</p>
						</td>
					</tr>
				</table>
			</div></div>

		<div class="postbox">
			<h3 class="hndle"><label for="title">Debugging and Testing Settings</label></h3>
			<div class="inside">
				<table class="form-table">

					<tr valign="top">
						<th scope="row">Enable Debug Logging</th>
						<td><input name="enable_debug" type="checkbox" <?php echo esc_attr($enable_debug_checked) ?> value="1"/>
							<p class="description">If checked, debug output will be written to log files (keep it disabled unless you are troubleshooting).</p>
							<br />- View debug log file by clicking <a href="<?php echo esc_attr( wp_nonce_url( 'admin.php?page=wp_lic_mgr_settings&slm_view_log=1', 'slm_view_debug_log', 'slm_view_debug_log_nonce' ) ); ?>" target="_blank">here</a>.
							<br />- Reset debug log file by clicking <a href="<?php echo esc_attr( wp_nonce_url( 'admin.php?page=wp_lic_mgr_settings&slm_reset_log=1', 'slm_reset_debug_log', 'slm_reset_debug_log_nonce' ) ); ?>" target="_blank" onclick="return confirm('Are you sure want to reset debug log file?');">here</a>.
						</td>
					</tr>

				</table>
			</div></div>

		<div class="submit">
			<input type="submit" class="button-primary" name="slm_save_settings" value=" <?php esc_html_e( 'Update Options', 'slm' ); ?>" />
		</div>
	</form>
	<?php
}
