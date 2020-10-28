<?php

if (!defined('ABSPATH'))
    exit;

class SLM_STRIPE_CHECKOUT {

    var $plugin_version = '1.1.7';
    var $db_version = '1.0.8';
    var $plugin_url;
    var $plugin_path;

    function __construct() {
        define('SLM_STRIPE_CHECKOUT_VERSION', $this->plugin_version);
        define('SLM_STRIPE_CHECKOUT_DB_VERSION', $this->db_version);
        define('SLM_STRIPE_CHECKOUT_SITE_URL', site_url());
        define('SLM_STRIPE_CHECKOUT_HOME_URL', home_url());
        define('SLM_STRIPE_CHECKOUT_URL', $this->plugin_url());
        define('SLM_STRIPE_CHECKOUT_PATH', $this->plugin_path());
        $options = slm_stripe_checkout_get_option();
        if (isset($options['enable_debug']) && $options['enable_debug']=="1") {
            define('SLM_STRIPE_CHECKOUT_DEBUG', true);
        } else {
            define('SLM_STRIPE_CHECKOUT_DEBUG', false);
        }
        if (isset($options['stripe_testmode']) && $options['stripe_testmode']=="1") {
            define('SLM_STRIPE_CHECKOUT_TESTMODE', true);
        } else {
            define('SLM_STRIPE_CHECKOUT_TESTMODE', false);
        }
        define('SLM_STRIPE_CHECKOUT_DEBUG_LOG_PATH', $this->debug_log_path());
        $this->plugin_includes();
        $this->loader_operations();
    }

    function plugin_includes() {
        include_once('slm-stripe-checkout-order.php');
        include_once('slm-stripe-checkout-process.php');
        include_once('class-slm-stripe-api.php');
    }

    function loader_operations() {
        register_activation_hook( __FILE__, array($this, 'activate_handler') );
        add_action('plugins_loaded', array($this, 'plugins_loaded_handler'));
        if (is_admin()) {
            add_filter('plugin_action_links', array($this, 'add_plugin_action_links'), 10, 2);
        }
        add_action('admin_notices', array($this, 'admin_notice'));
        add_action('wp_head', array($this, 'wp_head'));
        add_action('wp_enqueue_scripts', array($this, 'plugin_scripts'));
        add_action('admin_menu', array($this, 'add_options_menu'));
        add_action('init', array($this, 'plugin_init'));
        add_filter('manage_slm_stripe_co_order_posts_columns', 'slm_stripe_checkout_order_columns');
        add_action('manage_slm_stripe_co_order_posts_custom_column', 'slm_stripe_checkout_custom_column', 10, 2);
        add_shortcode('slm_stripe_checkout', 'slm_stripe_checkout_button_handler');
        add_shortcode('slm_stripe_checkout_v3', 'slm_stripe_checkout_v3_button_handler');
    }

    function plugins_loaded_handler() {  //Runs when plugins_loaded action gets fired
        load_plugin_textdomain( 'slm-stripe-checkout', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
        $this->check_upgrade();
    }

    function activate_handler() {
        slm_stripe_checkout_set_default_email_options();
        add_option('slm_stripe_checkout_db_version', $this->db_version);
    }

    function check_upgrade() {
        if (is_admin()) {
            $db_version = get_option('slm_stripe_checkout_db_version');
            if (!isset($db_version) || $db_version != $this->plugin_version) {
                slm_stripe_checkout_set_default_email_options();
                update_option('slm_stripe_checkout_db_version', $this->plugin_version);
            }
        }
    }

    function admin_notice() {
        if (SLM_STRIPE_CHECKOUT_DEBUG) {  //debug is enabled. Check to make sure log file is writable
            $real_file = SLM_STRIPE_CHECKOUT_DEBUG_LOG_PATH;
            if (!is_writeable($real_file)) {
                echo '<div class="updated"><p>' . __('SLM Stripe Checkout Debug log file is not writable. Please check to make sure that it has the correct file permission (ideally 644). Otherwise the plugin will not be able to write to the log file. The log file (log.txt) can be found in the root directory of the plugin - ', 'slm-stripe-checkout') . '<code>' . SLM_STRIPE_CHECKOUT_URL . '</code></p></div>';
            }
        }
    }

    function plugin_init() {
        //register order type
        slm_stripe_checkout_register_order_type();
        //process order
        slm_stripe_checkout_process_order();
        //process webhook
        slm_stripe_checkout_process_webhook();
    }

    function plugin_scripts() {
        if (!is_admin()) {

        }
    }

    function wp_head(){
        $output = '';
        global $post;
	if(is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'slm_stripe_checkout_v3')){
            $output .= '<script src="https://js.stripe.com/v3"></script>';
        }
        echo $output;
    }

