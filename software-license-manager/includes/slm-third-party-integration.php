<?php

/* * ********************************* */
/* * * WP eStore Plugin Integration ** */
/* * ********************************* */
add_filter( 'eStore_notification_email_body_filter', 'slm_handle_estore_email_body_filter', 10, 3 ); //Standard sale notification email
add_filter( 'eStore_squeeze_form_email_body_filter', 'slm_handle_estore_email_body_filter', 10, 3 ); //Squeeze form email

function slm_handle_estore_email_body_filter( $body, $payment_data, $cart_items ) {
	global $slm_debug_logger, $wpdb;
	$slm_debug_logger->log_debug( 'WP eStore integration - checking if a license key needs to be created for this transaction.' );
	$products_table_name = $wpdb->prefix . 'wp_eStore_tbl';
	$slm_data            = '';

	//Check if this is a recurring payment.
	if ( function_exists( 'is_paypal_recurring_payment' ) ) {
		$recurring_payment = is_paypal_recurring_payment( $payment_data );
		if ( $recurring_payment ) {
			$slm_debug_logger->log_debug( 'This is a recurring payment. No need to create a new license key.' );
			do_action( 'slm_estore_recurring_payment_received', $payment_data, $cart_items );
			return $body;
		}
	}

	foreach ( $cart_items as $current_cart_item ) {
		$prod_id   = $current_cart_item['item_number'];
		$item_name = $current_cart_item['item_name'];
		$quantity  = $current_cart_item['quantity'];
		if ( empty( $quantity ) ) {
			$quantity = 1;
		}
		$slm_debug_logger->log_debug( 'License Manager - Item Number: ' . $prod_id . ', Quantity: ' . $quantity . ', Item Name: ' . $item_name );

		$retrieved_product = $wpdb->get_row( "SELECT * FROM $products_table_name WHERE id = '$prod_id'", OBJECT );
		$package_product   = eStore_is_package_product( $retrieved_product );
		if ( $package_product ) {
			$slm_debug_logger->log_debug( 'Checking license key generation for package/bundle product.' );
			$product_ids = explode( ',', $retrieved_product->product_download_url );
			foreach ( $product_ids as $id ) {
				$id                                = trim( $id );
				$retrieved_product_for_specific_id = $wpdb->get_row( "SELECT * FROM $products_table_name WHERE id = '$id'", OBJECT );
				//$slm_data .= slm_estore_check_and_generate_key($retrieved_product_for_specific_id, $payment_data, $cart_items, $item_name);
				$slm_data .= slm_estore_check_and_create_key_for_qty( $retrieved_product_for_specific_id, $payment_data, $cart_items, $item_name, $quantity );
			}
		} else {
			$slm_debug_logger->log_debug( 'Checking license key generation for single item product.' );
			$slm_data .= slm_estore_check_and_create_key_for_qty( $retrieved_product, $payment_data, $cart_items, $item_name, $quantity );
		}
	}

	$body = str_replace( '{slm_data}', $slm_data, $body );
	return $body;
}

function slm_estore_check_and_create_key_for_qty( $retrieved_product, $payment_data, $cart_items, $item_name, $quantity ) {
	$prod_key_data = '';
	for ( $i = 0; $i < $quantity; $i++ ) {
		$prod_key_data .= slm_estore_check_and_generate_key( $retrieved_product, $payment_data, $cart_items, $item_name );
	}
	return $prod_key_data;
}

function slm_estore_check_and_generate_key( $retrieved_product, $payment_data, $cart_items, $item_name ) {
	global $slm_debug_logger;
	$license_data = '';

	if ( $retrieved_product->create_license == 1 ) {
		$slm_debug_logger->log_debug( 'Need to create a license key for this product (' . $retrieved_product->id . ')' );
		$slm_key      = slm_estore_create_license( $retrieved_product, $payment_data, $cart_items, $item_name );
		$license_data = "\n" . __( 'Item Name: ', 'slm' ) . $retrieved_product->name . ' - ' . __( 'License Key: ', 'slm' ) . $slm_key;
		$slm_debug_logger->log_debug( 'Liense data: ' . $license_data );
		$license_data = apply_filters( 'slm_estore_item_license_data', $license_data );
	}
	return $license_data;
}

