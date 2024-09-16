<?php

function lic_mgr_integration_help_menu() {
	?>
	<style type="text/css">
	.lic_mgr_code{border:1px solid #C2D7EF; background-color:#E2EDFF; margin:10px 0; padding:10px; width:800px; font-family:"Consolas","Bitstream Vera Sans Mono","Courier New",Courier,monospace !important; font-size:13px;}
        .slm_yellow_box {background: #FFF6D5; border: 1px solid #D1B655; color: #3F2502; margin: 10px 0px 10px 0px; padding: 5px 5px 5px 10px; text-shadow: 1px 1px #FFFFFF;}
	</style>
	<?php

	$options                 = get_option( 'slm_plugin_options' );
	$creation_secret_key     = isset($options['lic_creation_secret']) && !empty($options['lic_creation_secret']) ? $options['lic_creation_secret'] : '';
	$secret_verification_key = isset($options['lic_verification_secret']) && !empty($options['lic_verification_secret']) ? $options['lic_verification_secret'] : '';

	echo '<div class="wrap">';
	echo '<h2>License Manager Integration Help</h2>';
	echo '<div id="poststuff"><div id="post-body">';

    echo '<div class="slm_yellow_box">';
	echo '<p>For information, updates and documentation, please visit the <a href="https://www.tipsandtricks-hq.com/software-license-manager-plugin-for-wordpress" target="_blank">License Manager Documentation</a> page.</p>';
    echo '</div>';

    echo '<h3>Some Key Variable Info for Your Install</h3>';

	$api_query_post_url = SLM_SITE_HOME_URL;
	echo '<strong>The License API Query POST URL For Your Installation</strong>';
	echo '<div class="lic_mgr_code">' . esc_url( $api_query_post_url ) . '</div>';

	echo '<strong>The License Activation or Deactivation API secret key</strong>';
	echo '<div class="lic_mgr_code">' . esc_html( $secret_verification_key ) . '</div>';

	echo '<strong>The License Creation API secret key</strong>';
	echo '<div class="lic_mgr_code">' . esc_html( $creation_secret_key ) . '</div>';

	echo '<h3>3rd Party Integration Documentation</h3>';

    echo '<div class="slm_yellow_box">';
    echo '<p>Integration documentation is available on the <a href="https://www.tipsandtricks-hq.com/software-license-manager-plugin-for-wordpress" target="_blank">License Manager Documentation</a> page. You can also download a sample plugin code from that page.</p>';
    echo '</div>';

	echo '</div></div>';
	echo '</div>';
}
