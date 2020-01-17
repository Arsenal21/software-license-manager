<?php

function wp_lic_mgr_add_licenses_menu() {
    global $wpdb;
    //initialise some variables
    $id = '';
    $license_key = '';
    $max_domains = 1;
    $license_status = '';
    $first_name = '';
    $last_name = '';
    $email = '';
    $company_name = '';
    $txn_id = '';
    $reset_count = '';
    $created_date = '';
    $renewed_date = '';
    $expiry_date = '';
    $current_date = (date ("Y-m-d"));
    $current_date_plus_1year = date('Y-m-d', strtotime('+1 year'));
    $product_ref = '';
    $subscr_id = '';

    $slm_options = get_option('slm_plugin_options');

    echo '<div class="wrap">';
    echo '<h2>Add/Edit Licenses</h2>';
    echo '<div id="poststuff"><div id="post-body">';

    //If product is being edited, grab current product info
    if (isset($_GET['edit_record'])) {
        $errors = '';
        $id = $_GET['edit_record'];
        $lk_table = SLM_TBL_LICENSE_KEYS;
        $sql_prep = $wpdb->prepare("SELECT * FROM $lk_table WHERE id = %s", $id);
        $record = $wpdb->get_row($sql_prep, OBJECT);
        $license_key = $record->license_key;
        $max_domains = $record->max_allowed_domains;
        $license_status = $record->lic_status;
        $first_name = $record->first_name;
        $last_name = $record->last_name;
        $email = $record->email;
        $company_name = $record->company_name;
        $txn_id = $record->txn_id;
        $reset_count = $record->manual_reset_count;
        $created_date = $record->date_created;
        $renewed_date = $record->date_renewed;
        $expiry_date = $record->date_expiry;
        $product_ref = $record->product_ref;
        $subscr_id = $record->subscr_id;
    }


    if (isset($_POST['save_record'])) {

        //Check nonce
        if ( !isset($_POST['slm_add_edit_nonce_val']) || !wp_verify_nonce($_POST['slm_add_edit_nonce_val'], 'slm_add_edit_nonce_action' )){
            //Nonce check failed.
            wp_die("Error! Nonce verification failed for license save action.");
        }

        do_action('slm_add_edit_interface_save_submission');

        //TODO - do some validation
        $license_key = $_POST['license_key'];
        $max_domains = $_POST['max_allowed_domains'];
        $license_status = $_POST['lic_status'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $email = $_POST['email'];
        $company_name = $_POST['company_name'];
        $txn_id = $_POST['txn_id'];
        $reset_count = $_POST['manual_reset_count'];
        $created_date = $_POST['date_created'];
        $renewed_date = $_POST['date_renewed'];
        $expiry_date = $_POST['date_expiry'];
        $product_ref = $_POST['product_ref'];
        $subscr_id = $_POST['subscr_id'];

        if(empty($created_date)){
            $created_date = $current_date;
        }
        if(empty($renewed_date)){
            $renewed_date = $current_date;
        }
        if(empty($expiry_date)){
            $expiry_date = $current_date_plus_1year;
        }

        //Save the entry to the database
        $fields = array();
        $fields['license_key'] = $license_key;
        $fields['max_allowed_domains'] = $max_domains;
        $fields['lic_status'] = $license_status;
        $fields['first_name'] = $first_name;
        $fields['last_name'] = $last_name;
        $fields['email'] = $email;
        $fields['company_name'] = $company_name;
        $fields['txn_id'] = $txn_id;
        $fields['manual_reset_count'] = $reset_count;
        $fields['date_created'] = $created_date;
        $fields['date_renewed'] = $renewed_date;
        $fields['date_expiry'] = $expiry_date;
        $fields['product_ref'] = $product_ref;
        $fields['subscr_id'] = $subscr_id;

        $id = isset($_POST['edit_record'])?$_POST['edit_record']:'';
        $lk_table = SLM_TBL_LICENSE_KEYS;
        if (empty($id)) {//Insert into database
            $result = $wpdb->insert( $lk_table, $fields);
            $id = $wpdb->insert_id;
            if($result === false){
                $errors .= __('Record could not be inserted into the database!', 'slm');
            }
        } else { //Update record
            $where = array('id'=>$id);
            $updated = $wpdb->update($lk_table, $fields, $where);
            if($updated === false){
                //TODO - log error
                $errors .= __('Update of the license key table failed!', 'slm');
            }
        }

        if(empty($errors)){
            $message = "Record successfully saved!";
            echo '<div id="message" class="updated fade"><p>';
            echo $message;
            echo '</p></div>';
        }else{
            echo '<div id="message" class="error">' . $errors . '</div>';
        }

        $data = array('row_id' => $id, 'key' => $license_key);
        do_action('slm_add_edit_interface_save_record_processed',$data);

    }

	?>
	<style type="text/css">
		.domain-licenses {
			overflow: auto;
			max-height: 400px;
			border: 1px solid #ccc;
		}
		.domain-license-table {
			width: 100%;
		}
		.form-table .domain-license-table td {
			padding: 8px 10px;
		}
		.domain-license-table td.remove-domain {
			width: 15px;
			padding-right: 0;
		}
		.del {
			display: inline-block;
			text-decoration: none;
			cursor: pointer;
			padding: 3px 7px 5px;
			line-height: 1;
			border-radius: 100%;
			color: red;
			transition: 200ms all ease-in-out;
		}
		.del:hover,
		.del:focus,
		.del:active {
			background-color: red;
			color: #fff;
		}
		#reg_del_msg {
			background-color: #666;
			display: inline-block;
			color: white;
			font-weight: bold;
			padding: 3px 15px;
			margin-bottom: 5px;
			border-radius: 3px;
		}
		#reg_del_msg.success {
			background: green;
		}
		#reg_del_msg.error {
			background: red;
		}
	</style>
    You can add a new license or edit an existing one from this interface.
    <br /><br />

    <div class="postbox">
        <h3 class="hndle"><label for="title">License Details </label></h3>
        <div class="inside">

            <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
                <?php wp_nonce_field('slm_add_edit_nonce_action', 'slm_add_edit_nonce_val' ) ?>
                <table class="form-table">

                    <?php
                    if ($id != '') {
                        echo '<input name="edit_record" type="hidden" value="' . $id . '" />';
                    } else {
                        if(!isset($editing_record)){//Create an empty object
                            $editing_record = new stdClass();
                        }
                        //Auto generate unique key
                        $lic_key_prefix = $slm_options['lic_prefix'];
                        if (!empty($lic_key_prefix)) {
                            $license_key = uniqid($lic_key_prefix);
                        } else {
                            $license_key = uniqid();
                        }
                    }
                    ?>

                    <tr valign="top">
                        <th scope="row">License Key</th>
                        <td><input name="license_key" type="text" id="license_key" value="<?php echo $license_key; ?>" size="30" />
                            <br/>The unique license key. When adding a new record it automatically generates a unique key in this field for you. You can change this value to customize the key if you like.</td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Maximum Allowed Domains</th>
                        <td><input name="max_allowed_domains" type="text" id="max_allowed_domains" value="<?php echo $max_domains; ?>" size="5" /><br/>Number of domains/installs in which this license can be used.</td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">License Status</th>
                        <td>
                            <select name="lic_status">
                                <option value="pending" <?php if ($license_status == 'pending') echo 'selected="selected"'; ?> >Pending</option>
                                <option value="active" <?php if ($license_status == 'active') echo 'selected="selected"'; ?> >Active</option>
                                <option value="blocked" <?php if ($license_status == 'blocked') echo 'selected="selected"'; ?> >Blocked</option>
                                <option value="expired" <?php if ($license_status == 'expired') echo 'selected="selected"'; ?> >Expired</option>
                            </select>
                        </td></tr>

					<?php
					if ( '' != $id ) :
						global $wpdb;
						$reg_table   = SLM_TBL_LIC_DOMAIN;
						$sql_prep    = $wpdb->prepare( "SELECT * FROM `$reg_table` WHERE `lic_key_id` = %s", $id );
						$reg_domains = $wpdb->get_results( $sql_prep, OBJECT );
						?>
						<tr valign="top">
							<th scope="row">Registered Domains</th>
							<td>
								<?php if ( count( $reg_domains ) > 0 ) { ?>
									<div id="reg_del_msg" style="display: none;"></div>
									<div class="domain-licenses">
										<table cellpadding="0" cellspacing="0" class="domain-license-table">
											<?php
											$count = 0;
											foreach ( $reg_domains as $reg_domain ) :
												?>
												<tr <?php echo ( $count % 2 ) ? 'class="alternate"' : ''; ?>>
													<td class="remove-domain"><a class="del" id="<?php echo esc_attr( $reg_domain->id ); ?>" href="#remove-domain">&times;</a></td>
													<td><?php echo esc_html( $reg_domain->registered_domain ); ?></td>
												</tr>
												<?php
												$count++;
											endforeach;
											?>
										</table>
									</div>
									<?php
								} else {
									echo esc_html__( 'No domains activated.', 'slm' );
								}
								?>
							</td>
						</tr>
					<?php endif; ?>

                    <tr valign="top">
                        <th scope="row">First Name</th>
                        <td><input name="first_name" type="text" id="first_name" value="<?php echo $first_name; ?>" size="20" /><br/>License user's first name</td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Last Name</th>
                        <td><input name="last_name" type="text" id="last_name" value="<?php echo $last_name; ?>" size="20" /><br/>License user's last name</td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Email Address</th>
                        <td><input name="email" type="text" id="email" value="<?php echo $email; ?>" size="30" /><br/>License user's email address</td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Company Name</th>
                        <td><input name="company_name" type="text" id="company_name" value="<?php echo $company_name; ?>" size="30" /><br/>License user's company name</td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Unique Transaction ID</th>
                        <td><input name="txn_id" type="text" id="txn_id" value="<?php echo $txn_id; ?>" size="30" /><br/>The unique transaction ID associated with this license key</td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Manual Reset Count</th>
                        <td><input name="manual_reset_count" type="text" id="manual_reset_count" value="<?php echo $reset_count; ?>" size="6" />
                            <br/>The number of times this license has been manually reset by the admin (use it if you want to keep track of it). It can be helpful for the admin to keep track of manual reset counts.</td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Date Created</th>
                        <td><input name="date_created" type="text" id="date_created" class="wplm_pick_date" value="<?php echo $created_date; ?>" size="10" />
                            <br/>Creation date of license.</td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Date Renewed</th>
                        <td><input name="date_renewed" type="text" id="date_renewed" class="wplm_pick_date" value="<?php echo $renewed_date; ?>" size="10" />
                            <br/>Renewal date of license.</td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Date of Expiry</th>
                        <td><input name="date_expiry" type="text" id="date_expiry" class="wplm_pick_date" value="<?php echo $expiry_date; ?>" size="10" />
                            <br/>Expiry date of license.</td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Product Reference</th>
                        <td><input name="product_ref" type="text" id="product_ref" value="<?php echo $product_ref; ?>" size="30" />
                            <br/>The product that this license applies to (if any).</td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Subscriber ID</th>
                        <td><input name="subscr_id" type="text" id="subscr_id" value="<?php echo $subscr_id; ?>" size="50" />
                            <br/>The Subscriber ID (if any). Can be useful if you are using the license key with a recurring payment plan.</td>
                    </tr>

                </table>

                <?php
                $data = array('row_id' => $id, 'key' => $license_key);
                $extra_output = apply_filters('slm_add_edit_interface_above_submit','', $data);
                if(!empty($extra_output)){
                    echo $extra_output;
                }
                ?>

                <div class="submit">
                    <input type="submit" class="button-primary" name="save_record" value="Save Record" />
                </div>
            </form>
        </div></div>
    <a href="admin.php?page=<?php echo SLM_MAIN_MENU_SLUG; ?>" class="button">Manage Licenses</a><br /><br />
    </div></div>
    </div>

	<script type="text/javascript">
	jQuery( function( $ ) {
		$( '.del' ).on( 'click', function( e ) {
			e.preventDefault();

			var $link = $( this );

			if ( ! confirm( 'Are you sure you want to remove this domain?' ) ) {
				$link.blur();
				return false;
			}

			var $spinner = $( '<span />' ).addClass( 'spinner' ).css( 'visibility', 'visible' ).css( 'margin', '0 0 0 2px' );
			$link.before( $spinner ).hide();

			var id = $link.attr( 'id' ),
				$msg = $( '#reg_del_msg' );

			$msg.html( 'Loading ...' ).show();

			$.get(
				'<?php echo esc_html( admin_url( 'admin-ajax.php' ) ); ?>' + '?action=del_reistered_domain&id=' + id,
				function( data ) {
					if ( 'success' == data ) {
						$msg.addClass( 'success' ).html( 'Deleted' );

						var $tr = $link.parents( 'tr:first' );
						$tr.fadeOut( 'fast', function() {
							$tr.remove();

							// Check if any more rows exist.
							if ( ! $( '.domain-license-table tbody tr' ).length ) {
								var $none  =$( '<p />' ).html( 'No domains activated.' ).hide();
								$( '.domain-licenses' ).after( $none ).hide();
								$none.fadeIn( 'fast' );
							} else {
								// Restripe table.
								$( '.domain-license-table tbody tr.alternate' ).removeClass( 'alternate' );
								$( '.domain-license-table tbody tr:even' ).addClass( 'alternate' );
							}
						} );
					} else {
						$msg.addClass( 'error' ).html( 'Failed' );
					}
				} // ajax callback function.
			); // get/ajax.
		}); // click event.
	}); // document ready.
	</script>
	<?php
}
