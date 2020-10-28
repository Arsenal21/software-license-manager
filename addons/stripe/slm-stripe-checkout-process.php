<?php

function slm_stripe_checkout_process_webhook(){
    if(!isset($_REQUEST['slm_stripe_co_webhook']))
    {
        return;
    }
    //status_header(200);
    http_response_code(200);
    // retrieve the request's body and parse it as JSON
    $body = @file_get_contents('php://input');
    // grab the event information
    $event_json = json_decode($body);

    $allowed_events = array("checkout.session.completed"); //add event types that we want to handle
    if (!in_array($event_json->type, $allowed_events))
    {
        return;
    }
    slm_stripe_checkout_debug_log("Received event notification from Stripe. Event type: ".$event_json->type, true);
    slm_stripe_checkout_debug_log_array($event_json, true);
    $client_reference_id = sanitize_text_field($event_json->data->object->client_reference_id);
    if(!isset($client_reference_id) || empty($client_reference_id)){
        slm_stripe_checkout_debug_log("Client Reference ID could not be found. This notification cannot be processed.", false);
        return;
    }
    if(strpos($client_reference_id, 'wpsc') === false){
        slm_stripe_checkout_debug_log("This payment was not initiated by the Stripe checkout plugin.", false);
        return;
    }
    $payment_data = array();
    $subscription_id = sanitize_text_field($event_json->data->object->subscription);
    if(isset($subscription_id) && !empty($subscription_id)){
        $payment_data['txn_id'] = $subscription_id;
        slm_stripe_checkout_debug_log("This notification is for a subscription payment.", true);
        $payment_data['stripe_customer_id'] = sanitize_text_field($event_json->data->object->customer);
        if(!isset($payment_data['stripe_customer_id']) || empty($payment_data['stripe_customer_id'])){
            slm_stripe_checkout_debug_log("Customer ID could not be found. This notification cannot be processed.", false);
            return;
        }
        $customers = WP_SC_Stripe_API::retrieve('customers/'.$payment_data['stripe_customer_id']);
        $payment_data['customer_email'] = sanitize_text_field($customers->email);
        if(!isset($payment_data['customer_email']) || empty($payment_data['customer_email'])){
            slm_stripe_checkout_debug_log("Customer email could not be found. This notification cannot be processed.", false);
            return;
        }
        $subscriptions = WP_SC_Stripe_API::retrieve('subscriptions/'.$subscription_id);
        $product_id = sanitize_text_field($subscriptions->plan->product);
        if(!isset($product_id) || empty($product_id)){
            slm_stripe_checkout_debug_log("Product ID could not be found. This notification cannot be processed.", false);
            return;
        }
        $products = WP_SC_Stripe_API::retrieve('products/'.$product_id);
        $payment_data['product_name'] = sanitize_text_field($products->name);
        $amount = sanitize_text_field($event_json->data->object->amount_total);
        $payment_data['price'] = $amount/100;
        $currency = sanitize_text_field($event_json->data->object->currency);
        $payment_data['currency_code'] = strtoupper($currency);

        $payment_method_id = sanitize_text_field($subscriptions->default_payment_method);
        if(!isset($payment_method_id) || empty($payment_method_id)){
            slm_stripe_checkout_debug_log("Payment method could not be found. This notification cannot be processed.", false);
            return;
        }
        $payment_methods = WP_SC_Stripe_API::retrieve('payment_methods/'.$payment_method_id);
        $billing_name = $payment_methods->billing_details->name;
        $payment_data['billing_name'] = isset($billing_name) && !empty($billing_name) ? sanitize_text_field($billing_name) : '';
        $payment_data['billing_first_name'] = '';
        $payment_data['billing_last_name'] = '';
        if(!empty($payment_data['billing_name'])){
            $billing_name_parts = explode(" ", $payment_data['billing_name']);
            $payment_data['billing_first_name'] = isset($billing_name_parts[0]) && !empty($billing_name_parts[0]) ? $billing_name_parts[0] : '';
            $payment_data['billing_last_name'] = isset($billing_name_parts[1]) && !empty($billing_name_parts[1]) ? array_pop($billing_name_parts) : '';
        }
        $address_line1 = $payment_methods->billing_details->address->line1;
        $payment_data['billing_address_line1'] = isset($address_line1) && !empty($address_line1) ? sanitize_text_field($address_line1) : '';
        $address_zip = $payment_methods->billing_details->address->postal_code;
        $payment_data['billing_address_zip'] = isset($address_zip) && !empty($address_zip) ? sanitize_text_field($address_zip) : '';
        $address_state = $payment_methods->billing_details->address->state;
        $payment_data['billing_address_state'] = isset($address_state) && !empty($address_state) ? sanitize_text_field($address_state) : '';
        $address_city = $payment_methods->billing_details->address->city;
        $payment_data['billing_address_city'] = isset($address_city) && !empty($address_city) ? sanitize_text_field($address_city) : '';
        $address_country = $payment_methods->billing_details->address->country;
        $payment_data['billing_address_country'] = isset($address_country) && !empty($address_country) ? sanitize_text_field($address_country) : '';
    }
    else{
        $payment_intent_id = $event_json->data->object->payment_intent;
        if(!isset($payment_intent_id) || empty($payment_intent_id)){
            slm_stripe_checkout_debug_log("Payment Intent ID could not be found. This notification cannot be processed.", false);
            return;
        }

        $payment_intent = WP_SC_Stripe_API::retrieve('payment_intents/'.$payment_intent_id);

        $payment_data['product_name'] = sanitize_text_field($payment_intent->charges->data[0]->description);
        $amount = sanitize_text_field($payment_intent->charges->data[0]->amount);
        $payment_data['price'] = $amount/100;
        $currency = sanitize_text_field($payment_intent->charges->data[0]->currency);
        $payment_data['currency_code'] = strtoupper($currency);

        $billing_name = $payment_intent->charges->data[0]->billing_details->name;
        $payment_data['billing_name'] = isset($billing_name) && !empty($billing_name) ? sanitize_text_field($billing_name) : '';
        $payment_data['billing_first_name'] = '';
        $payment_data['billing_last_name'] = '';
        if(!empty($payment_data['billing_name'])){
            $billing_name_parts = explode(" ", $payment_data['billing_name']);
            $payment_data['billing_first_name'] = isset($billing_name_parts[0]) && !empty($billing_name_parts[0]) ? $billing_name_parts[0] : '';
            $payment_data['billing_last_name'] = isset($billing_name_parts[1]) && !empty($billing_name_parts[1]) ? array_pop($billing_name_parts) : '';
        }
        $address_line1 = $payment_intent->charges->data[0]->billing_details->address->line1;
        $payment_data['billing_address_line1'] = isset($address_line1) && !empty($address_line1) ? sanitize_text_field($address_line1) : '';
        $address_zip = $payment_intent->charges->data[0]->billing_details->address->postal_code;
        $payment_data['billing_address_zip'] = isset($address_zip) && !empty($address_zip) ? sanitize_text_field($address_zip) : '';
        $address_state = $payment_intent->charges->data[0]->billing_details->address->state;
        $payment_data['billing_address_state'] = isset($address_state) && !empty($address_state) ? sanitize_text_field($address_state) : '';
        $address_city = $payment_intent->charges->data[0]->billing_details->address->city;
        $payment_data['billing_address_city'] = isset($address_city) && !empty($address_city) ? sanitize_text_field($address_city) : '';
        $address_country = $payment_intent->charges->data[0]->billing_details->address->country;
        $payment_data['billing_address_country'] = isset($address_country) && !empty($address_country) ? sanitize_text_field($address_country) : '';
        $customer_email = $payment_intent->charges->data[0]->billing_details->email;
        $payment_data['customer_email'] = sanitize_email($customer_email);
        $payment_data['stripe_customer_id'] = sanitize_text_field($event_json->data->object->customer);
        //process data
        $txn_id = sanitize_text_field($payment_intent->charges->data[0]->id);
        if(!isset($txn_id) || empty($txn_id)){
            $txn_id = $payment_intent_id;
        }
        $payment_data['txn_id'] = $txn_id;
    }
    $args = array(
        'post_type' => 'slmstripeco_order',
        'meta_query' => array(
            array(
                'key' => '_txn_id',
                'value' => $payment_data['txn_id'],
                'compare' => '=',
            ),
        ),
    );
    $query = new WP_Query($args);
    if ($query->have_posts()) {  //a record already exists
        slm_stripe_checkout_debug_log("An order with this transaction ID already exists. This payment will not be processed.", false);
        return;
    }
    $content = '';
    $content .= '<strong>Transaction ID:</strong> '.$payment_data['txn_id'].'<br />';
    $content .= '<strong>Product name:</strong> '.$payment_data['product_name'].'<br />';
    $content .= '<strong>Amount:</strong> '.$payment_data['price'].'<br />';
    $content .= '<strong>Currency:</strong> '.$payment_data['currency_code'].'<br />';
    if(!empty($payment_data['billing_name'])){
        $content .= '<strong>Billing Name:</strong> '.$payment_data['billing_name'].'<br />';
    }
    if(!empty($payment_data['customer_email'])){
        $content .= '<strong>Email:</strong> '.$payment_data['customer_email'].'<br />';
    }
    if(!empty($payment_data['stripe_customer_id'])){
        $content .= '<strong>Stripe Customer ID:</strong> '.$payment_data['stripe_customer_id'].'<br />';
    }
    if(!empty($payment_data['billing_address_line1'])){
        $content .= '<strong>Billing Address:</strong> '.$payment_data['billing_address_line1'];
        if(!empty($payment_data['billing_address_city'])){
            $content .= ', '.$payment_data['billing_address_city'];
        }
        if(!empty($payment_data['billing_address_state'])){
            $content .= ', '.$payment_data['billing_address_state'];
        }
        if(!empty($payment_data['billing_address_zip'])){
            $content .= ', '.$payment_data['billing_address_zip'];
        }
        if(!empty($payment_data['billing_address_country'])){
            $content .= ', '.$payment_data['billing_address_country'];
        }
        $content .= '<br />';
    }
    $payment_data['order_id'] = '';
    $slm_stripe_checkout_order = array(
        'post_title' => 'order',
        'post_type' => 'slmstripeco_order',
        'post_content' => '',
        'post_status' => 'publish',
    );
    slm_stripe_checkout_debug_log("Updating order information", true);
    $post_id = wp_insert_post($slm_stripe_checkout_order);  //insert a new order
    $post_updated = false;
    if ($post_id > 0) {
        $post_content = $content;
        $updated_post = array(
            'ID' => $post_id,
            'post_title' => $post_id,
            'post_type' => 'slmstripeco_order',
            'post_content' => $post_content
        );
        $updated_post_id = wp_update_post($updated_post);  //update the order
        if ($updated_post_id > 0) {  //successfully updated
            $post_updated = true;
        }
    }
    //save order information
    if ($post_updated) {
        $payment_data['order_id'] = $post_id;
        update_post_meta($post_id, '_txn_id', $payment_data['txn_id']);
        update_post_meta($post_id, '_name', $payment_data['billing_name']);
        update_post_meta($post_id, '_amount', $payment_data['price']);
        update_post_meta($post_id, '_email', $payment_data['customer_email']);
        slm_stripe_checkout_debug_log("Order information updated", true);
        $email_options = slm_stripe_checkout_get_email_option();
        add_filter('wp_mail_from', 'slm_stripe_checkout_set_email_from');
        add_filter('wp_mail_from_name', 'slm_stripe_checkout_set_email_from_name');
        if(isset($email_options['purchase_email_enabled']) && !empty($email_options['purchase_email_enabled']) && !empty($payment_data['customer_email'])){
            $subject = $email_options['purchase_email_subject'];
            $type = $email_options['purchase_email_type'];
            $body = $email_options['purchase_email_body'];
            $body = slm_stripe_checkout_do_email_tags($payment_data, $body);
            if($type == "html"){
                add_filter('wp_mail_content_type', 'slm_stripe_checkout_set_html_email_content_type');
                $body = apply_filters('slm_stripe_checkout_email_body_wpautop', true) ? wpautop($body) : $body;
            }
            slm_stripe_checkout_debug_log("Sending a purchase receipt email to ".$payment_data['customer_email'], true);
            $mail_sent = wp_mail($payment_data['customer_email'], $subject, $body);
            if($type == "html"){
                remove_filter('wp_mail_content_type', 'slm_stripe_checkout_set_html_email_content_type');
            }
            if($mail_sent == true){
                slm_stripe_checkout_debug_log("Email was sent successfully by WordPress", true);
            }
            else{
                slm_stripe_checkout_debug_log("Email could not be sent by WordPress", false);
            }
        }
        if(isset($email_options['sale_notification_email_enabled']) && !empty($email_options['sale_notification_email_enabled']) && !empty($email_options['sale_notification_email_recipient'])){
            $subject = $email_options['sale_notification_email_subject'];
            $type = $email_options['sale_notification_email_type'];
            $body = $email_options['sale_notification_email_body'];
            $body = slm_stripe_checkout_do_email_tags($payment_data, $body);
            if($type == "html"){
                add_filter('wp_mail_content_type', 'slm_stripe_checkout_set_html_email_content_type');
                $body = apply_filters('slm_stripe_checkout_email_body_wpautop', true) ? wpautop($body) : $body;
            }
            slm_stripe_checkout_debug_log("Sending a sale notification email to ".$email_options['sale_notification_email_recipient'], true);
            $mail_sent = wp_mail($email_options['sale_notification_email_recipient'], $subject, $body);
            if($type == "html"){
                remove_filter('wp_mail_content_type', 'slm_stripe_checkout_set_html_email_content_type');
            }
            if($mail_sent == true){
                slm_stripe_checkout_debug_log("Email was sent successfully by WordPress", true);
            }
            else{
                slm_stripe_checkout_debug_log("Email could not be sent by WordPress", false);
            }
        }
        remove_filter('wp_mail_from', 'slm_stripe_checkout_set_email_from');
        remove_filter('wp_mail_from_name', 'slm_stripe_checkout_set_email_from_name');
        do_action('slmstripecheckout_order_processed', $post_id);
    } else {
        slm_stripe_checkout_debug_log("Order information could not be updated", false);
        return;
    }
    slm_stripe_checkout_debug_log("Oder processing completed", true, true);
    do_action('slmstripecheckout_payment_completed', $payment_data);
}

