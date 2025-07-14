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
	if ( ! wp_next_scheduled( 'flamingo_hourly_cron_job' ) ) {
		wp_schedule_event( time(), 'hourly', 'flamingo_hourly_cron_job' );
	}
}


add_action( 'flamingo_hourly_cron_job', 'flamingo_schedule_function', 10, 0 );

/**
 * The cron job.
 *
 * @since 2.1
 */
function flamingo_schedule_function() {
	flamingo_schedule_move_trash();
}


// Unscheduling cron jobs on plugin deactivation.
register_deactivation_hook( FLAMINGO_PLUGIN, 'flamingo_schedule_deactivate' );

/**
 * Unschedules cron jobs.
 *
 * @since 2.1
 */
function flamingo_schedule_deactivate() {
	wp_clear_scheduled_hook( 'flamingo_hourly_cron_job' );
	wp_clear_scheduled_hook( 'flamingo_daily_cron_job' );
}
