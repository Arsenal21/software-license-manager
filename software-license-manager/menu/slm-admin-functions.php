<?php

function wp_lic_mgr_admin_fnc_menu() {

	echo '<div class="wrap">';
	echo '<h2>License Manager Admin Functions</h2>';
	echo '<div id="poststuff"><div id="post-body">';

	$slm_options = get_option( 'slm_plugin_options' );

	$post_url = '';

	if ( isset( $_POST['send_deactivation_request'] ) ) {
		check_admin_referer( 'slm_send_deact_req' );
		$post_url                 = filter_input( INPUT_POST, 'lic_mgr_deactivation_req_url', FILTER_SANITIZE_URL );
		$secretKeyForVerification = $slm_options['lic_verification_secret'];
		$data                     = array();
		$data['secret_key']       = $secretKeyForVerification;

		$ch = curl_init( $post_url );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$return_value = curl_exec( $ch );

		$msg = '';
		if ( 'Success' === $return_value ) {
			$msg .= 'Success message returned from the remote host.';
		}
		echo '<div id="message" class="updated fade"><p>';
		echo 'Request sent to the specified URL!';
		echo '<br />' . esc_html( $msg );
		echo '</p></div>';
	}
	?>
	<br />
	<div class="postbox">
		<h3 class="hndle"><label for="title">Send Deactivation Message for a License</label></h3>
		<div class="inside">
			<br /><strong>Enter the URL where the license deactivation message will be sent to</strong>
			<br /><br />
			<form method="post" action="">
				<?php wp_nonce_field( 'slm_send_deact_req' ); ?>
				<input name="lic_mgr_deactivation_req_url" type="text" size="100" value="<?php esc_attr( $post_url ); ?>"/>
				<div class="submit">
					<input type="submit" name="send_deactivation_request" value="Send Request" class="button" />
				</div>
			</form>
		</div></div>    
	<?php
	echo '</div></div>';
	echo '</div>';
}