function slm_stripe_checkout_process_order() {
    if (!isset($_POST['slm_stripe_checkout_legacy']) && !isset($_POST['slm_stripe_checkout_legacy'])) {
        return;
    }
    if (!isset($_POST['stripeToken']) && !isset($_POST['stripeTokenType'])) {
        return;
    }
    $nonce = $_REQUEST['_wpnonce'];
    if ( !wp_verify_nonce($nonce, 'slm_stripe_checkout_legacy')){
        $error_msg = __('Error! Nonce Security Check Failed!', 'wp-stripe-checkout');
        wp_die($error_msg);
    }
    $_POST = stripslashes_deep($_POST);
    $stripeToken = sanitize_text_field($_POST['stripeToken']);
    if (empty($stripeToken)) {
        $error_msg = __('Please make sure your card details have been entered correctly and that your browser supports JavaScript.', 'wp-stripe-checkout');
        $error_msg .= ' ' . __('Please also make sure that you are including jQuery and there are no JavaScript errors on the page.', 'wp-stripe-checkout');
        wp_die($error_msg);
    }
    if (!isset($_POST['item_name']) || empty($_POST['item_name'])) {
        $error_msg = __('Product name could not be found.', 'wp-stripe-checkout');
        wp_die($error_msg);
    }
    $payment_data = array();
    $payment_data['product_name'] = sanitize_text_field($_POST['item_name']);
    /*
    $transient_name = 'slmstripecheckout-amount-' . sanitize_title_with_dashes($payment_data['product_name']);
    $payment_data['price'] = get_transient($transient_name);
    if(!isset($payment_data['price']) || !is_numeric($payment_data['price'])){
        $error_msg = __('Product price could not be found.', 'wp-stripe-checkout');
        wp_die($error_msg);
    }
    $transient_name = 'slmstripecheckout-currency-' . sanitize_title_with_dashes($payment_data['product_name']);
    $payment_data['currency_code'] = get_transient($transient_name);
    if(!isset($payment_data['currency_code']) || empty($payment_data['currency_code'])){
        $error_msg = __('Currency could not be found.', 'wp-stripe-checkout');
        wp_die($error_msg);
    }
    */
    if (!isset($_POST['item_price']) || !is_numeric($_POST['item_price'])) {
        $error_msg = __('Product price could not be found.', 'wp-stripe-checkout');
        wp_die($error_msg);
    }
    $payment_data['price'] = sanitize_text_field($_POST['item_price']);
    if (!isset($_POST['item_amount']) || !is_numeric($_POST['item_amount'])) {
        $error_msg = __('Product amount could not be found.', 'wp-stripe-checkout');
        wp_die($error_msg);
    }
    $payment_data['amount'] = sanitize_text_field($_POST['item_amount']);
    if (!isset($_POST['item_currency']) || empty($_POST['item_currency'])) {
        $error_msg = __('Currency could not be found.', 'wp-stripe-checkout');
        wp_die($error_msg);
    }
    $payment_data['currency_code'] = sanitize_text_field($_POST['item_currency']);
    $payment_data['product_description'] = '';
    if(isset($_POST['item_description']) && !empty($_POST['item_description'])){
        $payment_data['product_description'] = sanitize_text_field($_POST['item_description']);
    }
    $success_url = '';
    if (isset($_POST['success_url']) && !empty($_POST['success_url'])) {
        $success_url = esc_url_raw($_POST['success_url']);
    }
    $payment_data['billing_name'] = isset($_POST['stripeBillingName']) && !empty($_POST['stripeBillingName']) ? sanitize_text_field($_POST['stripeBillingName']) : '';
    $customer_description = '';
    $payment_data['billing_first_name'] = '';
    $payment_data['billing_last_name'] = '';
    if(!empty($payment_data['billing_name'])){
        $customer_description = __('Name', 'wp-stripe-checkout').': '.$payment_data['billing_name'];
        $billing_name_parts = explode(" ", $payment_data['billing_name']);
        $payment_data['billing_first_name'] = isset($billing_name_parts[0]) && !empty($billing_name_parts[0]) ? $billing_name_parts[0] : '';
        $payment_data['billing_last_name'] = isset($billing_name_parts[1]) && !empty($billing_name_parts[1]) ? array_pop($billing_name_parts) : '';
    }
    $payment_data['billing_address_line1'] = isset($_POST['stripeBillingAddressLine1']) && !empty($_POST['stripeBillingAddressLine1']) ? sanitize_text_field($_POST['stripeBillingAddressLine1']) : '';
    $payment_data['billing_address_zip'] = isset($_POST['stripeBillingAddressZip']) && !empty($_POST['stripeBillingAddressZip']) ? sanitize_text_field($_POST['stripeBillingAddressZip']) : '';
    $payment_data['billing_address_state'] = isset($_POST['stripeBillingAddressState']) && !empty($_POST['stripeBillingAddressState']) ? sanitize_text_field($_POST['stripeBillingAddressState']) : '';
    $payment_data['billing_address_city'] = isset($_POST['stripeBillingAddressCity']) && !empty($_POST['stripeBillingAddressCity']) ? sanitize_text_field($_POST['stripeBillingAddressCity']) : '';
    $payment_data['billing_address_country'] = isset($_POST['stripeBillingAddressCountry']) && !empty($_POST['stripeBillingAddressCountry']) ? sanitize_text_field($_POST['stripeBillingAddressCountry']) : '';
    $payment_data['shipping_name'] = isset($_POST['stripeShippingName']) && !empty($_POST['stripeShippingName']) ? sanitize_text_field($_POST['stripeShippingName']) : '';
    $payment_data['shipping_first_name'] = '';
    $payment_data['shipping_last_name'] = '';
    if(!empty($payment_data['shipping_name'])){
        $shipping_name_parts = explode(" ", $payment_data['shipping_name']);
        $payment_data['shipping_first_name'] = isset($shipping_name_parts[0]) && !empty($shipping_name_parts[0]) ? $shipping_name_parts[0] : '';
        $payment_data['shipping_last_name'] = isset($shipping_name_parts[1]) && !empty($shipping_name_parts[1]) ? array_pop($shipping_name_parts) : '';
    }
    $payment_data['shipping_address_line1'] = isset($_POST['stripeShippingAddressLine1']) && !empty($_POST['stripeShippingAddressLine1']) ? sanitize_text_field($_POST['stripeShippingAddressLine1']) : '';
    $payment_data['shipping_address_zip'] = isset($_POST['stripeShippingAddressZip']) && !empty($_POST['stripeShippingAddressZip']) ? sanitize_text_field($_POST['stripeShippingAddressZip']) : '';
    $payment_data['shipping_address_state'] = isset($_POST['stripeShippingAddressState']) && !empty($_POST['stripeShippingAddressState']) ? sanitize_text_field($_POST['stripeShippingAddressState']) : '';
    $payment_data['shipping_address_city'] = isset($_POST['stripeShippingAddressCity']) && !empty($_POST['stripeShippingAddressCity']) ? sanitize_text_field($_POST['stripeShippingAddressCity']) : '';
    $payment_data['shipping_address_country'] = isset($_POST['stripeShippingAddressCountry']) && !empty($_POST['stripeShippingAddressCountry']) ? sanitize_text_field($_POST['stripeShippingAddressCountry']) : '';
    slm_stripe_checkout_debug_log("Post Data", true);
    slm_stripe_checkout_debug_log_array($_POST, true);
    // Other charge data
    $post_data['currency'] = strtolower($payment_data['currency_code']);
    $post_data['amount'] = $payment_data['amount']; //$payment_data['price'] * 100;
    $post_data['description'] = $payment_data['product_description'];
    $post_data['capture'] = 'true';
    $payment_data['customer_email'] = '';
    if(isset($_POST['stripeEmail'])) {
        $payment_data['customer_email'] = sanitize_email($_POST['stripeEmail']);
        $post_data['receipt_email'] = $payment_data['customer_email'];
        //create a Stripe customer
        $customer_args = array(
                'email'       => $payment_data['customer_email'],
                'description' => $customer_description,
                'source' => $stripeToken,
        );
        slm_stripe_checkout_debug_log("Creating a Stripe customer", true);
        $response = WP_SC_Stripe_API::request($customer_args, 'customers');
        slm_stripe_checkout_debug_log("Response Data", true);
        slm_stripe_checkout_debug_log(print_r($response, true), true);
        $post_data['customer'] = $response->id;
    }
    //only specify a source if no customber is created
    if(!isset($post_data['customer'])) {
        $post_data['source'] = $stripeToken;
    }
    $post_data['expand[]'] = 'balance_transaction';

    // Make the request
    slm_stripe_checkout_debug_log("Creating a charge request", true);
    $response = WP_SC_Stripe_API::request($post_data);
    slm_stripe_checkout_debug_log("Response Data", true);
    slm_stripe_checkout_debug_log(print_r($response, true), true);
    //process data
    $payment_data['txn_id'] = $response->id;
    $args = array(
        'post_type' => 'slmstripeco_order',
        'meta_query' => array(
            array(
                'key' => '_txn_id',
                'value' => $payment_data['txn_id'],
                'compare' => '=',
            ),
        ),
    );
    $query = new WP_Query($args);
    if ($query->have_posts()) {  //a record already exists
        slm_stripe_checkout_debug_log("An order with this transaction ID already exists. This payment will not be processed.", false);
        return;
    }
    $content = '';
    $content .= '<strong>Transaction ID:</strong> '.$payment_data['txn_id'].'<br />';
    $content .= '<strong>Product name:</strong> '.$payment_data['product_name'].'<br />';
    $content .= '<strong>Amount:</strong> '.$payment_data['price'].'<br />';
    $content .= '<strong>Currency:</strong> '.$payment_data['currency_code'].'<br />';
    if(!empty($payment_data['billing_name'])){
        $content .= '<strong>Billing Name:</strong> '.$payment_data['billing_name'].'<br />';
    }
    if(!empty($payment_data['customer_email'])){
        $content .= '<strong>Email:</strong> '.$payment_data['customer_email'].'<br />';
    }
    if(!empty($payment_data['billing_address_line1'])){
        $content .= '<strong>Billing Address:</strong> '.$payment_data['billing_address_line1'];
        if(!empty($payment_data['billing_address_city'])){
            $content .= ', '.$payment_data['billing_address_city'];
        }
        if(!empty($payment_data['billing_address_state'])){
            $content .= ', '.$payment_data['billing_address_state'];
        }
        if(!empty($payment_data['billing_address_zip'])){
            $content .= ', '.$payment_data['billing_address_zip'];
        }
        if(!empty($payment_data['billing_address_country'])){
            $content .= ', '.$payment_data['billing_address_country'];
        }
        $content .= '<br />';
    }
    if(!empty($payment_data['shipping_address_line1'])){
        $content .= '<strong>Shipping Address:</strong> '.$payment_data['shipping_address_line1'];
        if(!empty($payment_data['shipping_address_city'])){
            $content .= ', '.$payment_data['shipping_address_city'];
        }
        if(!empty($payment_data['shipping_address_state'])){
            $content .= ', '.$payment_data['shipping_address_state'];
        }
        if(!empty($payment_data['shipping_address_zip'])){
            $content .= ', '.$payment_data['shipping_address_zip'];
        }
        if(!empty($payment_data['shipping_address_country'])){
            $content .= ', '.$payment_data['shipping_address_country'];
        }
        $content .= '<br />';
    }
    $payment_data['order_id'] = '';
    $slm_stripe_checkout_order = array(
        'post_title' => 'order',
        'post_type' => 'slmstripeco_order',
        'post_content' => '',
        'post_status' => 'publish',
    );
    slm_stripe_checkout_debug_log("Updating order information", true);
    $post_id = wp_insert_post($slm_stripe_checkout_order);  //insert a new order
    $post_updated = false;
    if ($post_id > 0) {
        $post_content = $content;
        $updated_post = array(
            'ID' => $post_id,
            'post_title' => $post_id,
            'post_type' => 'slmstripeco_order',
            'post_content' => $post_content
        );
        $updated_post_id = wp_update_post($updated_post);  //update the order
        if ($updated_post_id > 0) {  //successfully updated
            $post_updated = true;
        }
    }
    //save order information
    if ($post_updated) {
        $payment_data['order_id'] = $post_id;
        update_post_meta($post_id, '_txn_id', $payment_data['txn_id']);
        update_post_meta($post_id, '_name', $payment_data['billing_name']);
        update_post_meta($post_id, '_amount', $payment_data['price']);
        update_post_meta($post_id, '_email', $payment_data['customer_email']);
        slm_stripe_checkout_debug_log("Order information updated", true);
        $email_options = slm_stripe_checkout_get_email_option();
        add_filter('wp_mail_from', 'slm_stripe_checkout_set_email_from');
        add_filter('wp_mail_from_name', 'slm_stripe_checkout_set_email_from_name');
        if(isset($email_options['purchase_email_enabled']) && !empty($email_options['purchase_email_enabled']) && !empty($payment_data['customer_email'])){
            $subject = $email_options['purchase_email_subject'];
            $type = $email_options['purchase_email_type'];
            $body = $email_options['purchase_email_body'];
            $body = slm_stripe_checkout_do_email_tags($payment_data, $body);
            if($type == "html"){
                add_filter('wp_mail_content_type', 'slm_stripe_checkout_set_html_email_content_type');
                $body = apply_filters('slm_stripe_checkout_email_body_wpautop', true) ? wpautop($body) : $body;
            }
            slm_stripe_checkout_debug_log("Sending a purchase receipt email to ".$payment_data['customer_email'], true);
            $mail_sent = wp_mail($payment_data['customer_email'], $subject, $body);
            if($type == "html"){
                remove_filter('wp_mail_content_type', 'slm_stripe_checkout_set_html_email_content_type');
            }
            if($mail_sent == true){
                slm_stripe_checkout_debug_log("Email was sent successfully by WordPress", true);
            }
            else{
                slm_stripe_checkout_debug_log("Email could not be sent by WordPress", false);
            }
        }
        if(isset($email_options['sale_notification_email_enabled']) && !empty($email_options['sale_notification_email_enabled']) && !empty($email_options['sale_notification_email_recipient'])){
            $subject = $email_options['sale_notification_email_subject'];
            $type = $email_options['sale_notification_email_type'];
            $body = $email_options['sale_notification_email_body'];
            $body = slm_stripe_checkout_do_email_tags($payment_data, $body);
            if($type == "html"){
                add_filter('wp_mail_content_type', 'slm_stripe_checkout_set_html_email_content_type');
                $body = apply_filters('slm_stripe_checkout_email_body_wpautop', true) ? wpautop($body) : $body;
            }
            slm_stripe_checkout_debug_log("Sending a sale notification email to ".$email_options['sale_notification_email_recipient'], true);
            $mail_sent = wp_mail($email_options['sale_notification_email_recipient'], $subject, $body);
            if($type == "html"){
                remove_filter('wp_mail_content_type', 'slm_stripe_checkout_set_html_email_content_type');
            }
            if($mail_sent == true){
                slm_stripe_checkout_debug_log("Email was sent successfully by WordPress", true);
            }
            else{
                slm_stripe_checkout_debug_log("Email could not be sent by WordPress", false);
            }
        }
        remove_filter('wp_mail_from', 'slm_stripe_checkout_set_email_from');
        remove_filter('wp_mail_from_name', 'slm_stripe_checkout_set_email_from_name');
        do_action('slmstripecheckout_order_processed', $post_id);
    } else {
        slm_stripe_checkout_debug_log("Order information could not be updated", false);
        return;
    }
    slm_stripe_checkout_debug_log("Oder processing completed", true, true);
    do_action('slmstripecheckout_payment_completed', $payment_data);
    $stripe_options = slm_stripe_checkout_get_option();
    if(!empty($success_url)){
        wp_safe_redirect($success_url);
        exit;
    }
    else if(isset($stripe_options['return_url']) && !empty($stripe_options['return_url'])){
        wp_safe_redirect($stripe_options['return_url']);
        exit;
    }
}

