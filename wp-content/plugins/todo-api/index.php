<?php
/*
Plugin Name: Todo
Description: A test plugin to demonstrate wordpress api todo endpoint
Author: Jason Gunawan
Version: 0.1
*/
add_action('rest_api_init', function () {
	$namespace = "grab-todo/v1";
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
	$todos = $wpdb->get_results("SELECT id, title, description FROM {$wpdb->prefix}todos");
	return $todos;
}

function get_todo($request)
{
	global $wpdb;
	$id = $request['todo_id'];
	$query = $wpdb->prepare("SELECT id, title, description FROM {$wpdb->prefix}todos WHERE id = %d", $id);
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
			'description' => $request['description']
		],
	);
	return new WP_REST_Response(null, 201);
}

function update_todo($request)
{
	global $wpdb;
	$id = $request['todo_id'];
	$query = $wpdb->prepare("SELECT title, description FROM {$wpdb->prefix}todos WHERE id = %d", $id);
	$old_todo = $wpdb->get_results($query)[0];
	if (empty($old_todo)) {
		return new WP_Error('empty_todo', 'there is no todo be updated', ['status' => 400]);
	}
	$wpdb->update(
		"{$wpdb->prefix}todos",
		[
			'title' => $request['title'] ?? $old_todo->title,
			'description' => $request['description'] ?? $old_todo->description,
			'completed' => $request['completed'] ?? $old_todo->completed,
		],
		[
			'id' => $id
		]
	);

	return new WP_REST_Response(null, 200);
}
