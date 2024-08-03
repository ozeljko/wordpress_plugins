<?php
/*
Plugin Name: Things REST API
Description: Custom REST API endpoints for the Things table.
Version: 1.0
License: GNU AGPL v3.0
Author: ozeljko
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Things_REST_API {
    private $namespace = 'things/v1';
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'things';
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {
        register_rest_route($this->namespace, '/insert', array(
            'methods' => 'POST',
            'callback' => array($this, 'insert_thing'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route($this->namespace, '/select', array(
            'methods' => 'GET',
            'callback' => array($this, 'select_things'),
            'permission_callback' => '__return_true',
        ));
    }

    public function insert_thing($request) {
        global $wpdb;

        $params = $request->get_json_params();
        $default = array(
            'name' => '',
            'email' => '',
            'ip_address' => null,
        );

        $item = shortcode_atts($default, $params);
        $result = $wpdb->insert($this->table_name, $item);

        if ($result) {
            return new WP_REST_Response(array('message' => 'Item was successfully saved', 'id' => $wpdb->insert_id), 200);
        } else {
            return new WP_REST_Response(array('message' => 'There was an error while saving item'), 500);
        }
    }

    public function select_things($request) {
        global $wpdb;

        $results = $wpdb->get_results("SELECT * FROM {$this->table_name}", ARRAY_A);

        if (!empty($results)) {
            return new WP_REST_Response($results, 200);
        } else {
            return new WP_REST_Response(array('message' => 'No items found'), 404);
        }
    }
}

new Things_REST_API();