function slm_estore_create_license( $retrieved_product, $payment_data, $cart_items, $item_name ) {
	global $slm_debug_logger;
	global $wpdb;
	$product_meta_table_name = WP_ESTORE_PRODUCTS_META_TABLE_NAME;

	//Retrieve the default settings values.
	$options        = get_option( 'slm_plugin_options' );
	$lic_key_prefix = $options['lic_prefix'];
	$max_domains    = $options['default_max_domains'];

	//Lets check any product specific configuration.
	$prod_id      = $retrieved_product->id;
	$product_meta = $wpdb->get_row( "SELECT * FROM $product_meta_table_name WHERE prod_id = '$prod_id' AND meta_key='slm_max_allowed_domains'", OBJECT );
	if ( $product_meta ) {
		//Found product specific SLM config data.
		$max_domains = $product_meta->meta_value;
	} else {
		//Use the default value from settings (the $max_domains variable contains the default value already).
	}
        
        //Use the default value (1 year from today). If a product specific one is set, it will be overriden later.
        $current_date_plus_1year = date( 'Y-m-d', strtotime( '+1 year' ) );
        $slm_date_of_expiry      = $current_date_plus_1year;

	//Lets check if any product specific expiry date is set
	$product_meta = $wpdb->get_row( "SELECT * FROM $product_meta_table_name WHERE prod_id = '$prod_id' AND meta_key='slm_date_of_expiry'", OBJECT );       
	if ( $product_meta ) {
		//Found product specific SLM config data. Override the expiry date using the product specific configuration.
		$num_days_before_expiry = $product_meta->meta_value;
		$slm_date_of_expiry     = date( 'Y-m-d', strtotime( '+' . $num_days_before_expiry . ' days' ) );
	}

        //Get emember ID from custom fields (if available)
        $customvariables = isset($payment_data['custom']) ? eStore_get_payment_custom_var($payment_data['custom']) : array();
        $emember_id = isset($customvariables['eMember_id']) ? $customvariables['eMember_id'] : '';

        //Create the fields array
	$fields                        = array();
	$fields['license_key']         = uniqid( $lic_key_prefix );
	$fields['lic_status']          = 'pending';
	$fields['first_name']          = $payment_data['first_name'];
	$fields['last_name']           = $payment_data['last_name'];
	$fields['email']               = $payment_data['payer_email'];
	$fields['company_name']        = $payment_data['company_name'];
	$fields['txn_id']              = $payment_data['txn_id'];
	$fields['max_allowed_domains'] = $max_domains;
	$fields['date_created']        = date( 'Y-m-d' ); //Today's date
	$fields['date_expiry']         = $slm_date_of_expiry;
	$fields['product_ref']         = $prod_id;//WP eStore product ID
	$fields['subscr_id']           = isset( $payment_data['subscr_id'] ) ? $payment_data['subscr_id'] : '';
        $fields['user_ref']            = $emember_id;//WP eMember member ID (if available)

	$slm_debug_logger->log_debug( 'Inserting license data into the license manager DB table.' );
	$fields = array_filter( $fields ); //Remove any null values.

	$tbl_name = SLM_TBL_LICENSE_KEYS;
	$result   = $wpdb->insert( $tbl_name, $fields );
	if ( ! $result ) {
		$slm_debug_logger->log_debug( 'Notice! initial database table insert failed on license key table (User Email: ' . $fields['email'] . '). Trying again by converting charset', true );
		//Convert the default PayPal IPN charset to UTF-8 format
		$first_name             = mb_convert_encoding( $fields['first_name'], 'UTF-8', 'windows-1252' );
		$fields['first_name']   = esc_sql( $first_name );
		$last_name              = mb_convert_encoding( $fields['last_name'], 'UTF-8', 'windows-1252' );
		$fields['last_name']    = esc_sql( $last_name );
		$company_name           = mb_convert_encoding( $fields['company_name'], 'UTF-8', 'windows-1252' );
		$fields['company_name'] = esc_sql( $company_name );

		$result = $wpdb->insert( $tbl_name, $fields );
		if ( ! $result ) {
			$slm_debug_logger->log_debug( 'Error! Failed to update license key table. DB insert query failed.', false );
		}
	}
	//SLM_API_Utility::insert_license_data_internal($fields);

	$prod_args = array(
		'estore_prod_id'   => $prod_id,
		'estore_item_name' => $item_name,
	);
	do_action( 'slm_estore_license_created', $prod_args, $payment_data, $cart_items, $fields );

	return $fields['license_key'];
}