function slm_stripe_checkout_do_email_tags($payment_data, $content){
    $search = array(
        '{first_name}',
        '{last_name}',
        '{full_name}',
        '{txn_id}',
        '{product_name}',
        '{currency_code}',
        '{price}',
        '{customer_email}'
    );
    $replace = array(
        $payment_data['billing_first_name'],
        $payment_data['billing_last_name'],
        $payment_data['billing_name'],
        $payment_data['txn_id'],
        $payment_data['product_name'],
        $payment_data['currency_code'],
        $payment_data['price'],
        $payment_data['customer_email']
    );
    $content = str_replace($search, $replace, $content);
    return $content;
}

function slm_stripe_checkout_set_email_from($from){
    $email_options = slm_stripe_checkout_get_email_option();
    if(isset($email_options['email_from_address']) && !empty($email_options['email_from_address'])){
        $from = $email_options['email_from_address'];
    }
    return $from;
}

function slm_stripe_checkout_set_email_from_name($from_name){
    $email_options = slm_stripe_checkout_get_email_option();
    if(isset($email_options['email_from_name']) && !empty($email_options['email_from_name'])){
        $from_name = $email_options['email_from_name'];
    }
    return $from_name;
}

function slm_stripe_checkout_set_html_email_content_type($content_type){
    $content_type = 'text/html';
    return $content_type;
}