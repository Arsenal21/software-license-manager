<?php

if (!defined('WPINC')) {
    die;
}

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class SLM_List_Licenses extends WP_List_Table
{

    function __construct()
    {
        global $status, $page;
        //Set parent defaults
        parent::__construct(array(
            'singular'  => 'item',     //singular name of the listed records
            'plural'    => 'items',    //plural name of the listed records
            'ajax'      => false       //does this table support ajax?
        ));
    }

    public function no_items()
    {
        esc_html_e('No licenses avaliable.', 'slm-plus');
    }

    function get_views()
    {

        $base = admin_url('admin.php?page=slm_overview');
        $current = isset($_GET['view']) ? $_GET['view'] : '';

        $link_html = '<a href="%s"%s>%s</a>(%s)';

        $views = array(
            'all'      => sprintf(
                $link_html,
                esc_url(remove_query_arg('view', $base)),
                $current === 'all' || $current == '' ? ' class="current"' : '',
                esc_html__('All', 'slm-plus'),
                SLM_Utility::get_total_licenses()
            ),
            'active'   => sprintf(
                $link_html,
                esc_url(add_query_arg('view', 'active', $base . '&s=active')),
                $current === 'active' ? ' class="current"' : '',
                esc_html__('active', 'slm-plus'),
                SLM_Utility::count_licenses('active')
            ),
            'pending' => sprintf(
                $link_html,
                esc_url(add_query_arg('view', 'pending', $base . '&s=pending')),
                $current === 'pending' ? ' class="current"' : '',
                esc_html__('pending', 'slm-plus'),
                SLM_Utility::count_licenses('pending')
            ),
            'expired'  => sprintf(
                $link_html,
                esc_url(add_query_arg('view', 'expired', $base . '&s=expired')),
                $current === 'expired' ? ' class="current"' : '',
                esc_html__('expired', 'slm-plus'),
                SLM_Utility::count_licenses('expired')
            ),
            'blocked'  => sprintf(
                $link_html,
                esc_url(add_query_arg('view', 'blocked', $base . '&s=blocked')),
                $current === 'blocked' ? ' class="current"' : '',
                esc_html__('blocked', 'slm-plus'),
                SLM_Utility::count_licenses('blocked')
            )
        );

        return $views;
    }


    function get_columns()
    {
        $columns = array(
            'cb'                    => '<input type="checkbox" />', //Render a checkbox
            'id'                    => __('ID', 'slm-plus'),
            'lic_status'            => __('Status', 'slm-plus'),
            'license_key'           => __('Key', 'slm-plus'),
            'item_reference'        => __('Item reference', 'slm-plus'),
            'lic_type'              => __('License type', 'slm-plus'),
            'email'                 => __('Email', 'slm-plus'),
            'max_allowed_domains'   => __('Domains', 'slm-plus'),
            'max_allowed_devices'   => __('Devices', 'slm-plus'),
            'purchase_id_'          => __('Order #', 'slm-plus'),
            'date_created'          => __('Created on', 'slm-plus'),
            'date_renewed'          => __('Renewed on', 'slm-plus'),
            'date_activated'        => __('Activated on', 'slm-plus'),
            'date_expiry'           => __('Expiration', 'slm-plus'),
            'until'                 => __('Until Ver.', 'slm-plus'),
            'current_ver'           => __('Current Ver.', 'slm-plus')
        );
        return $columns;
    }

    function column_default($item, $column_name)
    {
        switch ($column_name) {

            case 'lic_status':
                return '<span class="slm-lic-' . $item[$column_name] . '"> <span class="slm-status ' . $item[$column_name] . '"></span>'  . $item[$column_name] . '</span>';
                break;

            case 'email':
                return '<a href="' . admin_url('admin.php?page=slm_subscribers&slm_subscriber_edit=true&manage_subscriber=' . $item['id'] . '&email=' . $item[$column_name] . '') . '">' . $item[$column_name] . ' </a>';
                break;

            case 'date_expiry':
                $expiration = $item[$column_name];
                $date_today = time();

                if ($expiration == '0000-00-00') {
                    return '<span class="tag license-date-valid">' . __(' Lifetime ', 'slm-plus') . '</span>' . '<span class="days-left"> </span>';
                }


                if ($expiration != '0000-00-00') {
                    if (strtotime($expiration) < time()) {
                        return '<span class="slm-lic-expired-date"> ' . $expiration . '  </span>' . '<span class="days-left"> ' . SLM_Utility::get_days_remaining($expiration) . ' day(s) due</span>';
                    } else {
                        return '<span class="tag license-date-valid">' . $item[$column_name] . '</span>' . '<span class="days-left"> ' . SLM_Utility::get_days_remaining($expiration) . ' day(s) left</span>';
                    }
                } else {
                    //return $item[$column_name];
                    return '<span class="tag license-date-null">not set<span>';
                }
                break;

            default:
                return $item[$column_name];
        }
    }

    function column_id($item)
    {
        $row_id = $item['id'];
        $actions = array(
            'edit'      => sprintf('<a class="left" href="admin.php?page=slm_manage_license&edit_record=%s">Edit</a>', $row_id),
            'delete'    => sprintf('<a href="admin.php?page=slm_overview&action=delete_license&id=%s" onclick="return confirm(\'Are you sure you want to delete this record?\')">Delete</a>', $row_id),
        );
        return sprintf(
            ' <span style="color:black"> %1$s </span>%2$s',
            /*$1%s*/
            $item['id'],
            /*$2%s*/
            $this->row_actions($actions)
        );
    }

    function column_active($item)
    {
        if ($item['active'] == 1) {
            return 'active';
        } else {
            return 'inactive';
        }
    }

    function column_cb($item)
    {

        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/
            $this->_args['singular'],  //Let's simply repurpose the table's singular label
            /*$2%s*/
            $item['id']                //The value of the checkbox should be the record's id
        );
    }

    function get_sortable_columns()
    {
        $sortable_columns = array(
            'id'            => array('id', true),
            'email'         => array('email', true),
            'lic_type'      => array('lic_type', true),
            'until'         => array('until', true),
            'current_ver'   => array('current_ver', true),
            'lic_status'    => array('lic_status', true),
            'item_reference' => array('item_reference', true),
        );

        return $sortable_columns;
    }

    function get_bulk_actions()
    {
        $actions = array(
            'delete'    => 'Delete',
            'blocked'   => 'Block',
            'expired'   => 'Expire',
            'active'    => 'Activate',
            // 'reminder'  => 'Send Reminder',
            'export'    => 'Export',
        );
        return $actions;
    }

    function process_bulk_action()
    {
        if ('delete' === $this->current_action()) {
            //Process delete bulk actions
            if (!isset($_REQUEST['item'])) {
                $error_msg = '<p>' . __('Error - Please select some records using the checkboxes', 'slm-plus') . '</p>';
                echo '<div id="message" class="error fade">' . esc_html($error_msg) . '</div>';
                return;
            } else {
                $nvp_key            = $this->_args['singular'];
                $records_to_delete  = $_GET[$nvp_key];

                foreach ($records_to_delete as $row) {
                    SLM_Utility::delete_license_key_by_row_id($row);
                }

                echo '<div id="message" class="updated fade"><p>Selected records deleted successfully!</p></div>';
            }
        }

        if ('blocked' === $this->current_action()) {
            //Process blocked bulk actions
            if (!isset($_REQUEST['item'])) {
                $error_msg = '<p>' . __('Error - Please select some records using the checkboxes', 'slm-plus') . '</p>';
                echo '<div id="message" class="error fade">' . esc_html($error_msg) . '</div>';
                return;
            } else {
                $nvp_key            = $this->_args['singular'];
                $licenses_to_block  = $_GET[$nvp_key];

                foreach ($licenses_to_block as $row) {
                    SLM_Utility::block_license_key_by_row_id($row);
                }

                echo '<div id="message" class="updated fade"><p>' . esc_html($row) . ' ' . esc_html__('Selected records blocked successfully!', 'slm-plus') . '</p></div>';
            }
        }

        if ('expired' === $this->current_action()) {
            //Process expired bulk actions
            if (!isset($_REQUEST['item'])) {
                $error_msg = '<p>' . __('Error - Please select some records using the checkboxes', 'slm-plus') . '</p>';
                echo '<div id="message" class="error fade">' . esc_html($error_msg) . '</div>';
                return;
            } else {
                $nvp_key            = $this->_args['singular'];
                $licenses_to_expire  = $_GET[$nvp_key];

                foreach ($licenses_to_expire as $row) {
                    SLM_Utility::expire_license_key_by_row_id($row);
                }

                echo '<div id="message" class="updated fade"><p>' . esc_html($row) . ' ' . esc_html__('Selected records expired successfully!', 'slm-plus') . '</p></div>';
            }
        }

        if ('active' === $this->current_action()) {
            //Process activate bulk actions
            if (!isset($_REQUEST['item'])) {
                $error_msg = '<p>' . __('Error - Please select some records using the checkboxes', 'slm-plus') . '</p>';
                echo '<div id="message" class="error fade">' . esc_html($error_msg) . '</div>';
                return;
            } else {
                $nvp_key                = $this->_args['singular'];
                $liceses_to_activate    = $_GET[$nvp_key];

                foreach ($liceses_to_activate as $row) {
                    SLM_Utility::active_license_key_by_row_id($row);
                }

                echo '<div id="message" class="updated fade"><p>' . esc_html($row) . ' ' . esc_html__('Selected records activated successfully!', 'slm-plus') . '</p></div>';
            }
        }

        // Export license data
        if ('export' === $this->current_action()) {
            if (!isset($_REQUEST['item'])) {
                $error_msg = '<p>' . __('Error - Please select some records using the checkboxes', 'slm-plus') . '</p>';
                echo '<div id="message" class="error fade">' . esc_html($error_msg) . '</div>';
                return;
            } else {
                $nvp_key = $this->_args['singular'];
                $licenses_to_export = $_GET[$nvp_key];

                // Call the export function
                $file_urls = self::export_license_data($licenses_to_export);

                // Display success message with download links for each license
                echo '<div id="message" class="updated fade">';
                echo '<p>Export successful! Download the CSV files:</p>';
                foreach ($file_urls as $file_url) {
                    echo '<p><a href="' . esc_url($file_url) . '" target="_blank">Download CSV File</a></p>';
                }
                echo '</div>';
            }
        }
    }

    public static function export_license_data($license_ids)
    {
        global $wpdb;

        // Fetch the custom directory path from options (saved with hash)
        $slm_options = get_option('slm_plugin_options');
        $custom_dir_hash = isset($slm_options['slm_backup_dir_hash']) ? $slm_options['slm_backup_dir_hash'] : '';

        // Prepare file URLs array
        $file_urls = [];

        // Get the WordPress upload directory
        $upload_dir = wp_upload_dir();
        $custom_dir = $upload_dir['basedir'] . '/' . $custom_dir_hash;

        // Initialize WP_Filesystem for safe file handling
        if (empty($GLOBALS['wp_filesystem'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }

        // Ensure the directory exists using WP_Filesystem methods
        if (!is_dir($custom_dir)) {
            $created = $GLOBALS['wp_filesystem']->mkdir($custom_dir, 0755); // Create the directory if it doesn't exist
            if (!$created) {
                return new WP_Error('directory_creation_failed', 'Unable to create the directory.');
            }
        }

        // Fetch license data for each selected ID
        foreach ($license_ids as $license_id) {
            $data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM " . SLM_TBL_LICENSE_KEYS . " WHERE id = %d",
                $license_id
            ), ARRAY_A);

            if ($data) {
                $license_key = $data['license_key'];

                // Prepare file name as "license_key.csv"
                $file_name = sanitize_file_name($license_key) . '.csv';
                $file_path = $custom_dir . '/' . $file_name;

                // Open file handle using WP_Filesystem methods
                $file_handle = $GLOBALS['wp_filesystem']->open($file_path, 'w');

                if (!$file_handle) {
                    return new WP_Error('file_creation_failed', 'Unable to open the file for writing.');
                }

                // Write CSV headers and license data to the file
                fputcsv($file_handle, array_keys($data));
                fputcsv($file_handle, $data);

                // Close the file handle
                $GLOBALS['wp_filesystem']->close($file_handle);

                // Store the file URL for download
                $file_urls[] = $upload_dir['baseurl'] . '/' . $custom_dir_hash . '/' . $file_name;
            }
        }

        // Return the array of file URLs
        return $file_urls;
    }



    /*
     * This function will delete the selected license key entries from the DB.
     */
    function delete_license_key($key_row_id)
    {
        SLM_Utility::delete_license_key_by_row_id($key_row_id);
        $success_msg    = '<div id="message" class="updated"><p><strong>';
        $success_msg    .= 'The selected entry was deleted successfully!';
        $success_msg    .= '</strong></p></div>';
        echo esc_html($success_msg);
    }

    function block_license_key($key_row_id)
    {
        SLM_Utility::block_license_key_by_row_id($key_row_id);
        $success_msg    = '<div id="message" class="updated"><p><strong>';
        $success_msg    .= 'The selected entry was blocked successfully!';
        $success_msg    .= '</strong></p></div>';
        echo esc_html($success_msg);
    }

    private function sort_data($a, $b)
    {
        // Set defaults
        $orderby = 'id';
        $order = 'desc';

        // Sanitize and unslash input for 'orderby' and 'order'
        if (!empty($_GET['orderby'])) {
            $orderby = wp_unslash($_GET['orderby']); // wp_unslash before sanitization
            $orderby = sanitize_key($orderby); // sanitize for key-based data
        }

        if (!empty($_GET['order'])) {
            $order = wp_unslash($_GET['order']); // wp_unslash before sanitization
            $order = in_array(strtolower($order), ['asc', 'desc']) ? strtolower($order) : 'desc'; // Ensure 'asc' or 'desc' only
        }

        // Sorting logic
        if ($orderby == 'id') {
            if ($a[$orderby] == $b[$orderby]) {
                $result = 0;
            } else {
                $result = ($a[$orderby] < $b[$orderby]) ? -1 : 1;
            }
        } else {
            $result = strcmp($a[$orderby], $b[$orderby]);
        }

        // Return based on the order (asc or desc)
        if ($order === 'asc') {
            return $result;
        }

        return -$result;
    }

    function prepare_items()
    {
        global $wpdb;
        $user = get_current_user_id();
        $screen = get_current_screen();
        $option = $screen->get_option('per_page', 'option');
        $per_page = get_user_meta($user, $option, true);

        if (empty($per_page) || $per_page < 1) {
            $per_page = $screen->get_option('per_page', 'default');
        }

        $columns = $this->get_columns();
        $hidden = get_hidden_columns($screen);
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->process_bulk_action();

        // Ensure the license table constant is used safely
        $license_table = esc_sql(SLM_TBL_LICENSE_KEYS); // Sanitize the table name

        // Search handling with esc_like for wildcard search
        $search = isset($_REQUEST['s']) ? sanitize_text_field(wp_unslash($_REQUEST['s'])) : ''; // Use wp_unslash to handle slashes
        $search_term = wp_strip_all_tags($search); // Using wp_strip_all_tags instead of strip_tags
        $search_term_esc = addcslashes($search_term, '_%'); // Escapes underscore and percent characters

        // Prepared query with placeholders and escaped search term
        $do_search = $wpdb->prepare(
            "SELECT * FROM $license_table 
             WHERE license_key LIKE %s OR email LIKE %s OR lic_status LIKE %s OR first_name LIKE %s OR last_name LIKE %s",
            '%' . $search_term_esc . '%',  // Apply wildcard to the escaped search term
            '%' . $search_term_esc . '%',
            '%' . $search_term_esc . '%',
            '%' . $search_term_esc . '%',
            '%' . $search_term_esc . '%'
        );

        // Execute the query and get the results
        $data = $wpdb->get_results($do_search, ARRAY_A);

        // Sort data
        usort($data, array(&$this, 'sort_data'));

        // Pagination
        $current_page = $this->get_pagenum();
        $total_items = count($data);
        $data = array_slice($data, (($current_page - 1) * $per_page), $per_page);

        $this->items = $data;

        // Set pagination arguments
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }
}