    function plugin_url() {
        if ($this->plugin_url)
            return $this->plugin_url;
        return $this->plugin_url = plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__));
    }

    function plugin_path() {
        if ($this->plugin_path)
            return $this->plugin_path;
        return $this->plugin_path = untrailingslashit(plugin_dir_path(__FILE__));
    }

    function debug_log_path() {
        return SLM_STRIPE_CHECKOUT_PATH . '/log.txt';
    }

    function add_plugin_action_links($links, $file) {
        if ($file == plugin_basename(dirname(__FILE__) . '/main.php')) {
            $links[] = '<a href="edit.php?post_type=slmstripeco_order&page=slm-stripe-checkout-settings">'.__('Settings', 'slm-stripe-checkout').'</a>';
        }
        return $links;
    }

    function add_options_menu() {
        if (is_admin()) {
            add_submenu_page('edit.php?post_type=slmstripeco_order', __('Settings', 'slm-stripe-checkout'), __('Settings', 'slm-stripe-checkout'), 'manage_options', 'slm-stripe-checkout-settings', array($this, 'options_page'));
            add_submenu_page('edit.php?post_type=slmstripeco_order', __('Debug', 'slm-stripe-checkout'), __('Debug', 'slm-stripe-checkout'), 'manage_options', 'slm-stripe-checkout-debug', array($this, 'debug_page'));
        }
    }

    function options_page() {
        $plugin_tabs = array(
            'slm-stripe-checkout-settings' => __('General', 'slm-stripe-checkout'),
            'slm-stripe-checkout-settings&tab=emails' => __('Emails', 'slm-stripe-checkout')
        );
        echo '<div class="wrap"><h2>'.__('SLM Stripe Checkout', 'slm-stripe-checkout').' v' . SLM_STRIPE_CHECKOUT_VERSION . '</h2>';
        $url = 'https://noorsplugin.com/stripe-checkout-plugin-for-wordpress/';
        $link_msg = sprintf( wp_kses( __( 'Please visit the <a target="_blank" href="%s">Stripe Checkout</a> documentation page for setup instructions.', 'slm-stripe-checkout' ), array(  'a' => array( 'href' => array(), 'target' => array() ) ) ), esc_url( $url ) );
        echo '<div class="update-nag">'.$link_msg.'</div>';

        if (isset($_GET['page'])) {
            $current = $_GET['page'];
            if (isset($_GET['tab'])) {
                $current .= "&tab=" . $_GET['tab'];
            }
        }
        $content = '';
        $content .= '<h2 class="nav-tab-wrapper">';
        foreach ($plugin_tabs as $location => $tabname) {
            if ($current == $location) {
                $class = ' nav-tab-active';
            } else {
                $class = '';
            }
            $content .= '<a class="nav-tab' . $class . '" href="?post_type=slmstripeco_order&page=' . $location . '">' . $tabname . '</a>';
        }
        $content .= '</h2>';
        echo $content;

        //echo '<div id="poststuff"><div id="post-body">';

        if(isset($_GET['tab']))
        {
            switch ($_GET['tab'])
            {
               case 'emails':
                   $this->email_settings();
                   break;
            }
        }
        else
        {
            $this->general_settings();
        }

        //echo '</div></div>';
        echo '</div>';
    }

    function general_settings() {
        if (isset($_POST['slm_stripe_checkout_update_settings'])) {
            $nonce = $_REQUEST['_wpnonce'];
            if (!wp_verify_nonce($nonce, 'slm_stripe_checkout_general_settings')) {
                wp_die(__('Error! Nonce Security Check Failed! please save the general settings again.', 'slm-stripe-checkout'));
            }
            $stripe_testmode = (isset($_POST["stripe_testmode"]) && $_POST["stripe_testmode"] == '1') ? '1' : '';
            $stripe_test_secret_key = '';
            if(isset($_POST['stripe_test_secret_key']) && !empty($_POST['stripe_test_secret_key'])){
                $stripe_test_secret_key = sanitize_text_field($_POST['stripe_test_secret_key']);
            }
            $stripe_test_publishable_key = '';
            if(isset($_POST['stripe_test_publishable_key']) && !empty($_POST['stripe_test_publishable_key'])){
                $stripe_test_publishable_key = sanitize_text_field($_POST['stripe_test_publishable_key']);
            }
            $stripe_secret_key = '';
            if(isset($_POST['stripe_secret_key']) && !empty($_POST['stripe_secret_key'])){
                $stripe_secret_key = sanitize_text_field($_POST['stripe_secret_key']);
            }
            $stripe_publishable_key = '';
            if(isset($_POST['stripe_publishable_key']) && !empty($_POST['stripe_publishable_key'])){
                $stripe_publishable_key = sanitize_text_field($_POST['stripe_publishable_key']);
            }
            $stripe_currency_code = '';
            if(isset($_POST['stripe_currency_code']) && !empty($_POST['stripe_currency_code'])){
                $stripe_currency_code = sanitize_text_field($_POST['stripe_currency_code']);
            }
            $return_url = '';
            if(isset($_POST['return_url']) && !empty($_POST['return_url'])){
                $return_url = sanitize_text_field($_POST['return_url']);
            }
            $stripe_options = array();
            $stripe_options['stripe_testmode'] = $stripe_testmode;
            $stripe_options['stripe_test_secret_key'] = $stripe_test_secret_key;
            $stripe_options['stripe_test_publishable_key'] = $stripe_test_publishable_key;
            $stripe_options['stripe_secret_key'] = $stripe_secret_key;
            $stripe_options['stripe_publishable_key'] = $stripe_publishable_key;
            $stripe_options['stripe_currency_code'] = $stripe_currency_code;
            $stripe_options['return_url'] = $return_url;
            slm_stripe_checkout_update_option($stripe_options);
            echo '<div id="message" class="updated fade"><p><strong>';
            echo __('Settings Saved', 'slm-stripe-checkout').'!';
            echo '</strong></p></div>';
        }

        $stripe_options = slm_stripe_checkout_get_option();
        $api_keys_url = "https://dashboard.stripe.com/account/apikeys";
        $api_keys_link = sprintf(wp_kses(__('You can get it from your <a target="_blank" href="%s">stripe account</a>.', 'slm-stripe-checkout'), array('a' => array('href' => array(), 'target' => array()))), esc_url($api_keys_url));

        $currency_check_url = "https://support.stripe.com/questions/which-currencies-does-stripe-support";
        $currency_check_link = sprintf(wp_kses(__('See <a target="_blank" href="%s">which currencies are supported by stripe</a> for details.', 'slm-stripe-checkout'), array('a' => array('href' => array(), 'target' => array()))), esc_url($currency_check_url));

        $webhook_doc_url = "https://noorsplugin.com/stripe-checkout-plugin-for-wordpress/";
        $webhook_doc_url = sprintf(wp_kses(__('Learn how to configure it <a target="_blank" href="%s">here</a>.', 'slm-stripe-checkout'), array('a' => array('href' => array(), 'target' => array()))), esc_url($webhook_doc_url));
        ?>
        <table class="wpsc-general-settings-table">
            <tbody>
                <tr>
                    <td valign="top">
                        <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
                            <?php wp_nonce_field('slm_stripe_checkout_general_settings'); ?>

                            <table class="form-table">

                                <tbody>

                                    <tr valign="top">
                                        <th scope="row"><?Php _e('Test Mode', 'slm-stripe-checkout');?></th>
                                        <td> <fieldset><legend class="screen-reader-text"><span>Test Mode</span></legend><label for="stripe_testmode">
                                                    <input name="stripe_testmode" type="checkbox" id="stripe_testmode" <?php if ($stripe_options['stripe_testmode'] == '1') echo ' checked="checked"'; ?> value="1">
                                                    <?Php _e('Check this option if you want to place the Stripe payment gateway in test mode using test API keys.', 'slm-stripe-checkout');?></label>
                                            </fieldset></td>
                                    </tr>

                                    <tr valign="top">
                                        <th scope="row"><label for="stripe_test_secret_key"><?Php _e('Test Secret Key', 'slm-stripe-checkout');?></label></th>
                                        <td><input name="stripe_test_secret_key" type="text" id="stripe_test_secret_key" value="<?php echo $stripe_options['stripe_test_secret_key']; ?>" class="regular-text">
                                            <p class="description"><?Php echo __('Your Test Secret Key.', 'slm-stripe-checkout').' '.$api_keys_link;?></p></td>
                                    </tr>

                                    <tr valign="top">
                                        <th scope="row"><label for="stripe_test_publishable_key"><?Php _e('Test Publishable Key', 'slm-stripe-checkout');?></label></th>
                                        <td><input name="stripe_test_publishable_key" type="text" id="stripe_test_publishable_key" value="<?php echo $stripe_options['stripe_test_publishable_key']; ?>" class="regular-text">
                                            <p class="description"><?Php echo __('Your Test Publishable Key.', 'slm-stripe-checkout').' '.$api_keys_link;?></p></td>
                                    </tr>

                                    <tr valign="top">
                                        <th scope="row"><label for="stripe_secret_key"><?Php _e('Live Secret Key', 'slm-stripe-checkout');?></label></th>
                                        <td><input name="stripe_secret_key" type="text" id="stripe_secret_key" value="<?php echo $stripe_options['stripe_secret_key']; ?>" class="regular-text">
                                            <p class="description"><?Php echo __('Your Secret Key.', 'slm-stripe-checkout').' '.$api_keys_link;?></p></td>
                                    </tr>

                                    <tr valign="top">
                                        <th scope="row"><label for="stripe_publishable_key"><?Php _e('Live Publishable Key', 'slm-stripe-checkout');?></label></th>
                                        <td><input name="stripe_publishable_key" type="text" id="stripe_publishable_key" value="<?php echo $stripe_options['stripe_publishable_key']; ?>" class="regular-text">
                                            <p class="description"><?Php echo __('Your Live Publishable Key.', 'slm-stripe-checkout').' '.$api_keys_link;?></p></td>
                                    </tr>

                                    <tr valign="top">
                                        <th scope="row"><label for="stripe_currency_code"><?Php _e('Currency Code', 'slm-stripe-checkout');?></label></th>
                                        <td><input name="stripe_currency_code" type="text" id="stripe_currency_code" value="<?php echo $stripe_options['stripe_currency_code']; ?>" class="regular-text">
                                            <p class="description"><?Php echo __('The currency of the payment.', 'slm-stripe-checkout').' '.$currency_check_link;?></p></td>
                                    </tr>

                                    <tr valign="top">
                                        <th scope="row"><label for="return_url"><?Php _e('Return URL', 'slm-stripe-checkout');?></label></th>
                                        <td><input name="return_url" type="text" id="return_url" value="<?php echo $stripe_options['return_url']; ?>" class="regular-text">
                                            <p class="description"><?Php echo __('The page URL to which the customer will be redirected after a successful payment.', 'slm-stripe-checkout');?></p></td>
                                    </tr>

                                    <tr valign="top">
                                        <th scope="row"><label for="return_url"><?Php _e('Stripe Webhook URL', 'slm-stripe-checkout');?></label></th>
                                        <td><code><?php echo esc_url(home_url('/')."?wp_stripe_co_webhook=1"); ?></code>
                                            <p class="description"><?Php echo __('The URL of your site where Stripe will send notification of an event.', 'slm-stripe-checkout').' '.$webhook_doc_url;?></p></td>
                                    </tr>

                                </tbody>

                            </table>

                            <p class="submit"><input type="submit" name="slm_stripe_checkout_update_settings" id="slm_stripe_checkout_update_settings" class="button button-primary" value="<?Php _e('Save Changes', 'slm-stripe-checkout');?>"></p></form>
                    </td>
                    <td valign="top">

                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    function email_settings() {
        if (isset($_POST['slm_stripe_checkout_update_email_settings'])) {
            $nonce = $_REQUEST['_wpnonce'];
            if (!wp_verify_nonce($nonce, 'slm_stripe_checkout_email_settings')) {
                wp_die(__('Error! Nonce Security Check Failed! please save the email settings again.', 'slm-stripe-checkout'));
            }
            $_POST = stripslashes_deep($_POST);
            $email_from_name = '';
            if(isset($_POST['email_from_name']) && !empty($_POST['email_from_name'])){
                $email_from_name = sanitize_text_field($_POST['email_from_name']);
            }
            $email_from_address= '';
            if(isset($_POST['email_from_address']) && !empty($_POST['email_from_address'])){
                $email_from_address = sanitize_text_field($_POST['email_from_address']);
            }
            $purchase_email_enabled = (isset($_POST["purchase_email_enabled"]) && $_POST["purchase_email_enabled"] == '1') ? '1' : '';
            $purchase_email_subject = '';
            if(isset($_POST['purchase_email_subject']) && !empty($_POST['purchase_email_subject'])){
                $purchase_email_subject = sanitize_text_field($_POST['purchase_email_subject']);
            }
            $purchase_email_type = '';
            if(isset($_POST['purchase_email_type']) && !empty($_POST['purchase_email_type'])){
                $purchase_email_type = sanitize_text_field($_POST['purchase_email_type']);
            }
            $purchase_email_body = '';
            if(isset($_POST['purchase_email_body']) && !empty($_POST['purchase_email_body'])){
                $purchase_email_body = trim($_POST['purchase_email_body']);
            }
            $sale_notification_email_enabled = (isset($_POST["sale_notification_email_enabled"]) && $_POST["sale_notification_email_enabled"] == '1') ? '1' : '';
            $sale_notification_email_recipient = '';
            if(isset($_POST['sale_notification_email_recipient']) && !empty($_POST['sale_notification_email_recipient'])){
                $sale_notification_email_recipient = sanitize_email($_POST['sale_notification_email_recipient']);
            }
            $sale_notification_email_subject = '';
            if(isset($_POST['sale_notification_email_subject']) && !empty($_POST['sale_notification_email_subject'])){
                $sale_notification_email_subject = sanitize_text_field($_POST['sale_notification_email_subject']);
            }
            $sale_notification_email_type = '';
            if(isset($_POST['sale_notification_email_type']) && !empty($_POST['sale_notification_email_type'])){
                $sale_notification_email_type = sanitize_text_field($_POST['sale_notification_email_type']);
            }
            $sale_notification_email_body = '';
            if(isset($_POST['sale_notification_email_body']) && !empty($_POST['sale_notification_email_body'])){
                $sale_notification_email_body = trim($_POST['sale_notification_email_body']);
            }
            $stripe_options = array();
            $stripe_options['email_from_name'] = $email_from_name;
            $stripe_options['email_from_address'] = $email_from_address;
            $stripe_options['purchase_email_enabled'] = $purchase_email_enabled;
            $stripe_options['purchase_email_subject'] = $purchase_email_subject;
            $stripe_options['purchase_email_type'] = $purchase_email_type;
            $stripe_options['purchase_email_body'] = $purchase_email_body;
            $stripe_options['sale_notification_email_enabled'] = $sale_notification_email_enabled;
            $stripe_options['sale_notification_email_recipient'] = $sale_notification_email_recipient;
            $stripe_options['sale_notification_email_subject'] = $sale_notification_email_subject;
            $stripe_options['sale_notification_email_type'] = $sale_notification_email_type;
            $stripe_options['sale_notification_email_body'] = $sale_notification_email_body;
            slm_stripe_checkout_update_email_option($stripe_options);
            echo '<div id="message" class="updated fade"><p><strong>';
            echo __('Settings Saved', 'slm-stripe-checkout').'!';
            echo '</strong></p></div>';
        }

        $stripe_options = slm_stripe_checkout_get_email_option();
        $api_keys_url = "https://dashboard.stripe.com/account/apikeys";
        $api_keys_link = sprintf(wp_kses(__('You can get it from your <a target="_blank" href="%s">stripe account</a>.', 'slm-stripe-checkout'), array('a' => array('href' => array(), 'target' => array()))), esc_url($api_keys_url));

        $email_tags_url = "https://noorsplugin.com/stripe-checkout-plugin-for-wordpress/";
        $email_tags_link = sprintf(wp_kses(__('You can find the full list of available email tags <a target="_blank" href="%s">here</a>.', 'slm-stripe-checkout'), array('a' => array('href' => array(), 'target' => array()))), esc_url($email_tags_url));
        ?>
        <table class="wpsc-email-settings-table">
            <tbody>
                <tr>
                    <td valign="top">
                        <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
                            <?php wp_nonce_field('slm_stripe_checkout_email_settings'); ?>

                            <h2><?Php _e('Email Sender Options', 'slm-stripe-checkout');?></h2>
                            <table class="form-table">
                                <tbody>
                                    <tr valign="top">
                                        <th scope="row"><label for="email_from_name"><?Php _e('From Name', 'slm-stripe-checkout');?></label></th>
                                        <td><input name="email_from_name" type="text" id="email_from_name" value="<?php echo esc_attr($stripe_options['email_from_name']); ?>" class="regular-text">
                                            <p class="description"><?Php _e('The sender name that appears in outgoing emails. Leave empty to use the default.', 'slm-stripe-checkout');?></p></td>
                                    </tr>
                                    <tr valign="top">
                                        <th scope="row"><label for="email_from_address"><?Php _e('From Email Address', 'slm-stripe-checkout');?></label></th>
                                        <td><input name="email_from_address" type="text" id="email_from_address" value="<?php echo esc_attr($stripe_options['email_from_address']); ?>" class="regular-text">
                                            <p class="description"><?Php _e('The sender email that appears in outgoing emails. Leave empty to use the default.', 'slm-stripe-checkout');?></p></td>
                                    </tr>
                                </tbody>
                            </table>
                            <h2><?Php _e('Purchase Receipt Email', 'slm-stripe-checkout');?></h2>
                            <p><?Php _e('A purchase receipt email is sent to the customer after completion of a successful purchase', 'slm-stripe-checkout');?></p>
                            <table class="form-table">
                                <tbody>
                                    <tr valign="top">
                                        <th scope="row"><?Php _e('Enable/Disable', 'slm-stripe-checkout');?></th>
                                        <td> <fieldset><legend class="screen-reader-text"><span>Enable/Disable</span></legend><label for="purchase_email_enabled">
                                                    <input name="purchase_email_enabled" type="checkbox" id="purchase_email_enabled" <?php if ($stripe_options['purchase_email_enabled'] == '1') echo ' checked="checked"'; ?> value="1">
                                                    <?Php _e('Enable this email notification', 'slm-stripe-checkout');?></label>
                                            </fieldset></td>
                                    </tr>
                                    <tr valign="top">
                                        <th scope="row"><label for="purchase_email_subject"><?Php _e('Subject', 'slm-stripe-checkout');?></label></th>
                                        <td><input name="purchase_email_subject" type="text" id="purchase_email_subject" value="<?php echo esc_attr($stripe_options['purchase_email_subject']); ?>" class="regular-text">
                                            <p class="description"><?Php _e('The subject line for the purchase receipt email.', 'slm-stripe-checkout');?></p></td>
                                    </tr>
                                    <tr valign="top">
                                        <th scope="row"><label for="purchase_email_type"><?php _e('Email Type', 'slm-stripe-checkout');?></label></th>
                                        <td>
                                        <select name="purchase_email_type" id="purchase_email_type">
                                            <option <?php echo ($stripe_options['purchase_email_type'] === 'plain')?'selected="selected"':'';?> value="plain"><?php _e('Plain Text', 'slm-stripe-checkout')?></option>
                                            <option <?php echo ($stripe_options['purchase_email_type'] === 'html')?'selected="selected"':'';?> value="html"><?php _e('HTML', 'slm-stripe-checkout')?></option>
                                        </select>
                                        <p class="description"><?php _e('The content type of the purchase receipt email.', 'slm-stripe-checkout')?></p>
                                        </td>
                                    </tr>
                                    <tr valign="top">
                                        <th scope="row"><label for="purchase_email_body"><?Php _e('Email Body', 'slm-stripe-checkout');?></label></th>
                                        <td><?php wp_editor($stripe_options['purchase_email_body'], 'purchase_email_body', array('textarea_name' => 'purchase_email_body'));?>
                                            <p class="description"><?Php echo __('The main content of the purchase receipt email.', 'slm-stripe-checkout').' '.$email_tags_link;?></p></td>
                                    </tr>
                                </tbody>
                            </table>
                            <h2><?Php _e('Sale Notification Email', 'slm-stripe-checkout');?></h2>
                            <p><?Php _e('A sale notification email is sent to the chosen recipient after completion of a successful purchase', 'slm-stripe-checkout');?></p>
                            <table class="form-table">
                                <tbody>
                                    <tr valign="top">
                                        <th scope="row"><?Php _e('Enable/Disable', 'slm-stripe-checkout');?></th>
                                        <td> <fieldset><legend class="screen-reader-text"><span>Enable/Disable</span></legend><label for="sale_notification_email_enabled">
                                                    <input name="sale_notification_email_enabled" type="checkbox" id="sale_notification_email_enabled" <?php if ($stripe_options['sale_notification_email_enabled'] == '1') echo ' checked="checked"'; ?> value="1">
                                                    <?Php _e('Enable this email notification', 'slm-stripe-checkout');?></label>
                                            </fieldset></td>
                                    </tr>
                                    <tr valign="top">
                                        <th scope="row"><label for="sale_notification_email_recipient"><?Php _e('Recipient', 'slm-stripe-checkout');?></label></th>
                                        <td><input name="sale_notification_email_recipient" type="text" id="sale_notification_email_recipient" value="<?php echo esc_attr($stripe_options['sale_notification_email_recipient']); ?>" class="regular-text">
                                            <p class="description"><?Php _e('The email address that should receive a notification anytime a sale is made.', 'slm-stripe-checkout');?></p></td>
                                    </tr>
                                    <tr valign="top">
                                        <th scope="row"><label for="sale_notification_email_subject"><?Php _e('Subject', 'slm-stripe-checkout');?></label></th>
                                        <td><input name="sale_notification_email_subject" type="text" id="sale_notification_email_subject" value="<?php echo esc_attr($stripe_options['sale_notification_email_subject']); ?>" class="regular-text">
                                            <p class="description"><?Php _e('The subject line for the sale notification email.', 'slm-stripe-checkout');?></p></td>
                                    </tr>
                                    <tr valign="top">
                                        <th scope="row"><label for="sale_notification_email_type"><?php _e('Email Type', 'slm-stripe-checkout');?></label></th>
                                        <td>
                                        <select name="sale_notification_email_type" id="sale_notification_email_type">
                                            <option <?php echo ($stripe_options['sale_notification_email_type'] === 'plain')?'selected="selected"':'';?> value="plain"><?php _e('Plain Text', 'slm-stripe-checkout')?></option>
                                            <option <?php echo ($stripe_options['sale_notification_email_type'] === 'html')?'selected="selected"':'';?> value="html"><?php _e('HTML', 'slm-stripe-checkout')?></option>
                                        </select>
                                        <p class="description"><?php _e('The content type of the sale notification email.', 'slm-stripe-checkout')?></p>
                                        </td>
                                    </tr>
                                    <tr valign="top">
                                        <th scope="row"><label for="sale_notification_email_body"><?Php _e('Email Body', 'slm-stripe-checkout');?></label></th>
                                        <td><?php wp_editor($stripe_options['sale_notification_email_body'], 'sale_notification_email_body', array('textarea_name' => 'sale_notification_email_body'));?>
                                            <p class="description"><?Php echo __('The main content of the sale notification email.', 'slm-stripe-checkout').' '.$email_tags_link;?></p></td>
                                    </tr>
                                </tbody>
                            </table>

                            <p class="submit"><input type="submit" name="slm_stripe_checkout_update_email_settings" id="slm_stripe_checkout_update_email_settings" class="button button-primary" value="<?Php _e('Save Changes', 'slm-stripe-checkout');?>"></p></form>
                    </td>
                    <td valign="top" style="width: 300px">
                        <div style="background: #ffc; border: 1px solid #333; margin: 2px; padding: 3px 15px">
                        <h3><?php _e('Need Help?', 'slm-stripe-checkout')?></h3>
                        <ol>
                        <li><?php printf(__('Use the <a href="%s">Debug</a> menu for diagnostics.', 'slm-stripe-checkout'), 'edit.php?post_type=slmstripeco_order&page=slm-stripe-checkout-debug');?></li>
                        <li><?php printf(__('Check out the <a target="_blank" href="%s">support forum</a> and <a target="_blank" href="%s">FAQ</a>.', 'slm-stripe-checkout'), 'https://wordpress.org/support/plugin/slm-stripe-checkout', 'https://wordpress.org/plugins/slm-stripe-checkout/#faq');?></li>
                        <li><?php printf(__('Visit the <a target="_blank" href="%s">plugin homepage</a>.', 'slm-stripe-checkout'), 'https://noorsplugin.com/stripe-checkout-plugin-for-wordpress/');?></li>
                        </ol>
                        <h3><?php _e('Rate This Plugin', 'slm-stripe-checkout')?></h3>
                        <p><?php printf(__('Please <a target="_blank" href="%s">rate us</a> and give feedback.', 'slm-stripe-checkout'), 'https://wordpress.org/support/plugin/slm-stripe-checkout/reviews?rate=5#new-post');?></p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    function debug_page() {
        ?>
        <div class="wrap">
            <h2><?Php _e('SLM Stripe Checkout Debug Log', 'slm-stripe-checkout');?></h2>
            <div id="poststuff">
                <div id="post-body">
                    <?php
                    if (isset($_POST['slm_stripe_checkout_update_log_settings'])) {
                        $nonce = $_REQUEST['_wpnonce'];
                        if (!wp_verify_nonce($nonce, 'slm_stripe_checkout_debug_log_settings')) {
                            wp_die(__('Error! Nonce Security Check Failed! please save the debug settings again.', 'slm-stripe-checkout'));
                        }
                        $options = array();
                        $options['enable_debug'] = (isset($_POST["enable_debug"]) && $_POST["enable_debug"] == '1') ? '1' : '';
                        slm_stripe_checkout_update_option($options);
                        echo '<div id="message" class="updated fade"><p>'.__('Settings Saved', 'slm-stripe-checkout').'!</p></div>';
                    }
                    if (isset($_POST['slm_stripe_checkout_reset_log'])) {
                        $nonce = $_REQUEST['_wpnonce'];
                        if (!wp_verify_nonce($nonce, 'slm_stripe_checkout_reset_log_settings')) {
                            wp_die(__('Error! Nonce Security Check Failed! please reset the debug log file again.', 'slm-stripe-checkout'));
                        }
                        if (slm_stripe_checkout_reset_log()) {
                            echo '<div id="message" class="updated fade"><p>'.__('Debug log file has been reset', 'slm-stripe-checkout').'!</p></div>';
                        } else {
                            echo '<div id="message" class="error"><p>'.__('Debug log file could not be reset', 'slm-stripe-checkout').'!</p></div>';
                        }
                    }
                    $real_file = SLM_STRIPE_CHECKOUT_DEBUG_LOG_PATH;
                    $content = file_get_contents($real_file);
                    $content = esc_textarea($content);
                    $options = slm_stripe_checkout_get_option();
                    ?>
                    <div id="template"><textarea cols="70" rows="25" name="slm_stripe_checkout_log" id="slm_stripe_checkout_log"><?php echo $content; ?></textarea></div>
                    <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
                        <?php wp_nonce_field('slm_stripe_checkout_debug_log_settings'); ?>
                        <table class="form-table">
                            <tbody>
                                <tr valign="top">
                                    <th scope="row"><?Php _e('Enable Debug', 'slm-stripe-checkout');?></th>
                                    <td> <fieldset><legend class="screen-reader-text"><span>Enable Debug</span></legend><label for="enable_debug">
                                                <input name="enable_debug" type="checkbox" id="enable_debug" <?php if ($options['enable_debug'] == '1') echo ' checked="checked"'; ?> value="1">
                                                <?Php _e('Check this option if you want to enable debug', 'slm-stripe-checkout');?></label>
                                        </fieldset></td>
                                </tr>

                            </tbody>

                        </table>
                        <p class="submit"><input type="submit" name="slm_stripe_checkout_update_log_settings" id="slm_stripe_checkout_update_log_settings" class="button button-primary" value="<?Php _e('Save Changes', 'slm-stripe-checkout');?>"></p>
                    </form>
                    <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
                        <?php wp_nonce_field('slm_stripe_checkout_reset_log_settings'); ?>
                        <p class="submit"><input type="submit" name="slm_stripe_checkout_reset_log" id="slm_stripe_checkout_reset_log" class="button" value="<?Php _e('Reset Log', 'slm-stripe-checkout');?>"></p>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

}

$GLOBALS['slm_stripe_checkout'] = new SLM_STRIPE_CHECKOUT();

function slm_stripe_checkout_button_handler($atts) {
    $atts = array_map('sanitize_text_field', $atts);
    if(!isset($atts['item_name']) || empty($atts['item_name'])){
        return __('item_name cannot be left empty', 'slm-stripe-checkout');
    }
    if(!isset($atts['amount']) || !is_numeric($atts['amount'])){
        return __('You need to provide a valid price amount for your item', 'slm-stripe-checkout');
    }
    $description = '';
    if(isset($atts['description']) && !empty($atts['description'])){
        $description = $atts['description'];
    }
    $success_url = '';
    if(isset($atts['success_url'])){
        if(!empty($atts['success_url'])){
            $success_url = $atts['success_url'];
        }
        unset($atts['success_url']);
    }
    $options = slm_stripe_checkout_get_option();
    $key = $options['stripe_publishable_key'];
    if(SLM_STRIPE_CHECKOUT_TESTMODE){
        $key = $options['stripe_test_publishable_key'];
    }
    $atts['key'] = $key;
    //$atts['image'] = "https://stripe.com/img/documentation/checkout/marketplace.png";
    $currency = $options['stripe_currency_code'];
    if(!isset($atts['currency']) || empty($atts['currency'])){
        $atts['currency'] = $currency;
    }
    //prefill the email if the user is logged in
    if(is_user_logged_in()){
        $current_user = wp_get_current_user();
        $atts['email'] = $current_user->user_email;
    }
    /*
    $transient_name = 'slmstripecheckout-amount-' . sanitize_title_with_dashes($atts['item_name']);
    set_transient( $transient_name, $atts['amount'], 4 * 3600 );
    $transient_name = 'slmstripecheckout-currency-' . sanitize_title_with_dashes($atts['item_name']);
    set_transient( $transient_name, $atts['currency'], 4 * 3600 );
    */
    $price = $atts['amount']; //actual item price
    $atts['amount'] = $atts['amount'] * 100;  //the price supported by Stripe
    //unset item_name because Stripe doesn't recognize it
    $item_name = $atts['item_name'];
    unset($atts['item_name']);
    //
    $button_code = '<form action="" method="POST">';
    $button_code .= '<script src="https://checkout.stripe.com/checkout.js" class="stripe-button"';
    foreach ($atts as $key => $value) {
        $button_code .= 'data-' . $key . '="' . $value . '"';
    }
    $button_code .= '></script>';
    $button_code .= wp_nonce_field('slm_stripe_checkout_legacy', '_wpnonce', true, false);
    $button_code .= '<input type="hidden" value="'.$item_name.'" name="item_name" />';
    $button_code .= '<input type="hidden" value="'.$price.'" name="item_price" />';
    $button_code .= '<input type="hidden" value="'.$atts['amount'].'" name="item_amount" />';
    $button_code .= '<input type="hidden" value="'.$atts['currency'].'" name="item_currency" />';
    $button_code .= '<input type="hidden" value="'.$description.'" name="item_description" />';
    if(!empty($success_url)){
        $button_code .= '<input type="hidden" value="'.$success_url.'" name="success_url" />';
    }
    $button_code .= '<input type="hidden" value="1" name="slm_stripe_checkout_legacy" />';
    $button_code .= '</form>';
    return $button_code;
}

function slm_stripe_checkout_v3_button_handler($atts) {
    $atts = array_map('sanitize_text_field', $atts);
    $button_text = 'Buy Now';
    if(isset($atts['button_text']) && !empty($atts['button_text'])){
        $button_text = $atts['button_text'];
    }
    $identifier = '';
    if(!isset($atts['price']) || empty($atts['price'])){  //new API
        //check for existing items that may still use sku
        if(!isset($atts['sku']) || empty($atts['sku'])){
            return __('You need to provide a price ID or sku in the shortcode', 'slm-stripe-checkout');
        }
        else{
            $identifier = $atts['sku'];
        }
    }
    else{
        $identifier = $atts['price'];
    }
    $options = slm_stripe_checkout_get_option();
    $success_url = $options['return_url'];
    if(isset($atts['success_url']) && !empty($atts['success_url'])){
        $success_url = $atts['success_url'];
    }
    //check to make sure that the success_url is set
    if(!isset($success_url) || empty($success_url)){
        return __('You need to provide a return URL page in the settings', 'slm-stripe-checkout');
    }
    $cancel_url = home_url();
    if(isset($atts['cancel_url']) && !empty($atts['cancel_url'])){
        $cancel_url = $atts['cancel_url'];
    }
    $key = $options['stripe_publishable_key'];
    if(SLM_STRIPE_CHECKOUT_TESTMODE){
        $key = $options['stripe_test_publishable_key'];
    }
    if(!isset($key) || empty($key)){
        return __('You need to provide your publishable key in the settings', 'slm-stripe-checkout');
    }
    //mode
    $mode = 'payment';
    if(isset($atts['mode']) && 'subscription' == $atts['mode']){
        $mode = 'subscription';
    }
    $id = uniqid();
    $client_reference_id = 'wpsc'.$id;
    $button_code = <<<EOT
    <button id="wpsc$id">$button_text</button>
    <div id="error-wpsc$id"></div>
    <script>
    (function() {
        var stripe_$id = Stripe('$key');
        var checkoutButton_$id = document.querySelector('#wpsc$id');
        checkoutButton_$id.addEventListener('click', function () {
            stripe_$id.redirectToCheckout({
              lineItems: [{
                price: '{$identifier}',
                quantity: 1
              }],
              mode: '{$mode}',
              successUrl: '{$success_url}',
              cancelUrl: '{$cancel_url}',
              clientReferenceId: '$client_reference_id',
              billingAddressCollection: 'required'
            })
            .then(function (result) {
                if (result.error) {
                  var displayError = document.getElementById('error-wpsc$id');
                  displayError.textContent = result.error.message;
                }
            })
            .catch(function(error) {
                console.error('Error:', error);
                var displayError = document.getElementById('error-wpsc$id');
                displayError.textContent = error;
            });
        });
    })();
    </script>
EOT;
    return $button_code;
}

function slm_stripe_checkout_get_option(){
    $options = get_option('slm_stripe_checkout_options');
    if(!is_array($options)){
        $options = slm_stripe_checkout_get_empty_options_array();
    }
    return $options;
}

function slm_stripe_checkout_update_option($new_options){
    $empty_options = slm_stripe_checkout_get_empty_options_array();
    $options = slm_stripe_checkout_get_option();
    if(is_array($options)){
        $current_options = array_merge($empty_options, $options);
        $updated_options = array_merge($current_options, $new_options);
        update_option('slm_stripe_checkout_options', $updated_options);
    }
    else{
        $updated_options = array_merge($empty_options, $new_options);
        update_option('slm_stripe_checkout_options', $updated_options);
    }
}

function slm_stripe_checkout_get_empty_options_array(){
    $options = array();
    $options['stripe_testmode'] = '';
    $options['stripe_test_secret_key'] = '';
    $options['stripe_test_publishable_key'] = '';
    $options['stripe_secret_key'] = '';
    $options['stripe_publishable_key'] = '';
    $options['stripe_currency_code'] = '';
    $options['return_url'] = '';
    $options['enable_debug'] = '';
    return $options;
}

function slm_stripe_checkout_get_email_option(){
    $options = get_option('slm_stripe_checkout_email_options');
    if(!is_array($options)){
        $options = slm_stripe_checkout_get_empty_email_options_array();
    }
    return $options;
}

function slm_stripe_checkout_update_email_option($new_options){
    $empty_options = slm_stripe_checkout_get_empty_email_options_array();
    $options = slm_stripe_checkout_get_email_option();
    if(is_array($options)){
        $current_options = array_merge($empty_options, $options);
        $updated_options = array_merge($current_options, $new_options);
        update_option('slm_stripe_checkout_email_options', $updated_options);
    }
    else{
        $updated_options = array_merge($empty_options, $new_options);
        update_option('slm_stripe_checkout_email_options', $updated_options);
    }
}

function slm_stripe_checkout_get_empty_email_options_array(){
    $options = array();
    $options['email_from_name'] = '';
    $options['email_from_address'] = '';
    $options['purchase_email_enabled'] = '';
    $options['purchase_email_subject'] = '';
    $options['purchase_email_type'] = '';
    $options['purchase_email_body'] = '';
    $options['sale_notification_email_enabled'] = '';
    $options['sale_notification_email_recipient'] = '';
    $options['sale_notification_email_subject'] = '';
    $options['sale_notification_email_type'] = '';
    $options['sale_notification_email_body'] = '';
    return $options;
}

function slm_stripe_checkout_set_default_email_options(){
    $options = slm_stripe_checkout_get_email_option();
    $options['purchase_email_type'] = 'plain';
    $options['purchase_email_subject'] = __("Purchase Receipt", "slm-stripe-checkout");
    $purchage_email_body = __("Dear", "slm-stripe-checkout")." {first_name},\n\n";
    $purchage_email_body .= __("Thank you for your purchase. Your purchase details are shown below for your reference:", "slm-stripe-checkout")."\n\n";
    $purchage_email_body .= __("Transaction ID:", "slm-stripe-checkout")." {txn_id}\n";
    $purchage_email_body .= __("Product:", "slm-stripe-checkout")." {product_name}\n";
    $purchage_email_body .= __("Price:", "slm-stripe-checkout")." {currency_code} {price}";
    $options['purchase_email_body'] = $purchage_email_body;
    $options['sale_notification_email_recipient'] = get_bloginfo('admin_email');
    $options['sale_notification_email_subject'] = __("New Customer Order", "slm-stripe-checkout");
    $options['sale_notification_email_type'] = 'plain';
    $sale_notification_email_body = __("Hello", "slm-stripe-checkout")."\n\n";
    $sale_notification_email_body .= __("A purchase has been made.", "slm-stripe-checkout")."\n\n";
    $sale_notification_email_body .= __("Purchased by:", "slm-stripe-checkout")." {full_name}\n";
    $sale_notification_email_body .= __("Product sold:", "slm-stripe-checkout")." {product_name}\n";
    $sale_notification_email_body .= __("Amount:", "slm-stripe-checkout")." {currency_code} {price}\n\n";
    $sale_notification_email_body .= __("Thank you", "slm-stripe-checkout");
    $options['sale_notification_email_body'] = $sale_notification_email_body;
    add_option('slm_stripe_checkout_email_options', $options);
}

function slm_stripe_checkout_debug_log($msg, $success, $end = false) {
    if (!SLM_STRIPE_CHECKOUT_DEBUG) {
        return;
    }
    $date_time = date('F j, Y g:i a');//the_date('F j, Y g:i a', '', '', FALSE);
    $text = '[' . $date_time . '] - ' . (($success) ? 'SUCCESS :' : 'FAILURE :') . $msg . "\n";
    if ($end) {
        $text .= "\n------------------------------------------------------------------\n\n";
    }
    // Write to log.txt file
    $fp = fopen(SLM_STRIPE_CHECKOUT_DEBUG_LOG_PATH, 'a');
    fwrite($fp, $text);
    fclose($fp);  // close file
}

function slm_stripe_checkout_debug_log_array($array_msg, $success, $end = false) {
    if (!SLM_STRIPE_CHECKOUT_DEBUG) {
        return;
    }
    $date_time = date('F j, Y g:i a');//the_date('F j, Y g:i a', '', '', FALSE);
    $text = '[' . $date_time . '] - ' . (($success) ? 'SUCCESS :' : 'FAILURE :') . "\n";
    ob_start();
    print_r($array_msg);
    $var = ob_get_contents();
    ob_end_clean();
    $text .= $var;
    if ($end) {
        $text .= "\n------------------------------------------------------------------\n\n";
    }
    // Write to log.txt file
    $fp = fopen(SLM_STRIPE_CHECKOUT_DEBUG_LOG_PATH, 'a');
    fwrite($fp, $text);
    fclose($fp);  // close filee
}

function slm_stripe_checkout_reset_log() {
    $log_reset = true;
    $date_time = date('F j, Y g:i a');//the_date('F j, Y g:i a', '', '', FALSE);
    $text = '[' . $date_time . '] - SUCCESS : Log reset';
    $text .= "\n------------------------------------------------------------------\n\n";
    $fp = fopen(SLM_STRIPE_CHECKOUT_DEBUG_LOG_PATH, 'w');
    if ($fp != FALSE) {
        @fwrite($fp, $text);
        @fclose($fp);
    } else {
        $log_reset = false;
    }
    return $log_reset;
}
