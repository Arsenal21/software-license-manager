<?php

function wp_lic_mgr_manage_licenses_menu() {
	include_once 'slm-list-licenses-class.php'; // For rendering the license List Table.
	$license_list = new WPLM_List_Licenses();
	// Do list table form row action tasks.

        $action = isset( $_GET['action'] ) ? sanitize_text_field( stripslashes ( $_GET['action'] ) ) : '';

	if ( ! empty( $action ) ) {
		$id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
		if ( ! empty( $id ) ) {
			$id = intval( $id );
			// Nonce check
			check_admin_referer( 'slm-delete-license-' . $id );
		}
	}

	switch ( $action ) {
		case 'delete_license':
			// Delete link was clicked for a row in list table.
			if ( ! empty( $id ) ) {
				$license_list->delete_license_key( intval( $id ) );
			}
			break;
		default:
			break;
	}

        $page = isset( $_GET['page'] ) ? sanitize_text_field( stripslashes ( $_GET['page'] ) ) : '';

	// Fetch, prepare, sort, and filter our data...
	$license_list->prepare_items();

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
	<h2>Manage Licenses
		<a href="<?php echo esc_attr( add_query_arg( 'page', 'wp_lic_mgr_addedit', get_admin_url( null, 'admin.php' ) ) ); ?>" class="page-title-action">Add New License</a>
	</h2>
	<div id="poststuff">
		<div id="post-body">
			<form id="tables-filter" method="get">
				<?php wp_nonce_field( 'slm_license_list_actions', 'slm_license_list_actions' ); ?>

				<?php $license_list->search_box( 'Search', 'slm_search' ); ?>

				<div class="postbox">
					<h3 class="hndle"><label for="title">Software Licenses</label></h3>
					<div class="inside">
						<!-- For plugins, we also need to ensure that the form posts back to our current page -->
						<input type="hidden" name="page" value="<?php echo esc_attr( $page ); ?>" />
						<!-- Now we can render the completed list table -->
						<?php $license_list->display(); ?>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
<script>
jQuery('input#doaction').click(function(e) {
	return confirm('Are you sure you want to perform this bulk operation on the selected entries?');
});
</script>
	<?php
}
