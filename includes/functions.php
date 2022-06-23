<?php

/**
 * Retrieves a URL of a file under the Flamingo plugin directory.
 */
function flamingo_plugin_url( $path = '' ) {
	$url = plugins_url( $path, FLAMINGO_PLUGIN );

	if ( is_ssl() and 'http:' == substr( $url, 0, 5 ) ) {
		$url = 'https:' . substr( $url, 5 );
	}

	return $url;
}


/**
 * Converts a multidimensional array to a flat array.
 */
function flamingo_array_flatten( $input ) {
	if ( ! is_array( $input ) ) {
		return array( $input );
	}

	$output = array();

	foreach ( $input as $value ) {
		$output = array_merge( $output, flamingo_array_flatten( $value ) );
	}

	return $output;
}


/**
 * Moves a spam to the Trash.
 *
 * @since 2.1
 */
function flamingo_schedule_move_trash() {

	// abort if FLAMINGO_MOVE_TRASH_DAYS is set to zero or in minus
	if ( (int) FLAMINGO_MOVE_TRASH_DAYS <= 0 ) {
		return true;
	}

	$posts_to_move = Flamingo_Inbound_Message::find( array(
		'posts_per_page' => 100,
		'meta_key' => '_spam_meta_time',
		'meta_value' => time() - ( DAY_IN_SECONDS * FLAMINGO_MOVE_TRASH_DAYS ),
		'meta_compare' => '<',
		'post_status' => Flamingo_Inbound_Message::spam_status,
	) );

	foreach ( $posts_to_move as $post ) {

		if ( $post->trash() ) {

			// delete spam meta time to stop trashing in cron job
			delete_post_meta( $post->id(), '_spam_meta_time' );
		}

	}
}