/* Code to handle the eStore's product add/edit interface for SLM specific product configuration */
add_filter( 'eStore_addon_product_settings_filter', 'slm_estore_product_configuration_html', 10, 2 ); //Render the product add/edit HTML
add_action( 'eStore_new_product_added', 'slm_estore_new_product_added', 10, 2 ); //Handle the DB insert after a product add.
add_action( 'eStore_product_updated', 'slm_estore_product_updated', 10, 2 ); //Handle the DB update after a product edit.
add_action( 'eStore_product_deleted', 'slm_estore_product_deleted' ); //Handle the DB delete after a product delete.

function slm_estore_product_configuration_html( $product_config_html, $prod_id ) {
	global $wpdb;
	$product_meta_table_name = WP_ESTORE_PRODUCTS_META_TABLE_NAME;

	if ( empty( $prod_id ) ) {
		//New product add
		$slm_max_allowed_domains = '';
		$slm_date_of_expiry      = '';
	} else {
		//Existing product edit

		//Retrieve the max domain value
		$product_meta = $wpdb->get_row( "SELECT * FROM $product_meta_table_name WHERE prod_id = '$prod_id' AND meta_key='slm_max_allowed_domains'", OBJECT );
		if ( $product_meta ) {
			$slm_max_allowed_domains = $product_meta->meta_value;
		} else {
			$slm_max_allowed_domains = '';
		}

		//Retrieve the expiry date value
		$product_meta = $wpdb->get_row( "SELECT * FROM $product_meta_table_name WHERE prod_id = '$prod_id' AND meta_key='slm_date_of_expiry'", OBJECT );
		if ( $product_meta ) {
			$slm_date_of_expiry = $product_meta->meta_value;
		} else {
			$slm_date_of_expiry = '';
		}
	}

	$product_config_html .= '<div class="msg_head">Software License Manager Plugin (Click to Expand)</div><div class="msg_body"><table class="form-table">';

	$product_config_html .= '<tr valign="top"><th scope="row">Maximum Allowed Domains</th><td>';
	$product_config_html .= '<input name="slm_max_allowed_domains" type="text" id="slm_max_allowed_domains" value="' . $slm_max_allowed_domains . '" size="10" />';
	$product_config_html .= '<p class="description">Number of domains/installs in which this license can be used. Leave blank if you wish to use the default value set in the license manager plugin settings.</p>';
	$product_config_html .= '</td></tr>';

	$product_config_html .= '<tr valign="top"><th scope="row">Number of Days before Expiry</th><td>';
	$product_config_html .= '<input name="slm_date_of_expiry" type="text" id="slm_date_of_expiry" value="' . $slm_date_of_expiry . '" size="10" /> Days';
	$product_config_html .= '<p class="description">Number of days before expiry. The expiry date of the license will be set based on this value. For example, if you want the key to expire in 6 months then enter a value of 180.</p>';
	$product_config_html .= '</td></tr>';

	$product_config_html .= '</table></div>';

	return $product_config_html;
}

function slm_estore_new_product_added( $prod_dat_array, $prod_id ) {
	global $wpdb;
	$product_meta_table_name = WP_ESTORE_PRODUCTS_META_TABLE_NAME;

	//Save max domain value
	$fields               = array();
	$fields['prod_id']    = $prod_id;
	$fields['meta_key']   = 'slm_max_allowed_domains';
	$fields['meta_value'] = $prod_dat_array['slm_max_allowed_domains'];
	$result               = $wpdb->insert( $product_meta_table_name, $fields );
	if ( ! $result ) {
		//insert query failed
	}

	//Save expiry date value
	$fields               = array();
	$fields['prod_id']    = $prod_id;
	$fields['meta_key']   = 'slm_date_of_expiry';
	$fields['meta_value'] = $prod_dat_array['slm_date_of_expiry'];
	$result               = $wpdb->insert( $product_meta_table_name, $fields );
	if ( ! $result ) {
		//insert query failed
	}

}

