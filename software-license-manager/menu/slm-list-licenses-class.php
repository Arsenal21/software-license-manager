<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WPLM_List_Licenses extends WP_List_Table {

	function __construct() {
		global $status, $page;

		//Set parent defaults
		parent::__construct(
			array(
				'singular' => 'item',     //singular name of the listed records
				'plural'   => 'items',    //plural name of the listed records
				'ajax'     => false,        //does this table support ajax?
			)
		);

	}

	function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	function column_id( $item ) {
		$row_id  = $item['id'];
		$actions = array(
			'edit'   => sprintf( '<a href="admin.php?page=wp_lic_mgr_addedit&edit_record=%s">Edit</a>', $row_id ),
			'delete' => sprintf(
				'<a href="admin.php?page=slm-main&action=delete_license&id=%s&_wpnonce=%s" onclick="return confirm(\'Are you sure you want to delete this record?\')">Delete</a>',
				$row_id,
				wp_create_nonce( 'slm-delete-license-' . $row_id )
			),
		);
		return sprintf(
			'%1$s <span style="color:silver"></span>%2$s',
			/*$1%s*/ $item['id'],
			/*$2%s*/ $this->row_actions( $actions )
		);
	}


	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label
			/*$2%s*/ $item['id']                //The value of the checkbox should be the record's id
		);
	}

	function column_active( $item ) {
		if ( $item['active'] == 1 ) {
			return 'active';
		} else {
			return 'inactive';
		}
	}

	function get_columns() {
		$columns = array(
			'cb'                  => '<input type="checkbox" />', // Render a checkbox.
			'id'                  => 'ID',
			'license_key'         => 'License Key',
			'lic_status'          => 'Status',
			'max_allowed_domains' => 'Domains',
			'email'               => 'Registered Email',
			'date_created'        => 'Date Created',
			'date_renewed'        => 'Date Renewed',
			'date_expiry'         => 'Expiry',
			'product_ref'         => 'Product Reference',
		);
		return $columns;
	}

	function get_sortable_columns() {
		$sortable_columns = array(
			'id'           => array( 'id', false ),
			'license_key'  => array( 'license_key', false ),
			'lic_status'   => array( 'lic_status', false ),
			'date_created' => array( 'date_created', false ),
			'date_renewed' => array( 'date_renewed', false ),
			'date_expiry'  => array( 'date_expiry', false ),
		);
		return $sortable_columns;
	}

	function get_bulk_actions() {
		$actions = array(
			'delete' => 'Delete',
		);
		return $actions;
	}

	function process_bulk_action() {
		if ( 'delete' === $this->current_action() ) {
			check_admin_referer( 'bulk-' . $this->_args['plural'] );
			//Process delete bulk actions
			if ( ! isset( $_REQUEST['item'] ) ) {
				$error_msg = __( 'Error - Please select some records using the checkboxes', 'slm' );
				echo '<div id="message" class="error fade"><p>' . esc_html( $error_msg ) . '</p></div>';
				return;
			} else {
				$nvp_key           = $this->_args['singular'];
				$records_to_delete = filter_input( INPUT_GET, $nvp_key, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
				foreach ( $records_to_delete as $row ) {
					SLM_Utility::delete_license_key_by_row_id( $row );
				}
				echo '<div id="message" class="updated fade"><p>Selected records deleted successfully!</p></div>';
			}
		}
	}


	/*
	 * This function will delete the selected license key entries from the DB.
	 */
	function delete_license_key( $key_row_id ) {
		SLM_Utility::delete_license_key_by_row_id( $key_row_id );
		echo '<div id="message" class="updated"><p><strong>';
		echo 'The selected entry was deleted successfully!';
		echo '</strong></p></div>';
	}

	function search_box( $text, $input_id ) {
		if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) {
			return;
		}

		$input_id = $input_id . '-search-input';

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '" />';
		}
		if ( ! empty( $_REQUEST['order'] ) ) {
			echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '" />';
		}
		if ( ! empty( $_REQUEST['post_mime_type'] ) ) {
			echo '<input type="hidden" name="post_mime_type" value="' . esc_attr( $_REQUEST['post_mime_type'] ) . '" />';
		}
		if ( ! empty( $_REQUEST['detached'] ) ) {
			echo '<input type="hidden" name="detached" value="' . esc_attr( $_REQUEST['detached'] ) . '" />';
		}
		?>