class SLM_Plugin
{
    // class instance
    static $instance;

    // customer WP_List_Table object
    public $licenses_obj;

    // class constructor
    public function __construct()
    {
        add_filter('set-screen-option', [__CLASS__, 'set_screen'], 10, 3);
        add_action('admin_menu', [$this, 'slm_add_admin_menu']);
    }

    public static function set_screen($status, $option, $value)
    {
        return $value;
    }

    public function slm_add_admin_menu()
    {
        $icon_svg = SLM_ASSETS_URL . 'images/slm_logo_small.svg';
        add_menu_page(__('SLM Plus', 'slm-plus'), __('SLM Plus', 'slm-plus'), SLM_MANAGEMENT_PERMISSION, SLM_MAIN_MENU_SLUG, "slm_manage_licenses_menu", $icon_svg);
        $hook = add_submenu_page(SLM_MAIN_MENU_SLUG, __('Manage Licenses', 'slm-plus'), __('Manage Licenses', 'slm-plus'), SLM_MANAGEMENT_PERMISSION, SLM_MAIN_MENU_SLUG, "slm_manage_licenses_menu");
        add_submenu_page(SLM_MAIN_MENU_SLUG, __('Create license', 'slm-plus'), __('Create license', 'slm-plus'), SLM_MANAGEMENT_PERMISSION, 'slm_manage_license', "slm_add_licenses_menu");
        add_submenu_page(SLM_MAIN_MENU_SLUG, __('Subscribers', 'slm-plus'), __('Subscribers', 'slm-plus'), SLM_MANAGEMENT_PERMISSION, 'slm_subscribers', "slm_subscribers_menu");
        add_submenu_page(SLM_MAIN_MENU_SLUG, __('Tools', 'slm-plus'), __('Tools', 'slm-plus'), SLM_MANAGEMENT_PERMISSION, 'slm_admin_tools', "slm_admin_tools_menu");
        add_submenu_page(SLM_MAIN_MENU_SLUG, __('Settings', 'slm-plus'), __('Settings', 'slm-plus'), SLM_MANAGEMENT_PERMISSION, 'slm_settings', "slm_settings_menu");
        add_submenu_page(SLM_MAIN_MENU_SLUG, __('Help', 'slm-plus'), __('Help', 'slm-plus'), SLM_MANAGEMENT_PERMISSION, 'slm_help', "slm_integration_help_menu");
        add_submenu_page(SLM_MAIN_MENU_SLUG, __('About', 'slm-plus'), __('About', 'slm-plus'), SLM_MANAGEMENT_PERMISSION, 'slm_about', "slm_about_menu");
        add_action("load-" . $hook, [$this, 'screen_option']);
    }

    /**
     * Screen options
     */
    public function screen_option()
    {
        $option = 'per_page';
        $args   = [
            'label'   => 'Pagination',
            'default' => 16,
            'option'  => 'licenses_per_page'
        ];
        add_screen_option($option, $args);
        $this->licenses_obj = new SLM_List_Licenses();
    }

    /** Singleton instance */
    public static function get_instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}

add_action('plugins_loaded', function () {
    SLM_Plugin::get_instance();
});
