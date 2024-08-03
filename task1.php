<?php
/**
* Plugin Name: Task11
* Plugin URI:
* Description: Task1 assigned by Aniss ( Incsub )
* Version: 1.0.0.
* Requires at least:
* Requires PHP:
* Author: ozeljko
* Author URI:
* License: GNU AGPL v3.0
* License URI:
* Update URI:
* Text Domain:
* Domain Path:
*/

//create table  prefix things
function maybe_create_my_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'things';

    $sql = "CREATE TABLE $table_name (
        id int(11) NOT NULL AUTO_INCREMENT,
        thing_name tinytext NOT NULL,
        thing_email VARCHAR(100) NOT NULL,
        ip_address varchar(15),
        PRIMARY KEY (id),
        KEY ip_address (ip_address)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'maybe_create_my_table');

//delete table prefix things
function uninstall_task11_plugin(){
    global $wpdb;
    $table_name = $wpdb->prefix . 'things';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}
register_uninstall_hook(__FILE__,'uninstall_task11_plugin');

// Things in Admin panel
function things_custom_table_admin_menu() {
    add_menu_page(__('Things', 'things_custom_table'), __('Things', 'things_custom_table'), 'activate_plugins', 'things', 'things_custom_table_page_handler', 'dashicons-open-folder', '5');
    add_submenu_page('things', __('Things', 'things_custom_table'), __('Things', 'things_custom_table'), 'activate_plugins', 'things', 'things_custom_table_page_handler');
    add_submenu_page('things', __('Add new', 'things_custom_table'), __('Add new', 'things_custom_table'), 'activate_plugins', 'things_form', 'things_custom_table_page_handler_add_form');
}
add_action('admin_menu', 'things_custom_table_admin_menu');


//Records Display start
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}
class Things_Custom_Table_List_Table extends WP_List_Table {

	function __construct() {
        global $status, $page;

        parent::__construct(array(
            'singular' => 'person',
            'plural' => 'persons',
        ));
    }

    function column_default($item, $column_name) {
        return $item[$column_name];
    }



    function column_ip($item) {
        return '<em>' . $item['ip_address'] . '</em>';
    }
/*
    function column_name($item) {
        $actions = array(
            'edit' => sprintf('<a href="?page=things_form&id=%s">%s</a>', $item['id'], __('Edit', 'things_custom_table')),
            'delete' => sprintf('<a href="?page=%s&action=delete&id=%s">%s</a>', $_REQUEST['page'], $item['id'], __('Delete', 'things_custom_table')),
        );

        return sprintf('%s %s',
            $item['thing_name'],
            $this->row_actions($actions)
        );
    }
*/
    function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />',
            $item['id']
        );
    }

    function get_columns() {
        $columns = array(
            'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
            'thing_name' => __('Thing_name', 'things_custom_table'),
            'thing_email' => __('Thing_email', 'things_custom_table'),
            'ip_address' => __('IP address', 'things_custom_table'),
        );
        return $columns;
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            'thing_name' => array('thing_name', true),
            'thing_email' => array('thing_email', false),
            'ip_address' => array('ip_address', false),
        );
        return $sortable_columns;
    }

    function get_bulk_actions() {
        $actions = array(
            'delete' => 'Delete'
        );
        return $actions;
    }

    function process_bulk_action() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'things';

        if ('delete' === $this->current_action()) {
            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
            if (is_array($ids)) $ids = implode(',', $ids);

            if (!empty($ids)) {
                $wpdb->query("DELETE FROM $table_name WHERE id IN($ids)");
            }
        }
    }

    function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'things';

        $per_page = 4;

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->process_bulk_action();

        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

        $paged = isset($_REQUEST['paged']) ? ($per_page * max(0, intval($_REQUEST['paged']) - 1)) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'thing_name';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'asc';

        $this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }

}