<div class="postbox">
	<h3 class="hndle"><label for="title">License Search</label></h3>
	<div class="inside">
		<p>Search for a license by using email, name, key, domain or transaction ID</p>
		<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $text ); ?>:</label>
		<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" size="40" value="<?php _admin_search_query(); ?>" />
		<?php submit_button( $text, 'button', false, false, array( 'id' => 'search-submit' ) ); ?>
	</div>
</div>


		<?php
	}

	function prepare_items() {
		/**
		 * First, lets decide how many records per page to show
		 */
		$per_page     = 50;
		$current_page = $this->get_pagenum();
		$columns      = $this->get_columns();
		$hidden       = array();
		$sortable     = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->process_bulk_action();

		global $wpdb;
		$license_table = SLM_TBL_LICENSE_KEYS;
		$domain_table  = SLM_TBL_LIC_DOMAIN;

		/**
		 * Ordering parameters:
		 * Parameters that are going to be used to order the result.
		 */
		$orderby = ! empty( $_GET['orderby'] ) ? wp_strip_all_tags( $_GET['orderby'] ) : 'id';
		$order   = ! empty( $_GET['order'] ) ? wp_strip_all_tags( $_GET['order'] ) : 'DESC';

		$order_str = sanitize_sql_orderby( $orderby . ' ' . $order );

		$limit_from = ( $current_page - 1 ) * $per_page;

		if ( ! empty( $_REQUEST['s'] ) ) {
			$search_term = trim( sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) );
			$placeholder = '%' . $wpdb->esc_like( $search_term ) . '%';

			$select = "SELECT `lk` . * , CONCAT( COUNT( `rd` . `lic_key_id` ), '/', `lk` . `max_allowed_domains` ) AS `max_allowed_domains`";

			$after_select = "FROM `$license_table` `lk`
			LEFT JOIN `$domain_table` `rd` ON `lk`.`id` = `rd`.`lic_key_id`
			WHERE `lk`.`license_key` LIKE %s
			OR `lk`.`email` LIKE %s
			OR `lk`.`txn_id` LIKE %s
			OR `lk`.`first_name` LIKE %s
			OR `lk`.`last_name` LIKE %s
			OR `rd`.`registered_domain` LIKE %s";

			$after_query = "GROUP BY `lk` . `id` ORDER BY $order_str
			LIMIT $limit_from, $per_page";

			$q = "$select $after_select $after_query";

			$data = $wpdb->get_results(
				$wpdb->prepare(
					$q,
					$placeholder,
					$placeholder,
					$placeholder,
					$placeholder,
					$placeholder,
					$placeholder
				),
				ARRAY_A
			);

                        // SQL query for counting the total number of distinct license keys.
                        // Use COUNT(DISTINCT lk.id): This ensures that you're counting the number of distinct license keys (not the number of domains)
                        $found_rows_q = $wpdb->prepare(
                            "SELECT COUNT(DISTINCT `lk`.`id`)
                             $after_select",
                            $placeholder,
                            $placeholder,
                            $placeholder,
                            $placeholder,
                            $placeholder,
                            $placeholder
                        );

                        // Get the total number of items.
			$total_items = intval( $wpdb->get_var( $found_rows_q ) );
		} else {
			$after_select = "FROM `$license_table` `lk`
			LEFT JOIN `$domain_table` `rd`
			ON `lk` . `id` = `rd` . `lic_key_id`";

			$after_query = "GROUP BY `lk` . `id`
			ORDER BY $order_str
			LIMIT $limit_from, $per_page";

			$q = "SELECT `lk` . * ,
				CONCAT( COUNT( `rd` . `lic_key_id` ), '/', `lk` . `max_allowed_domains` ) as `max_allowed_domains`
				$after_select$after_query";

			$data = $wpdb->get_results( $q, ARRAY_A );

                        // SQL query for counting the total number of distinct license keys.
			//$found_rows_q = "SELECT COUNT( * ) $after_select";//Old query.
                        //Use COUNT(DISTINCT lk.id): This ensures that you're counting the number of distinct license keys (not the number of domains)
                        $found_rows_q = "SELECT COUNT(DISTINCT `lk`.`id`) $after_select";

                        // Get the total number of items.
			$total_items = intval( $wpdb->get_var( $found_rows_q ) );
		}

		$this->items = $data;
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,                     // WE have to calculate the total number of items.
				'per_page'    => $per_page,                        // WE have to determine how many items to show on a page.
				'total_pages' => ceil( $total_items / $per_page ), // WE have to calculate the total number of pages.
			)
		);
	}
}
