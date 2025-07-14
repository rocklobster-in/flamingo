<?php

require_once FLAMINGO_PLUGIN_DIR . '/admin/includes/admin-functions.php';
require_once FLAMINGO_PLUGIN_DIR . '/admin/includes/privacy.php';

add_action( 'admin_menu', 'flamingo_admin_menu', 8, 0 );

function flamingo_admin_menu() {
	add_menu_page(
		__( 'Flamingo Address Book', 'flamingo' ),
		__( 'Flamingo', 'flamingo' ),
		'flamingo_edit_contacts',
		'flamingo',
		'flamingo_contact_admin_page',
		'dashicons-feedback',
		28
	);

	$contact_admin = add_submenu_page(
		'flamingo',
		__( 'Flamingo Address Book', 'flamingo' ),
		__( 'Address Book', 'flamingo' ),
		'flamingo_edit_contacts',
		'flamingo',
		'flamingo_contact_admin_page'
	);

	add_action(
		'load-' . $contact_admin,
		'flamingo_load_contact_admin',
		10, 0
	);

	$inbound_admin = add_submenu_page(
		'flamingo',
		__( 'Flamingo Inbound Messages', 'flamingo' ),
		__( 'Inbound Messages', 'flamingo' ),
		'flamingo_edit_inbound_messages',
		'flamingo_inbound',
		'flamingo_inbound_admin_page'
	);

	add_action(
		'load-' . $inbound_admin,
		'flamingo_load_inbound_admin',
		10, 0
	);
}

add_filter( 'set_screen_option_flamingo_contacts_per_page',
	'flamingo_set_screen_options', 10, 3
);

add_filter( 'set_screen_option_flamingo_inbound_messages_per_page',
	'flamingo_set_screen_options', 10, 3
);

function flamingo_set_screen_options( $result, $option, $value ) {
	$flamingo_screens = array(
		'flamingo_contacts_per_page',
		'flamingo_inbound_messages_per_page',
	);

	if ( in_array( $option, $flamingo_screens ) ) {
		$result = $value;
	}

	return $result;
}

add_action( 'admin_enqueue_scripts', 'flamingo_admin_enqueue_scripts', 10, 1 );

function flamingo_admin_enqueue_scripts( $hook_suffix ) {
	if ( false === strpos( $hook_suffix, 'flamingo' ) ) {
		return;
	}

	wp_enqueue_style( 'flamingo-admin',
		flamingo_plugin_url( 'admin/includes/css/style.css' ),
		array(), FLAMINGO_VERSION, 'all'
	);

	if ( is_rtl() ) {
		wp_enqueue_style( 'flamingo-admin-rtl',
			flamingo_plugin_url( 'admin/includes/css/style-rtl.css' ),
			array(), FLAMINGO_VERSION, 'all'
		);
	}

	$assets = include FLAMINGO_PLUGIN_DIR . '/admin/includes/js/index.asset.php';

	$assets = wp_parse_args( $assets, array(
		'dependencies' => array(),
		'version' => FLAMINGO_VERSION,
	) );

	wp_enqueue_script( 'flamingo-admin',
		flamingo_plugin_url( 'admin/includes/js/index.js' ),
		$assets['dependencies'],
		$assets['version'],
		array( 'in_footer' => true )
	);

	wp_set_script_translations( 'flamingo-admin', 'flamingo' );

	$current_screen = get_current_screen();

	wp_add_inline_script( 'flamingo-admin',
		sprintf(
			'var flamingo = %s;',
			wp_json_encode( array(
				'screenId' => $current_screen->id,
			), JSON_PRETTY_PRINT )
		),
		'before'
	);
}

/* Updated Message */

add_action( 'flamingo_admin_updated_message',
	'flamingo_admin_updated_message',
	10, 0
);

function flamingo_admin_updated_message() {
	if ( empty( $_REQUEST['message'] ) ) {
		return;
	}

	if ( 'contactupdated' === $_REQUEST['message'] ) {
		$message = __( 'Contact updated.', 'flamingo' );
	} elseif ( 'contactdeleted' === $_REQUEST['message'] ) {
		$message = __( 'Contact deleted.', 'flamingo' );
	} elseif ( 'inboundupdated' === $_REQUEST['message'] ) {
		$message = __( 'Messages updated.', 'flamingo' );
	} elseif ( 'inboundtrashed' === $_REQUEST['message'] ) {
		$message = __( 'Messages trashed.', 'flamingo' );
	} elseif ( 'inbounduntrashed' === $_REQUEST['message'] ) {
		$message = __( 'Messages restored.', 'flamingo' );
	} elseif ( 'inbounddeleted' === $_REQUEST['message'] ) {
		$message = __( 'Messages deleted.', 'flamingo' );
	} elseif ( 'inboundspammed' === $_REQUEST['message'] ) {
		$message = __( 'Messages got marked as spam.', 'flamingo' );
	} elseif ( 'inboundunspammed' === $_REQUEST['message'] ) {
		$message = __( 'Messages got marked as not spam.', 'flamingo' );
	}

	if ( ! empty( $message ) ) {
		wp_admin_notice( $message, array(
			'type' => 'success',
			'dismissible' => true,
		) );
	}
}

