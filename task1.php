<?php
/**
* Plugin Name: Task1
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
function maybe_create_my_table2()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'things2';

        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            name tinytext NOT NULL,
            email VARCHAR(100) NOT NULL,
            ip_address varchar(15),
            PRIMARY KEY (id),
            KEY ip_address (ip_address)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

register_activation_hook(__FILE__, 'maybe_create_my_table2');

function load_plugin()
{
    if (is_admin() and get_option('Activated_Plugin') == 'Task1-slug')
      {
        delete_option('Activated_Plugin');
      };}
        # Perform actions once right after activation
        # Example: add_action('init', 'my_init_function')
add_option('Activated_Plugin', 'Task1-slug');
add_action('init', 'load_plugin');
add_action('init', 'maybe_create_my_table2');