function slm_estore_product_updated( $prod_dat_array, $prod_id ) {
	global $wpdb;
	$product_meta_table_name = WP_ESTORE_PRODUCTS_META_TABLE_NAME;

	//Find the existing value for the max domains field (for the given product)
	$product_meta = $wpdb->get_row( "SELECT * FROM $product_meta_table_name WHERE prod_id = '$prod_id' AND meta_key='slm_max_allowed_domains'", OBJECT );
	if ( $product_meta ) {
		//Found existing value so lets update it
		//Better to do specific update (so the other meta values for example "download_limit_count" doesn't get set to empty).
		$meta_key_name = 'slm_max_allowed_domains';
		$meta_value    = $prod_dat_array['slm_max_allowed_domains'];
		$update_db_qry = "UPDATE $product_meta_table_name SET meta_value='$meta_value' WHERE prod_id='$prod_id' AND meta_key='$meta_key_name'";
		$results       = $wpdb->query( $update_db_qry );

	} else {
		//No value for this field was there so lets insert one.
		$fields               = array();
		$fields['prod_id']    = $prod_id;
		$fields['meta_key']   = 'slm_max_allowed_domains';
		$fields['meta_value'] = $prod_dat_array['slm_max_allowed_domains'];
		$result               = $wpdb->insert( $product_meta_table_name, $fields );
	}

	//Find the existing value for the expiry date field (for the given product)
	$product_meta = $wpdb->get_row( "SELECT * FROM $product_meta_table_name WHERE prod_id = '$prod_id' AND meta_key='slm_date_of_expiry'", OBJECT );
	if ( $product_meta ) {
		//Found existing value so lets update it
		//Better to do specific update (so the other meta values for example "download_limit_count" doesn't get set to empty).
		$meta_key_name = 'slm_date_of_expiry';
		$meta_value    = $prod_dat_array['slm_date_of_expiry'];
		$update_db_qry = "UPDATE $product_meta_table_name SET meta_value='$meta_value' WHERE prod_id='$prod_id' AND meta_key='$meta_key_name'";
		$results       = $wpdb->query( $update_db_qry );

	} else {
		//No value for this field was there so lets insert one.
		$fields               = array();
		$fields['prod_id']    = $prod_id;
		$fields['meta_key']   = 'slm_date_of_expiry';
		$fields['meta_value'] = $prod_dat_array['slm_date_of_expiry'];
		$result               = $wpdb->insert( $product_meta_table_name, $fields );
	}

}

function slm_estore_product_deleted( $prod_id ) {
	global $wpdb;
	$product_meta_table_name = WP_ESTORE_PRODUCTS_META_TABLE_NAME;

	$result = $wpdb->delete(
		$product_meta_table_name,
		array(
			'prod_id'  => $prod_id,
			'meta_key' => 'slm_max_allowed_domains',
		)
	);
	$result = $wpdb->delete(
		$product_meta_table_name,
		array(
			'prod_id'  => $prod_id,
			'meta_key' => 'slm_date_of_expiry',
		)
	);
}

/************************************/
/*** End of WP eStore integration ***/
/************************************/


/*********************************************/
/*** WP eMember Plugin Integration Related ***/
/*********************************************/

add_shortcode( 'emember_show_slm_license_key', 'handle_emember_show_slm_license_key');

function handle_emember_show_slm_license_key( $args ){
    if ( !class_exists('Emember_Auth')) {
        return "Error! WP eMember plugin is not active";
    }

    $emember_auth = Emember_Auth::getInstance();
    if ( !$emember_auth->isLoggedIn() ) {
        return "You are not logged into the site. Please log in.";
    }
    global $wpdb;
    $output = "";

    //The member is logged-in. Find the WP eMember ID.
    $member_id = $emember_auth->getUserInfo('member_id');
    $lk_table = SLM_TBL_LICENSE_KEYS;
    $sql_prep = $wpdb->prepare( "SELECT * FROM $lk_table WHERE user_ref = %s", $member_id );
    $record = $wpdb->get_row( $sql_prep, OBJECT );

    if ( $record ){
        $license_key = $record->license_key;
        $output .= '<div class="emember_slm_license_key">Your license key is: ' . $license_key . '</div>';
    } else {
        //Could not find a record
        $output .= '<div class="emember_slm_license_key_not_found">Could not find a key for your account.</div>';
    }

    return $output;
}