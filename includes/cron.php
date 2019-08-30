<?php

/**
 * The file that defines the cron job functionality
 *
 * @since 2.1
 *
 */


// call when WP loads
add_action( 'wp', 'flamingo_schedule_activation', 10, 0 );

/**
 * Create schedule event for cron job, if its already not exists
 *
 * @since 2.1
 *
 * @see wp_next_scheduled(), wp_schedule_event()
 *
 */
function flamingo_schedule_activation() {
	if ( ! wp_next_scheduled( 'flamingo_daily_cron_job' ) ) {
		wp_schedule_event( time(), 'daily', 'flamingo_daily_cron_job' );
	}
}


// deactivate cron job on deactivation of the plugin on plugin's deactivation
register_deactivation_hook( __FILE__, 'flamingo_schedule_deactivate' );

/**
 * Function to deactivate the cron job
 *
 * @since 2.1
 *
 * @see wp_next_scheduled(), wp_unschedule_event()
 *
 */
function flamingo_schedule_deactivate() {

	// when the last event was scheduled
	$timestamp = wp_next_scheduled( 'flamingo_daily_cron_job' );

	// unschedule previous event if any
	wp_unschedule_event( $timestamp, 'flamingo_daily_cron_job' );
}


// hook flamingo_schedule_function to schedule event
add_action( 'flamingo_daily_cron_job', 'flamingo_schedule_function', 10, 0 );

/**
 * Function to run for cron job
 *
 * @since 2.1
 *
 * @see flamingo_schedule_move_trash()
 *
 */
function flamingo_schedule_function() {

	// run function move spam to trash
	flamingo_schedule_move_trash();
}
