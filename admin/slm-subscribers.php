<?php

if (!defined('WPINC')) {
    die;
}

$email = '';

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Subscribers_List_Table extends WP_List_Table
{
    function __construct()
    {
        global $status, $page;

        parent::__construct(array(
            'singular'  => 'slm_subscriber',
            'plural'    => 'slm_subscribers',
            'ajax'      => false
        ));
    }

    function column_default($item, $column_name)
    {
        switch ($column_name) {

            case 'lic_status':
                return '<span class="slm-lic-' . $item[$column_name] . '"> <span class="slm-status ' . $item[$column_name] . '"></span>'  . $item[$column_name] . '</span>';
                break;

            default:
                return $item[$column_name];
        }
    }



    function column_id($item)
    {
        $row_id = $item['id'] . '&email=' . $item['email'];
        $actions = array(
            'edit'      => sprintf('<a class="left" href="admin.php?page=slm_subscribers&slm_subscriber_edit=true&manage_subscriber=%s">View</a>', $row_id),
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

    function get_columns()
    {
        $columns = array(
            'cb'                    => '<input type="checkbox" />', //Render a checkbox
            'id'                    => __('ID', 'slm-plus'),
            'first_name'           => __('First Name', 'slm-plus'),
            'last_name'            => __('Last Name', 'slm-plus'),
            'email'                 => __('Email Address', 'slm-plus')
        );
        return $columns;
    }


    function get_sortable_columns()
    {
        $sortable_columns = array(
            'id'            => array('id', true),
            'email'         => array('email', true),
            'first_name'      => array('first_name', true),
            'last_name'         => array('last_name', true)
        );

        return $sortable_columns;
    }

    private function sort_data($a, $b)
    {
        // Set defaults
        $orderby = 'id';
        $order = 'desc';
        // If orderby is set, use this as the sort column
        if (!empty($_GET['orderby'])) {
            $orderby = $_GET['orderby'];
        }
        // If order is set use this as the order
        if (!empty($_GET['order'])) {
            $order = $_GET['order'];
        }
        if ($orderby == 'id') {
            if ($a[$orderby] == $b[$orderby]) {
                $result = 0;
            } else {
                $result = ($a[$orderby] < $b[$orderby]) ? -1 : 1;
            }
        } else {
            $result = strcmp($a[$orderby], $b[$orderby]);
        }
        if ($order === 'asc') {
            return $result;
        }
        return -$result;
    }

    function prepare_items()
    {
        $per_page = 24;
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->process_bulk_action();

        global $wpdb;
        $license_table = SLM_TBL_LICENSE_KEYS;

        // Sanitize the search term and strip all tags
        $search = isset($_REQUEST['s']) ? sanitize_text_field(wp_unslash($_REQUEST['s'])) : false;
        $search_term = trim(wp_strip_all_tags($search)); // Using wp_strip_all_tags for better sanitization

        // Escape the search term for SQL and add wildcards manually
        $escaped_search_term = '%' . $wpdb->esc_like($search_term) . '%'; // esc_like handles escaping the term

        // Prepare the query with placeholders to prevent SQL injection
        $do_search = $wpdb->prepare(
            "SELECT * FROM {$license_table} 
            WHERE `email` LIKE %s 
            OR `first_name` LIKE %s 
            OR `last_name` LIKE %s 
            GROUP BY `email`",
            $escaped_search_term, // Use the escaped search term with wildcards
            $escaped_search_term,
            $escaped_search_term
        );

        // Execute the query safely
        $data = $wpdb->get_results($do_search, ARRAY_A);

        usort($data, array(&$this, 'sort_data'));

        // Pagination logic
        $current_page = $this->get_pagenum();
        $total_items = count($data);
        $data = array_slice($data, (($current_page - 1) * $per_page), $per_page);
        $this->items = $data;

        // Set pagination arguments
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }
}

function slm_subscribers_menu()
{
    $subscribers_list = new Subscribers_List_Table();
    $slm_subscriber_edit = isset($_REQUEST['slm_subscriber_edit']) ? sanitize_text_field($_REQUEST['slm_subscriber_edit']) : '';
    if ($slm_subscriber_edit === 'true') : ?>

        <div class="wrap">
            <h1><?php esc_html_e('Overview - Manage Subscribers', 'slm-plus'); ?></h1>
            <br>
            <a href="<?php echo esc_url(admin_url('admin.php?page=slm_subscribers')); ?>" class="page-title-action aria-button-if-js" role="button" aria-expanded="false"><?php esc_html_e('View all', 'slm-plus'); ?></a>
            <hr class="wp-header-end">

            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-1">
                    <div id="post-body-content">
                        <div class="manage-user">
                            <table class="wp-list-table widefat fixed striped items">
                                <tr>
                                    <th scope="col" style="width: 32px"><?php esc_html__('ID', 'slm-plus'); ?></th>
                                    <th scope="col"><?php esc_html__('License key', 'slm-plus'); ?></th>
                                    <th scope="col"><?php esc_html__('Status', 'slm-plus'); ?></th>
                                    <th scope="col"> </th>
                                </tr>
                                <?php
                                SLM_Utility::get_subscriber_licenses();
                                ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php else : ?>

        <div class="wrap">
            <h1><?php esc_html_e('Overview - All Subscribers', 'slm-plus'); ?></h1>
            <br>
            <hr class="wp-header-end">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-1">
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <form id="licenses-filter" method="get">
                                <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
                                <?php
                                $subscribers_list->prepare_items();
                                $subscribers_list->search_box(__('Search', 'slm-plus'), 'search-box-id');
                                $subscribers_list->views();
                                $subscribers_list->display(); ?>
                            </form>
                        </div>
                    </div>
                </div>
                <br class="clear">
                <div id="post-body" class="metabox-holder columns-1">

                </div>
            </div>
        </div>
    <?php endif; ?>

<?php

}