function things_custom_table_page_handler() {
    global $wpdb;

    $table = new Things_Custom_Table_List_Table();
    $table->prepare_items();

    $message = '';
    if ('delete' === $table->current_action()) {
        $message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Items deleted: %d', 'things_custom_table'), count($_REQUEST['id'])) . '</p></div>';
    }
    ?>
<div class="wrap">

    <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
    <h2><?php _e('Things', 'things_custom_table')?> <a class="add-new-h2" href="<?php echo esc_url(site_url('/wp-admin/admin.php?page=things_form'));?>"><?php _e('Add new', 'things_custom_table')?></a>
    </h2>
    <?php echo $message; ?>

    <form id="things-table" method="GET">
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
        <?php $table->display() ?>
    </form>

</div>
<?php
}
//Records Display END

//Start ADD Data to Records
function things_custom_table_page_handler_add_form() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'things';

    $message = '';
    $notice = '';

    $default = array(
        'id' => 0,
        'thing_name' => '',
        'thing_email' => '',
        'ip_address' => null,
    );

    // Check if the form is submitted
    if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'things_custom_table_nonce')) {
        $item = shortcode_atts($default, $_POST);
        $result = $wpdb->insert($table_name, $item);
        $item['id'] = $wpdb->insert_id;
        if ($result) {
            $message = __('Item was successfully saved', 'things_custom_table');
        } else {
            $notice = __('There was an error while saving item', 'things_custom_table');
        }
    } else {
        $item = $default;
    }

    add_meta_box('things_form_meta_box', 'Things data', 'things_custom_table_things_form_meta_box_handler', 'thing', 'normal', 'default');

    ?>
    <div class="wrap">
        <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
        <h2><?php _e('Thing', 'things_custom_table')?> <a class="add-new-h2"
                                    href="<?php echo esc_url(site_url('/wp-admin/admin.php?page=things'));?>"><?php _e('back to list', 'things_custom_table')?></a>
        </h2>

        <?php if (!empty($notice)): ?>
        <div id="notice" class="error"><p><?php echo $notice ?></p></div>
        <?php endif;?>
        <?php if (!empty($message)): ?>
        <div id="message" class="updated"><p><?php echo $message ?></p></div>
        <?php endif;?>

        <form id="form" method="POST">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('things_custom_table_nonce')?>"/>
            <input type="hidden" name="id" value="<?php echo $item['id'] ?>"/>

            <div class="metabox-holder" id="poststuff">
                <div id="post-body">
                    <div id="post-body-content">
                        <?php do_meta_boxes('thing', 'normal', $item); ?>
                        <input type="submit" value="<?php _e('Save', 'things_custom_table')?>" id="submit" class="button-primary" name="submit">
                    </div>
                </div>
            </div>
        </form>
    </div>
    <?php
}

function things_custom_table_things_form_meta_box_handler($item) {
    ?>
    <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
        <tbody>
        <tr class="form-field">
            <th valign="top" scope="row">
                <label for="thing_name"><?php _e('Thing_name', 'things_custom_table')?></label>
            </th>
            <td>
                <input id="thing_name" name="thing_name" type="text" style="width: 95%" value="<?php echo esc_attr($item['thing_name'])?>"
                        size="50" class="code" placeholder="<?php _e('Your name', 'things_custom_table')?>" required>
            </td>
        </tr>
        <tr class="form-field">
            <th valign="top" scope="row">
                <label for="thing_email"><?php _e('Thing_email', 'things_custom_table')?></label>
            </th>
            <td>
                <input id="thing_email" name="thing_email" type="email" style="width: 95%" value="<?php echo esc_attr($item['thing_email'])?>"
                        size="50" class="code" placeholder="<?php _e('Your E-Mail', 'things_custom_table')?>" required>
            </td>
        </tr>
        <tr class="form-field">
            <th valign="top" scope="row">
                <label for="ip_address"><?php _e('IP', 'things_custom_table')?></label>
            </th>
            <td>
                <input id="ip_address" name="ip_address" type="number" style="width: 95%" value="<?php echo esc_attr($item['ip_address'])?>"
                        size="50" class="code" placeholder="<?php _e('Your IP address', 'things_custom_table')?>" required>
            </td>
        </tr>
        </tbody>
    </table>
    <?php
}

// END Add Data to Records

