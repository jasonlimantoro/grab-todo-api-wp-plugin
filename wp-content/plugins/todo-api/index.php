<?php
/*
Plugin Name: Todo
Description: A test plugin to demonstrate wordpress api todo endpoint
Author: Jason Gunawan
Version: 0.1
*/
function init_plugin()
{
	global $wpdb;
	$db_table_name = $wpdb->prefix . 'todos';  // table name
	$charset_collate = $wpdb->get_charset_collate();

	$query = "CREATE TABLE IF NOT EXISTS $db_table_name (
			`ID` int(11) NOT NULL AUTO_INCREMENT,
			`title` varchar(255) NOT NULL,
			`completed` tinyint(1) NOT NULL DEFAULT '0',
			PRIMARY KEY (`ID`)
		   ) $charset_collate";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($query);
}
function destroy_plugin()
{
	global $wpdb;
	$table_name = $wpdb->prefix . 'todos';
	$sql = "DROP TABLE IF EXISTS $table_name";
	$wpdb->query($sql);
}

register_activation_hook(__FILE__, 'init_plugin');
register_deactivation_hook(__FILE__, 'destroy_plugin');
register_uninstall_hook(__FILE__, 'destroy_plugin');

add_action('rest_api_init', function () {
	$namespace = "todo-api/v1";
	register_rest_route($namespace, 'todos', [
		'methods'  => 'GET',
		'callback' => 'get_todos'
	]);
	register_rest_route($namespace, 'todos/(?P<todo_id>\d+)', [
		'methods' => 'GET',
		'callback' => 'get_todo',
	]);

	register_rest_route($namespace, 'todos/(?P<todo_id>\d+)', [
		'methods' => 'DELETE',
		'callback' => 'delete_todo',
	]);

	register_rest_route($namespace, 'todos', [
		'methods' => 'POST',
		'callback' => 'create_todo',
	]);

	register_rest_route($namespace, 'todos/(?P<todo_id>\d+)', [
		'methods' => 'PATCH',
		'callback' => 'update_todo',
	]);
});

function get_todos()
{
	global $wpdb;
	$todos = $wpdb->get_results("SELECT id, title, completed FROM {$wpdb->prefix}todos");
	return $todos;
}

function get_todo($request)
{
	global $wpdb;
	$id = $request['todo_id'];
	$query = $wpdb->prepare("SELECT id, title FROM {$wpdb->prefix}todos WHERE id = %d", $id);
	$todo = $wpdb->get_results($query);
	if (empty($todo)) {
		return new WP_Error('empty_todo', 'there is no todo', ['status' => 404]);
	}
	return $todo[0];
}

function delete_todo($request)
{
	global $wpdb;
	$id = $request['todo_id'];
	$wpdb->delete("{$wpdb->prefix}todos", ['id' => $id]);
	return new WP_REST_Response(null, 204);
}


function create_todo($request)
{
	global $wpdb;
	$wpdb->insert(
		"{$wpdb->prefix}todos",
		[
			'title' => $request['title'],
		],
	);
	return new WP_REST_Response(['id' => $wpdb->insert_id], 201);
}

function update_todo($request)
{
	global $wpdb;
	$id = $request['todo_id'];
	$query = $wpdb->prepare("SELECT title FROM {$wpdb->prefix}todos WHERE id = %d", $id);
	$old_todo = $wpdb->get_results($query)[0];
	if (empty($old_todo)) {
		return new WP_Error('empty_todo', 'there is no todo be updated', ['status' => 400]);
	}
	$wpdb->update(
		"{$wpdb->prefix}todos",
		[
			'title' => $request['title'] ?? $old_todo->title,
			'completed' => $request['completed'] ?? $old_todo->completed,
		],
		[
			'id' => $id
		]
	);

	return new WP_REST_Response(null, 200);
}
