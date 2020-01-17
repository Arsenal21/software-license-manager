<?php

function wp_lic_mgr_manage_licenses_menu() {
	$search_term = ! empty( $_POST['slm_search'] ) ? sanitize_text_field( $_POST['slm_search'] ) : '';
	?>
	<style>
		th#id {
			width: 100px;
		}
		th#license_key {
			width: 250px;
		}
		th#max_allowed_domains {
			width: 75px;
		}
		th#lic_status {
			width: 100px;
		}
		th#date_created {
			width: 125px;
		}
		th#date_renewed {
			width: 125px;
		}
		th#date_expiry {
			width: 125px;
		}
	</style>
	<div class="wrap">
		<h2>Manage Licenses</h2>
		<div id="poststuff">
			<div id="post-body">
				<div class="postbox">
					<h3 class="hndle"><label for="title">License Search</label></h3>
					<div class="inside">
						<p>Search for a license by using email, name, key, domain or transaction ID</p>
						<form method="post" action="">
							<input name="slm_search" type="text" size="40" value="<?php echo esc_attr( $search_term ); ?>" />
							<input type="submit" name="slm_search_btn" class="button" value="Search" />
						</form>
					</div>
				</div>

				<div class="postbox">
					<h3 class="hndle"><label for="title">Software Licenses</label></h3>
					<div class="inside">
						<?php
						include_once 'slm-list-licenses-class.php'; // For rendering the license List Table.
						$license_list = new WPLM_List_Licenses();
						// Do list table form row action tasks.
						if ( isset( $_REQUEST['action'] ) ) {
							// Delete link was clicked for a row in list table.
							if ( isset( $_REQUEST['action'] ) && 'delete_license' === $_REQUEST['action'] ) {
								$license_list->delete_license_key( sanitize_text_field( $_REQUEST['id'] ) );
							}
						}
						// Fetch, prepare, sort, and filter our data...
						$license_list->prepare_items();
						?>
						<form id="tables-filter" method="get" onSubmit="return confirm('Are you sure you want to perform this bulk operation on the selected entries?');">
							<!-- For plugins, we also need to ensure that the form posts back to our current page -->
							<input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
							<!-- Now we can render the completed list table -->
							<?php $license_list->display(); ?>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
}
