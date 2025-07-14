<?php

function flamingo_current_action() {
	if ( isset( $_REQUEST['delete_all'] ) or isset( $_REQUEST['delete_all2'] ) ) {
		return 'delete_all';
	}

	if ( isset( $_REQUEST['action'] ) and -1 !== $_REQUEST['action'] ) {
		return $_REQUEST['action'];
	}

	if ( isset( $_REQUEST['action2'] ) and -1 !== $_REQUEST['action2'] ) {
		return $_REQUEST['action2'];
	}

	return false;
}

function flamingo_get_all_ids_in_trash( $post_type ) {
	global $wpdb;

	return $wpdb->get_col( $wpdb->prepare(
		"SELECT ID FROM %i WHERE post_status = 'trash' AND post_type = %s",
		$wpdb->posts,
		$post_type
	) );
}
