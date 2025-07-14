<?php
/*
 * Plugin Name: Flamingo
 * Plugin URI: https://contactform7.com/save-submitted-messages-with-flamingo/
 * Description: A trustworthy message storage plugin for Contact Form 7.
 * Author: Takayuki Miyoshi
 * Author URI: https://ideasilo.wordpress.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Version: 2.6
 * Requires at least: 6.7
 * Requires PHP: 7.4
 */

define( 'FLAMINGO_VERSION', '2.6' );

define( 'FLAMINGO_PLUGIN', __FILE__ );

define( 'FLAMINGO_PLUGIN_BASENAME',
	plugin_basename( FLAMINGO_PLUGIN )
);

define( 'FLAMINGO_PLUGIN_NAME',
	trim( dirname( FLAMINGO_PLUGIN_BASENAME ), '/' )
);

define( 'FLAMINGO_PLUGIN_DIR',
	untrailingslashit( dirname( FLAMINGO_PLUGIN ) )
);

if ( ! defined( 'FLAMINGO_MOVE_TRASH_DAYS' ) ) {
	define( 'FLAMINGO_MOVE_TRASH_DAYS', 30 );
}

// Deprecated, not used in the plugin core. Use flamingo_plugin_url() instead.
define( 'FLAMINGO_PLUGIN_URL',
	untrailingslashit( plugins_url( '', FLAMINGO_PLUGIN ) )
);

require_once FLAMINGO_PLUGIN_DIR . '/includes/functions.php';
require_once FLAMINGO_PLUGIN_DIR . '/includes/formatting.php';
require_once FLAMINGO_PLUGIN_DIR . '/includes/csv.php';
require_once FLAMINGO_PLUGIN_DIR . '/includes/capabilities.php';
require_once FLAMINGO_PLUGIN_DIR . '/includes/class-contact.php';
require_once FLAMINGO_PLUGIN_DIR . '/includes/class-inbound-message.php';
require_once FLAMINGO_PLUGIN_DIR . '/includes/user.php';
require_once FLAMINGO_PLUGIN_DIR . '/includes/comment.php';
require_once FLAMINGO_PLUGIN_DIR . '/includes/akismet.php';
require_once FLAMINGO_PLUGIN_DIR . '/includes/cron.php';

if ( is_admin() ) {
	require_once FLAMINGO_PLUGIN_DIR . '/admin/admin.php';
}

/* Init */

add_action( 'init', static function () {
	/* Custom Post Types */
	Flamingo_Contact::register_post_type();
	Flamingo_Inbound_Message::register_post_type();

	add_filter(
		'wp_untrash_post_status',
		'flamingo_untrash_post_status',
		10, 3
	);

	do_action( 'flamingo_init' );
}, 10, 0 );


function flamingo_untrash_post_status( $new_status, $post_id, $prev_status ) {
	$flamingo_post_types = array(
		Flamingo_Contact::post_type,
		Flamingo_Inbound_Message::post_type,
	);

	if ( in_array( get_post_type( $post_id ), $flamingo_post_types, true ) ) {
		return $prev_status;
	}

	return $new_status;
}
