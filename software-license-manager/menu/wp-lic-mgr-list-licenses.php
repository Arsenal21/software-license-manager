<?php
class WPLM_List_Licenses extends WP_License_Mgr_List_Table {
    
    function __construct(){
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'item',     //singular name of the listed records
            'plural'    => 'items',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
        
    }

    function column_default($item, $column_name){
    	return $item[$column_name];
    }
        
    function column_id($item){
        $row_id = $item['id'];
        $actions = array(
            'edit' => sprintf('<a href="admin.php?page=wp_lic_mgr_addedit&edit_record=%s">Edit</a>', $row_id),
            'delete' => sprintf('<a href="admin.php?page=slm-main&action=delete_license&id=%s" onclick="return confirm(\'Are you sure you want to delete this record?\')">Delete</a>',$row_id),
        );
        return sprintf('%1$s <span style="color:silver"></span>%2$s',
            /*$1%s*/ $item['id'],
            /*$2%s*/ $this->row_actions($actions)
        );
    }

    
    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label
            /*$2%s*/ $item['id']                //The value of the checkbox should be the record's id
       );
    }
    
    function column_active($item){
        if ($item['active'] == 1){
            return 'active';
        } else{
            return 'inactive';
        }
    }

    
    function get_columns(){
        $columns = array(
            'cb' => '<input type="checkbox" />', //Render a checkbox
            'id' => 'ID',
            'license_key' => 'License Key',
            'lic_status' => 'Status',
            'max_allowed_domains' => 'Domains Allowed',
            'email' => 'Registered Email',
            'date_created' => 'Date Created',
            'date_renewed' => 'Date Renewed',
            'date_expiry' => 'Expiry',
        );
        return $columns;
    }
    
    function get_sortable_columns() {
        $sortable_columns = array(
            'id' => array('id',false),
            'license_key' => array('license_key',false),
            'lic_status' => array('lic_status',false),
            'date_created' => array('date_created',false),
            'date_renewed' => array('date_renewed',false),
            'date_expiry' => array('date_expiry',false),
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
        if('delete'===$this->current_action()) 
        {
            //Process delete bulk actions
            if(!isset($_REQUEST['item'])){
                $error_msg = '<p>'.__('Error - Please select some records using the checkboxes', 'slm').'</p>';
                echo '<div id="message" class="error fade">'.$error_msg.'</div>';
                return;
            }else {            
        	$nvp_key = $this->_args['singular'];                
        	$records_to_delete = $_GET[$nvp_key];
                global $wpdb;
                $record_table_name = SLM_TBL_LICENSE_KEYS;//The table name for the records	
        	foreach ($records_to_delete as $row){
                    $sql_query = $wpdb->prepare("DELETE FROM $record_table_name WHERE id=%d", $row);
                    $results = $wpdb->query($sql_query);
        	}
        	echo '<div id="message" class="updated fade"><p>Selected records deleted successfully!</p></div>';
            }
        }
    }
    
    
    /*
     * This function will delete the selected license key entries from the DB.
     * The function accepts either an array of IDs or a single ID
     */
    function delete_licenses($entries)
    {
        global $wpdb;
        $license_table = SLM_TBL_LICENSE_KEYS;
        if (is_array($entries)){
            //Delete multiple records
            $id_list = "(" .implode(",",$entries) .")"; //Create comma separate list for DB operation
            $delete_command = "DELETE FROM ".$license_table." WHERE id IN ".$id_list;
            $result = $wpdb->query($delete_command);
            if($result != NULL)
            {
                $success_msg = '<div id="message" class="updated"><p><strong>';
                $success_msg .= 'The selected entries were deleted successfully!';
                $success_msg .= '</strong></p></div>';
                echo $success_msg;
            }else{
                //TODO - log an error 
            }
        }elseif ($entries != NULL){
            //Delete single record
            $delete_command = "DELETE FROM ".$license_table." WHERE id = '".absint($entries)."'";
            $result = $wpdb->query($delete_command);
            if($result != NULL){
                $success_msg = '<div id="message" class="updated"><p><strong>';
                $success_msg .= 'The selected entry was deleted successfully!';
                $success_msg .= '</strong></p></div>';
                echo $success_msg;
            }else{
                //TODO - log an error 
            }
        }
    }


    function prepare_items() {
        /**
         * First, lets decide how many records per page to show
         */
        $per_page = 50;
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);
        
        $this->process_bulk_action();
    	
    	global $wpdb;
        $license_table = SLM_TBL_LICENSE_KEYS;
        
	/* -- Ordering parameters -- */
	    //Parameters that are going to be used to order the result
	$orderby = !empty($_GET["orderby"]) ? mysql_real_escape_string($_GET["orderby"]) : 'id';
	$order = !empty($_GET["order"]) ? mysql_real_escape_string($_GET["order"]) : 'DESC';

        $data = $wpdb->get_results("SELECT * FROM $license_table ORDER BY $orderby $order", ARRAY_A);
        $current_page = $this->get_pagenum();
        $total_items = count($data);
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);
        $this->items = $data;
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ) );
    }
}