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

                if (empty($post_url)){
                    wp_die('The URL value is empty. Go back and enter a valid URL value.');
                }

                // Send query to the license manager server
                $response = wp_remote_get(add_query_arg($data, $post_url), array('timeout' => 20, 'sslverify' => false));

                // Check for error in the response
                if (is_wp_error($response)){
                    echo "Unexpected Error! The query returned with an error.";
                }

                // License data.
                $license_data = json_decode(wp_remote_retrieve_body($response));

		echo '<div id="message" class="updated fade"><p>';
		echo 'Request sent to the specified URL!';
		echo '</p></div>';
                echo '<p>Variable dump of the response below:</p>';
                var_dump($license_data);
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