/* Contact */

function flamingo_load_contact_admin() {
	$action = flamingo_current_action();

	$redirect_to = menu_page_url( 'flamingo', false );

	if ( 'save' === $action and ! empty( $_REQUEST['post'] ) ) {
		$post = new Flamingo_Contact( $_REQUEST['post'] );

		if ( ! empty( $post ) ) {
			if ( ! current_user_can( 'flamingo_edit_contact', $post->id() ) ) {
				wp_die(
					wp_kses_data( __( 'You are not allowed to edit this item.', 'flamingo' ) )
				);
			}

			check_admin_referer( 'flamingo-update-contact_' . $post->id() );

			$post->props = (array) $_POST['contact'];

			$post->name = trim( $_POST['contact']['name'] );

			$post->tags = (
				! empty( $_POST['tax_input'][Flamingo_Contact::contact_tag_taxonomy] )
				? explode(
					',', $_POST['tax_input'][Flamingo_Contact::contact_tag_taxonomy]
				)
				: array()
			);

			$post->save();

			$redirect_to = add_query_arg(
				array(
					'action' => 'edit',
					'post' => $post->id(),
					'message' => 'contactupdated',
				), $redirect_to
			);
		}

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'delete' === $action and ! empty( $_REQUEST['post'] ) ) {
		if ( ! is_array( $_REQUEST['post'] ) ) {
			check_admin_referer( 'flamingo-delete-contact_' . $_REQUEST['post'] );
		} else {
			check_admin_referer( 'bulk-posts' );
		}

		$deleted = 0;

		foreach ( (array) $_REQUEST['post'] as $post ) {
			$post = new Flamingo_Contact( $post );

			if ( empty( $post ) ) {
				continue;
			}

			if ( ! current_user_can( 'flamingo_delete_contact', $post->id() ) ) {
				wp_die(
					wp_kses_data( __( 'You are not allowed to delete this item.', 'flamingo' ) )
				);
			}

			if ( ! $post->delete() ) {
				wp_die(
					wp_kses_data( __( 'Error in deleting.', 'flamingo' ) )
				);
			}

			$deleted += 1;
		}

		if ( ! empty( $deleted ) ) {
			$redirect_to = add_query_arg(
				array( 'message' => 'contactdeleted' ), $redirect_to
			);
		}

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( ! empty( $_GET['export'] ) ) {
		check_admin_referer( 'bulk-posts' );

		$csv_class = apply_filters( 'flamingo_contact_csv_class',
			'Flamingo_Contact_CSV'
		);

		if ( is_subclass_of( $csv_class, 'Flamingo_CSV' ) ) {
			$csv_obj = new $csv_class;
			$csv_obj->send_http_headers();
			$csv_obj->print_data();
		}

		exit();
	}

	if ( 'edit' === $action ) {
		$post_id = (int) ( $_REQUEST['post'] ?? '0' );

		if ( ! $post_id ) {
			wp_safe_redirect( $redirect_to );
			exit();
		}

		if (
			! current_user_can( 'flamingo_edit_contact', $post_id ) or
			Flamingo_Contact::post_type !== get_post_type( $post_id )
		) {
			wp_die(
				wp_kses_data( __( 'You are not allowed to edit this item.', 'flamingo' ) )
			);
		}

		add_meta_box( 'submitdiv', __( 'Save', 'flamingo' ),
			'flamingo_contact_submit_meta_box', null, 'side', 'core'
		);

		add_meta_box( 'contacttagsdiv', __( 'Tags', 'flamingo' ),
			'flamingo_contact_tags_meta_box', null, 'side', 'core'
		);

		add_meta_box( 'contactnamediv', __( 'Name', 'flamingo' ),
			'flamingo_contact_name_meta_box', null, 'normal', 'core'
		);

	} else {
		if ( ! class_exists( 'Flamingo_Contacts_List_Table' ) ) {
			require_once FLAMINGO_PLUGIN_DIR
				. '/admin/includes/class-contacts-list-table.php';
		}

		$current_screen = get_current_screen();

		add_filter( 'manage_' . $current_screen->id . '_columns',
			array( 'Flamingo_Contacts_List_Table', 'define_columns' ),
			10, 0
		);

		add_screen_option( 'per_page', array(
			'default' => 20,
			'option' => 'flamingo_contacts_per_page',
		) );
	}
}

function flamingo_contact_admin_page() {
	if ( 'edit' === flamingo_current_action() ) {
		flamingo_contact_edit_page();
		return;
	}

	$list_table = new Flamingo_Contacts_List_Table();
	$list_table->prepare_items();

?>
<div class="wrap">

<h1 class="wp-heading-inline"><?php
	echo esc_html( __( 'Flamingo Address Book', 'flamingo' ) );
?></h1>

<?php
	if ( isset( $_REQUEST['s'] ) and strlen( $_REQUEST['s'] ) ) {
		echo sprintf(
			'<span class="subtitle">%s</span>',
			wp_kses_data( sprintf(
				/* translators: %s: Search query. */
				__( 'Search results for: <strong>%s</strong>', 'flamingo' ),
				esc_html( $_REQUEST['s'] )
			) )
		);
	}
?>

<hr class="wp-header-end">

<?php do_action( 'flamingo_admin_updated_message' ); ?>

<form method="get" action="">
	<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
	<?php $list_table->search_box( __( 'Search contacts', 'flamingo' ), 'flamingo-contact' ); ?>
	<?php $list_table->display(); ?>
</form>

</div>
<?php
}

function flamingo_contact_edit_page() {
	$post = new Flamingo_Contact( $_REQUEST['post'] );

	if ( empty( $post ) ) {
		return;
	}

	require_once FLAMINGO_PLUGIN_DIR . '/admin/includes/meta-boxes.php';

	include FLAMINGO_PLUGIN_DIR . '/admin/edit-contact-form.php';
}

/* Inbound Messages */

function flamingo_load_inbound_admin() {
	$action = flamingo_current_action();

	$redirect_to = menu_page_url( 'flamingo_inbound', false );

	if ( isset( $_GET['post_status'] ) ) {
		$redirect_to = add_query_arg(
			array(
				'post_status' => $_GET['post_status'],
			),
			$redirect_to
		);
	}

	if ( 'save' === $action and ! empty( $_REQUEST['post'] ) ) {
		$post = new Flamingo_Inbound_Message( $_REQUEST['post'] );

		if ( ! empty( $post ) ) {
			if ( ! current_user_can( 'flamingo_edit_inbound_message', $post->id() ) ) {
				wp_die(
					wp_kses_data( __( 'You are not allowed to edit this item.', 'flamingo' ) )
				);
			}

			check_admin_referer( 'flamingo-update-inbound_' . $post->id() );

			$status = $_POST['inbound']['status'] ?? '';

			if ( ! $post->spam and 'spam' === $status ) {
				$post->spam();
			} elseif ( $post->spam and 'ham' === $status ) {
				$post->unspam();
			}

			$redirect_to = add_query_arg(
				array(
					'action' => 'edit',
					'post' => $post->id(),
					'message' => 'inboundupdated',
				), $redirect_to
			);
		}

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'trash' === $action and ! empty( $_REQUEST['post'] ) ) {
		if ( ! is_array( $_REQUEST['post'] ) ) {
			check_admin_referer(
				'flamingo-trash-inbound-message_' . $_REQUEST['post']
			);
		} else {
			check_admin_referer( 'bulk-posts' );
		}

		$trashed = 0;

		foreach ( (array) $_REQUEST['post'] as $post ) {
			$post = new Flamingo_Inbound_Message( $post );

			if ( empty( $post ) ) {
				continue;
			}

			if ( ! current_user_can(
			'flamingo_delete_inbound_message', $post->id() ) ) {
				wp_die(
					wp_kses_data( __( 'You are not allowed to move this item to the Trash.', 'flamingo' ) )
				);
			}

			if ( ! $post->trash() ) {
				wp_die(
					wp_kses_data( __( 'Error in moving to Trash.', 'flamingo' ) )
				);
			}

			$trashed += 1;
		}

		if ( ! empty( $trashed ) ) {
			$redirect_to = add_query_arg(
				array(
					'message' => 'inboundtrashed',
				),
				$redirect_to
			);
		}

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'untrash' === $action and ! empty( $_REQUEST['post'] ) ) {
		if ( ! is_array( $_REQUEST['post'] ) ) {
			check_admin_referer(
				'flamingo-untrash-inbound-message_' . $_REQUEST['post']
			);
		} else {
			check_admin_referer( 'bulk-posts' );
		}

		$untrashed = 0;

		foreach ( (array) $_REQUEST['post'] as $post ) {
			$post = new Flamingo_Inbound_Message( $post );

			if ( empty( $post ) ) {
				continue;
			}

			if (
				! current_user_can( 'flamingo_delete_inbound_message', $post->id() )
			) {
				wp_die(
					wp_kses_data( __( 'You are not allowed to restore this item from the Trash.', 'flamingo' ) )
				);
			}

			if ( ! $post->untrash() ) {
				wp_die(
					wp_kses_data( __( 'Error in restoring from Trash.', 'flamingo' ) )
				);
			}

			$untrashed += 1;
		}

		if ( ! empty( $untrashed ) ) {
			$redirect_to = add_query_arg(
				array(
					'message' => 'inbounduntrashed',
				), $redirect_to
			);
		}

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'delete_all' === $action ) {
		check_admin_referer( 'bulk-posts' );

		$_REQUEST['post'] = flamingo_get_all_ids_in_trash(
			Flamingo_Inbound_Message::post_type
		);

		$action = 'delete';
	}

	if ( 'delete' === $action and ! empty( $_REQUEST['post'] ) ) {
		if ( ! is_array( $_REQUEST['post'] ) ) {
			check_admin_referer(
				'flamingo-delete-inbound-message_' . $_REQUEST['post']
			);
		} else {
			check_admin_referer( 'bulk-posts' );
		}

		$deleted = 0;

		foreach ( (array) $_REQUEST['post'] as $post ) {
			$post = new Flamingo_Inbound_Message( $post );

			if ( empty( $post ) ) {
				continue;
			}

			if (
				! current_user_can( 'flamingo_delete_inbound_message', $post->id() )
			) {
				wp_die(
					wp_kses_data( __( 'You are not allowed to delete this item.', 'flamingo' ) )
				);
			}

			if ( ! $post->delete() ) {
				wp_die(
					wp_kses_data( __( 'Error in deleting.', 'flamingo' ) )
				);
			}

			$deleted += 1;
		}

		if ( ! empty( $deleted ) ) {
			$redirect_to = add_query_arg(
				array(
					'message' => 'inbounddeleted',
				),
				$redirect_to
			);
		}

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'spam' === $action and ! empty( $_REQUEST['post'] ) ) {
		if ( ! is_array( $_REQUEST['post'] ) ) {
			check_admin_referer(
				'flamingo-spam-inbound-message_' . $_REQUEST['post']
			);
		} else {
			check_admin_referer( 'bulk-posts' );
		}

		$submitted = 0;

		foreach ( (array) $_REQUEST['post'] as $post ) {
			$post = new Flamingo_Inbound_Message( $post );

			if ( empty( $post ) ) {
				continue;
			}

			if (
				! current_user_can( 'flamingo_spam_inbound_message', $post->id() )
			) {
				wp_die(
					wp_kses_data( __( 'You are not allowed to spam this item.', 'flamingo' ) )
				);
			}

			if ( $post->spam() ) {
				$submitted += 1;
			}
		}

		if ( ! empty( $submitted ) ) {
			$redirect_to = add_query_arg(
				array(
					'message' => 'inboundspammed',
				),
				$redirect_to
			);
		}

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'unspam' === $action and ! empty( $_REQUEST['post'] ) ) {
		if ( ! is_array( $_REQUEST['post'] ) ) {
			check_admin_referer(
				'flamingo-unspam-inbound-message_' . $_REQUEST['post']
			);
		} else {
			check_admin_referer( 'bulk-posts' );
		}

		$submitted = 0;

		foreach ( (array) $_REQUEST['post'] as $post ) {
			$post = new Flamingo_Inbound_Message( $post );

			if ( empty( $post ) ) {
				continue;
			}

			if (
				! current_user_can( 'flamingo_unspam_inbound_message', $post->id() )
			) {
				wp_die(
					wp_kses_data( __( 'You are not allowed to unspam this item.', 'flamingo' ) )
				);
			}

			if ( $post->unspam() ) {
				$submitted += 1;
			}
		}

		if ( ! empty( $submitted ) ) {
			$redirect_to = add_query_arg(
				array(
					'message' => 'inboundunspammed',
				),
				$redirect_to
			);
		}

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( ! empty( $_GET['export'] ) ) {
		check_admin_referer( 'bulk-posts' );

		$csv_class = apply_filters( 'flamingo_inbound_csv_class',
			'Flamingo_Inbound_CSV'
		);

		if ( is_subclass_of( $csv_class, 'Flamingo_CSV' ) ) {
			$csv_obj = new $csv_class;
			$csv_obj->send_http_headers();
			$csv_obj->print_data();
		}

		exit();
	}

	if ( 'edit' === $action ) {
		$post_id = (int) ( $_REQUEST['post'] ?? '0' );

		if ( ! $post_id ) {
			wp_safe_redirect( $redirect_to );
			exit();
		}

		if (
			! current_user_can( 'flamingo_edit_inbound_message', $post_id ) or
			Flamingo_Inbound_Message::post_type !== get_post_type( $post_id )
		) {
			wp_die(
				wp_kses_data( __( 'You are not allowed to edit this item.', 'flamingo' ) )
			);
		}

		$post = new Flamingo_Inbound_Message( $post_id );

		add_meta_box( 'submitdiv', __( 'Status', 'flamingo' ),
			'flamingo_inbound_submit_meta_box', null, 'side', 'core'
		);

		if ( ! empty( $post->fields ) ) {
			add_meta_box( 'inboundfieldsdiv', __( 'Fields', 'flamingo' ),
				'flamingo_inbound_fields_meta_box', null, 'normal', 'core'
			);
		}

		if ( ! empty( $post->consent ) ) {
			add_meta_box( 'inboundconsentdiv', __( 'Consent', 'flamingo' ),
				'flamingo_inbound_consent_meta_box', null, 'normal', 'core'
			);
		}

		if ( ! empty( $post->recaptcha ) ) {
			add_meta_box( 'inboundrecaptchadiv', __( 'reCAPTCHA', 'flamingo' ),
				'flamingo_inbound_recaptcha_meta_box', null, 'normal', 'core'
			);
		}

		if ( ! empty( $post->meta ) ) {
			add_meta_box( 'inboundmetadiv', __( 'Meta', 'flamingo' ),
				'flamingo_inbound_meta_meta_box', null, 'normal', 'core'
			);
		}

	} else {
		if ( ! class_exists( 'Flamingo_Inbound_Messages_List_Table' ) ) {
			require_once FLAMINGO_PLUGIN_DIR . '/admin/includes/class-inbound-messages-list-table.php';
		}

		$current_screen = get_current_screen();

		add_filter( 'manage_' . $current_screen->id . '_columns',
			array( 'Flamingo_Inbound_Messages_List_Table', 'define_columns' ),
			10, 0
		);

		add_screen_option( 'per_page', array(
			'default' => 20,
			'option' => 'flamingo_inbound_messages_per_page',
		) );
	}
}

function flamingo_inbound_admin_page() {
	if ( 'edit' === flamingo_current_action() ) {
		flamingo_inbound_edit_page();
		return;
	}

	$list_table = new Flamingo_Inbound_Messages_List_Table();
	$list_table->prepare_items();

?>
<div class="wrap">

<h1 class="wp-heading-inline"><?php
	echo esc_html( __( 'Inbound Messages', 'flamingo' ) );
?></h1>

<?php
	if ( isset( $_REQUEST['s'] ) and strlen( $_REQUEST['s'] ) ) {
		echo sprintf(
			'<span class="subtitle">%s</span>',
			wp_kses_data( sprintf(
				/* translators: %s: Search query. */
				__( 'Search results for: <strong>%s</strong>', 'flamingo' ),
				esc_html( $_REQUEST['s'] )
			) )
		);
	}
?>

<hr class="wp-header-end">

<?php do_action( 'flamingo_admin_updated_message' ); ?>

<?php $list_table->views(); ?>

<form method="get" action="">
	<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
	<input type="hidden" name="post_status" value="<?php echo isset( $_REQUEST['post_status'] ) ? esc_attr( $_REQUEST['post_status'] ) : ''; ?>" />
	<?php $list_table->search_box( __( 'Search messages', 'flamingo' ), 'flamingo-inbound' ); ?>
	<?php $list_table->display(); ?>
</form>

</div>
<?php
}

function flamingo_inbound_edit_page() {
	$post = new Flamingo_Inbound_Message( $_REQUEST['post'] );

	if ( empty( $post ) ) {
		return;
	}

	require_once FLAMINGO_PLUGIN_DIR . '/admin/includes/meta-boxes.php';

	include FLAMINGO_PLUGIN_DIR . '/admin/edit-inbound-form.php';
}
