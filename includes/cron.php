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
	$timestamp = wp_next_scheduled(
		Flamingo_Inbound_Message::spam_to_trash_cron_hook
	);

	if ( false === $timestamp ) {
		wp_schedule_event(
			time(),
			'hourly',
			Flamingo_Inbound_Message::spam_to_trash_cron_hook
		);
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
	$timestamp = wp_next_scheduled(
		Flamingo_Inbound_Message::spam_to_trash_cron_hook
	);

	wp_unschedule_event(
		$timestamp,
		Flamingo_Inbound_Message::spam_to_trash_cron_hook
	);
}


add_action(
	Flamingo_Inbound_Message::spam_to_trash_cron_hook,
	'flamingo_schedule_function',
	10, 0
);

/**
 * The cron job.
 *
 * @since 2.1
 */
function flamingo_schedule_function() {
	flamingo_schedule_move_trash();
}
