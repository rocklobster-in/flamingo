<?php
/**
 * Cron job functionality
 *
 * @since 2.1
 */


add_action( 'admin_init', 'flamingo_schedule_activation', 10, 0 );

/**
 * Creates a scheduled event, if it does not exist.
 *
 * @since 2.1
 */
function flamingo_schedule_activation() {
	if ( ! wp_next_scheduled( 'flamingo_daily_cron_job' ) ) {
		wp_schedule_event( time(), 'daily', 'flamingo_daily_cron_job' );
	}
}


// Unscheduling cron jobs on plugin deactivation.
register_deactivation_hook( FLAMINGO_PLUGIN, 'flamingo_schedule_deactivate' );

/**
 * Unschedules cron jobs.
 *
 * @since 2.1
 */
function flamingo_schedule_deactivate() {

	// Timestamp of when the last event was scheduled
	$timestamp = wp_next_scheduled( 'flamingo_daily_cron_job' );

	wp_unschedule_event( $timestamp, 'flamingo_daily_cron_job' );
}


add_action( 'flamingo_daily_cron_job', 'flamingo_schedule_function', 10, 0 );

/**
 * The cron job.
 *
 * @since 2.1
 */
function flamingo_schedule_function() {
	flamingo_schedule_move_trash();
}