// shortcode to display the data from the custom table, with search functionality
function things_custom_table_shortcode($atts) {
    global $wpdb;

    // Handle search query
    $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

    // Query to fetch data from the custom table
    $table_name = $wpdb->prefix . 'things';
    $query = "SELECT * FROM $table_name";
    if (!empty($search)) {
        $query .= $wpdb->prepare(" WHERE name LIKE %s OR thing_email LIKE %s", '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%');
    }
    $results = $wpdb->get_results($query, ARRAY_A);

    // Output the search form and table
    ob_start();
    ?>
    <form method="GET" action="">
        <input type="text" name="search" value="<?php echo esc_attr($search); ?>" placeholder="Search by name or email">
        <input type="submit" value="Search">
    </form>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>IP Address</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($results)): ?>
                <?php foreach ($results as $row): ?>
                    <tr>
                        <td><?php echo esc_html($row['id']); ?></td>
                        <td><?php echo esc_html($row['thing_name']); ?></td>
                        <td><?php echo esc_html($row['thing_email']); ?></td>
                        <td><?php echo esc_html($row['ip_address']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">No items found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php
    return ob_get_clean();
}

// Register the shortcode
add_shortcode('things_table', 'things_custom_table_shortcode');

// Add the shortcode [things_table] to the content area

//shortcode to display a form that accept user input insert it into the custom table
function things_custom_table_form_shortcode() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'things';

    $message = '';
    $notice = '';

    $default = array(
        'id' => 0,
        'thing_name' => '',
        'thing_email' => '',
        'ip_address' => null,
    );

    // Check if the form is submitted
    if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'things_custom_table_nonce')) {
        $item = shortcode_atts($default, $_POST);
        $result = $wpdb->insert($table_name, $item);
        $item['id'] = $wpdb->insert_id;
        if ($result) {
            $message = __('Item was successfully saved', 'things_custom_table');
            wp_redirect($_SERVER['HTTP_REFERER']);
        } else {
            $notice = __('There was an error while saving item', 'things_custom_table');
            // Debugging: Output the last error
            $notice .= '<br>' . $wpdb->last_error;
        }
    } else {
        $item = $default;
    }

    ob_start();
    ?>
    <div class="wrap">
        <?php if (!empty($notice)): ?>
        <div id="notice" class="error"><p><?php echo $notice ?></p></div>
        <?php endif;?>
        <?php if (!empty($message)): ?>
        <div id="message" class="updated"><p><?php echo $message ?></p></div>
        <?php endif;?>

        <form id="form" method="POST">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('things_custom_table_nonce')?>"/>
            <input type="hidden" name="id" value="<?php echo $item['id'] ?>"/>

            <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
                <tbody>
                <tr class="form-field">
                    <th valign="top" scope="row">
                        <label for="thing_name"><?php _e('Thing_name', 'things_custom_table')?></label>
                    </th>
                    <td>
                        <input id="thing_name" name="thing_name" type="text" style="width: 95%" value="<?php echo esc_attr($item['thing_name'])?>"
                                size="50" class="code" placeholder="<?php _e('Your name', 'things_custom_table')?>" required>
                    </td>
                </tr>
                <tr class="form-field">
                    <th valign="top" scope="row">
                        <label for="thing_email"><?php _e('Thing_e-mail', 'things_custom_table')?></label>
                    </th>
                    <td>
                        <input id="thing_email" name="thing_email" type="email" style="width: 95%" value="<?php echo esc_attr($item['thing_email'])?>"
                                size="50" class="code" placeholder="<?php _e('Your E-Mail', 'things_custom_table')?>" required>
                    </td>
                </tr>
                <tr class="form-field">
                    <th valign="top" scope="row">
                        <label for="ip_address"><?php _e('IP', 'things_custom_table')?></label>
                    </th>
                    <td>
                        <input id="ip_address" name="ip_address" type="number" style="width: 95%" value="<?php echo esc_attr($item['ip_address'])?>"
                                size="50" class="code" placeholder="<?php _e('Your IP address', 'things_custom_table')?>" required>
                    </td>
                </tr>
                </tbody>
            </table>

            <input type="submit" value="<?php _e('Save', 'things_custom_table')?>" class="button-primary">
        </form>
    </div>
    <?php



    return ob_get_clean();
}

// Add the shortcode [things_form] to the content area
add_shortcode('things_form', 'things_custom_table_form_shortcode');
