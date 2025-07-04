<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Flamingo_Inbound_Messages_List_Table extends WP_List_Table {

	private $is_trash = false;
	private $is_spam = false;

	public static function define_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'subject' => __( 'Subject', 'flamingo' ),
			'from' => __( 'From', 'flamingo' ),
			'channel' => __( 'Channel', 'flamingo' ),
			'date' => __( 'Date', 'flamingo' ),
		);

		$columns = apply_filters(
			'manage_flamingo_inbound_posts_columns', $columns
		);

		return $columns;
	}

	public function __construct() {
		parent::__construct( array(
			'singular' => 'post',
			'plural' => 'posts',
			'ajax' => false,
		) );
	}

	public function prepare_items() {
		$per_page = $this->get_items_per_page(
			'flamingo_inbound_messages_per_page'
		);

		$args = array(
			'posts_per_page' => $per_page,
			'offset' => ( $this->get_pagenum() - 1 ) * $per_page,
			'orderby' => 'date',
			'order' => 'DESC',
		);

		if ( ! empty( $_REQUEST['s'] ) ) {
			$args['s'] = $_REQUEST['s'];
		}

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			if ( 'subject' === $_REQUEST['orderby'] ) {
				$args['meta_key'] = '_subject';
				$args['orderby'] = 'meta_value';
			} elseif ( 'from' === $_REQUEST['orderby'] ) {
				$args['meta_key'] = '_from';
				$args['orderby'] = 'meta_value';
			}
		}

		if (
			! empty( $_REQUEST['order'] ) and
			'asc' === strtolower( $_REQUEST['order'] )
		) {
			$args['order'] = 'ASC';
		}

		if ( ! empty( $_REQUEST['m'] ) ) {
			$args['m'] = $_REQUEST['m'];
		}

		if ( ! empty( $_REQUEST['channel_id'] ) ) {
			$args['channel_id'] = $_REQUEST['channel_id'];
		}

		if ( ! empty( $_REQUEST['channel'] ) ) {
			$args['channel'] = $_REQUEST['channel'];
		}

		if ( ! empty( $_REQUEST['post_status'] ) ) {
			if ( 'trash' === $_REQUEST['post_status'] ) {
				$args['post_status'] = 'trash';
				$this->is_trash = true;
			} elseif ( 'spam' === $_REQUEST['post_status'] ) {
				$args['post_status'] = Flamingo_Inbound_Message::spam_status;
				$this->is_spam = true;
			}
		}

		$this->items = Flamingo_Inbound_Message::find( $args );

		$total_items = Flamingo_Inbound_Message::count();
		$total_pages = ceil( $total_items / $per_page );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'total_pages' => $total_pages,
			'per_page' => $per_page,
		) );
	}

	protected function get_views() {
		$base_url = menu_page_url( 'flamingo_inbound', false );
		$link_data = array();

		// Inbox
		Flamingo_Inbound_Message::find( array(
			'post_status' => 'any',
		) );

		$posts_in_inbox = Flamingo_Inbound_Message::count();

		$inbox = sprintf(
			/* translators: %s: Number of items. */
			_nx(
				'Inbox <span class="count">(%s)</span>',
				'Inbox <span class="count">(%s)</span>',
				$posts_in_inbox, 'posts', 'flamingo'
			),
			number_format_i18n( $posts_in_inbox )
		);

		$link_data['inbox'] = array(
			'url' => $base_url,
			'label' => $inbox,
			'current' => ! $this->is_trash && ! $this->is_spam,
		);

		// Spam
		Flamingo_Inbound_Message::find( array(
			'post_status' => Flamingo_Inbound_Message::spam_status,
		) );

		$posts_in_spam = Flamingo_Inbound_Message::count();

		$spam = sprintf(
			/* translators: %s: Number of items. */
			_nx(
				'Spam <span class="count">(%s)</span>',
				'Spam <span class="count">(%s)</span>',
				$posts_in_spam, 'posts', 'flamingo'
			),
			number_format_i18n( $posts_in_spam )
		);

		$link_data['spam'] = array(
			'url' => add_query_arg( 'post_status', 'spam', $base_url ),
			'label' => $spam,
			'current' => $this->is_spam,
		);

		// Trash
		Flamingo_Inbound_Message::find( array(
			'post_status' => 'trash',
		) );

		$posts_in_trash = Flamingo_Inbound_Message::count();

		if ( $posts_in_trash ) {
			$trash = sprintf(
				/* translators: %s: Number of items. */
				_nx(
					'Trash <span class="count">(%s)</span>',
					'Trash <span class="count">(%s)</span>',
					$posts_in_trash, 'posts', 'flamingo'
				),
				number_format_i18n( $posts_in_trash )
			);

			$link_data['trash'] = array(
				'url' => add_query_arg( 'post_status', 'trash', $base_url ),
				'label' => $trash,
				'current' => $this->is_trash,
			);
		}

		return $this->get_views_links( $link_data );
	}

	public function get_columns() {
		return get_column_headers( get_current_screen() );
	}

	protected function get_sortable_columns() {
		$columns = array(
			'subject' => array( 'subject', false ),
			'from' => array( 'from', false ),
			'date' => array( 'date', true ),
		);

		return $columns;
	}

	protected function get_bulk_actions() {
		$actions = array();

		if ( $this->is_trash ) {
			$actions['untrash'] = __( 'Restore', 'flamingo' );
		}

		if ( $this->is_trash or ! EMPTY_TRASH_DAYS ) {
			$actions['delete'] = __( 'Delete permanently', 'flamingo' );
		} else {
			$actions['trash'] = __( 'Move to trash', 'flamingo' );
		}

		if ( $this->is_spam ) {
			$actions['unspam'] = __( 'Not spam', 'flamingo' );
		} else {
			$actions['spam'] = __( 'Mark as spam', 'flamingo' );
		}

		return $actions;
	}

	protected function extra_tablenav( $which ) {
		$channel = 0;

		if ( ! empty( $_REQUEST['channel_id'] ) ) {
			$term = get_term( $_REQUEST['channel_id'],
				Flamingo_Inbound_Message::channel_taxonomy
			);

			if ( ! empty( $term ) and ! is_wp_error( $term ) ) {
				$channel = $term->term_id;
			}

		} elseif ( ! empty( $_REQUEST['channel'] ) ) {
			$term = get_term_by( 'slug', $_REQUEST['channel'],
				Flamingo_Inbound_Message::channel_taxonomy
			);

			if ( ! empty( $term ) and ! is_wp_error( $term ) ) {
				$channel = $term->term_id;
			}
		}

?>
<div class="alignleft actions">
<?php
		if ( 'top' == $which ) {
			$this->months_dropdown( Flamingo_Inbound_Message::post_type );

			wp_dropdown_categories( array(
				'taxonomy' => Flamingo_Inbound_Message::channel_taxonomy,
				'name' => 'channel_id',
				'show_option_all' => __( 'View all channels', 'flamingo' ),
				'show_count' => 0,
				'hide_empty' => 1,
				'hide_if_empty' => 1,
				'orderby' => 'name',
				'hierarchical' => 1,
				'selected' => $channel,
			) );

			submit_button( __( 'Filter', 'flamingo' ),
				'secondary', false, false, array( 'id' => 'post-query-submit' )
			);

			if ( ! $this->is_spam and ! $this->is_trash ) {
				submit_button( __( 'Export', 'flamingo' ),
					'secondary', 'export', false
				);
			}
		}

		if (
			$this->is_trash and
			current_user_can( 'flamingo_delete_inbound_messages' )
		) {
			submit_button( __( 'Empty trash', 'flamingo' ),
				'button-secondary apply', 'delete_all', false
			);
		}
?>
</div>
<?php
	}

	protected function column_default( $item, $column_name ) {
		do_action( 'manage_flamingo_inbound_posts_custom_column',
			$column_name, $item->id()
		);
	}

	protected function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			$this->_args['singular'],
			$item->id()
		);
	}

	protected function column_subject( $item ) {
		if ( $this->is_trash ) {
			return sprintf( '<strong>%s</strong>', esc_html( $item->subject ) );
		}

		if ( current_user_can( 'flamingo_edit_inbound_message', $item->id() ) ) {
			$edit_link = add_query_arg( array(
				'post' => $item->id(),
				'action' => 'edit',
			), menu_page_url( 'flamingo_inbound', false ) );

			return sprintf(
				'<strong><a class="row-title" href="%1$s" aria-label="%2$s">%3$s</a></strong>',
				esc_url( $edit_link ),
				esc_attr( sprintf(
					/* translators: %s: Item title. */
					__( '&#8220;%s&#8221; (Edit)', 'flamingo' ),
					$item->subject
				) ),
				esc_html( $item->subject )
			);
		}

		return sprintf( '<strong>%1$s</strong>',
			esc_html( $item->subject )
		);
	}

	protected function handle_row_actions( $item, $column_name, $primary ) {
		if ( $column_name !== $primary ) {
			return '';
		}

		$actions = array();

		if ( current_user_can( 'flamingo_edit_inbound_message', $item->id() ) ) {
			$link = add_query_arg( array(
				'post' => $item->id(),
				'action' => 'edit',
			), menu_page_url( 'flamingo_inbound', false ) );

			$actions['edit'] = sprintf( '<a href="%1$s">%2$s</a>',
				esc_url( $link ),
				esc_html( __( 'View', 'flamingo' ) )
			);
		}

		if (
			$item->spam and
			current_user_can( 'flamingo_unspam_inbound_message', $item->id() )
		) {
			$link = add_query_arg( array(
				'post' => $item->id(),
				'action' => 'unspam',
			), menu_page_url( 'flamingo_inbound', false ) );

			$link = wp_nonce_url( $link,
				'flamingo-unspam-inbound-message_' . $item->id()
			);

			$actions['unspam'] = sprintf( '<a href="%1$s">%2$s</a>',
				esc_url( $link ),
				esc_html( __( 'Not spam', 'flamingo' ) )
			);
		}

		if (
			! $item->spam and
			current_user_can( 'flamingo_spam_inbound_message', $item->id() )
		) {
			$link = add_query_arg( array(
				'post' => $item->id(),
				'action' => 'spam',
			), menu_page_url( 'flamingo_inbound', false ) );

			$link = wp_nonce_url( $link,
				'flamingo-spam-inbound-message_' . $item->id()
			);

			$actions['spam'] = sprintf( '<a href="%1$s">%2$s</a>',
				esc_url( $link ),
				esc_html( __( 'Spam', 'flamingo' ) )
			);
		}

		return $this->row_actions( $actions );
	}

	protected function column_from( $item ) {
		return esc_html( $item->from );
	}

	protected function column_channel( $item ) {
		if ( empty( $item->channel ) ) {
			return '';
		}

		$term = get_term_by( 'slug', $item->channel,
			Flamingo_Inbound_Message::channel_taxonomy
		);

		if ( empty( $term ) or is_wp_error( $term ) ) {
			return $item->channel;
		}

		$output = '';

		$ancestors = (array) get_ancestors( $term->term_id,
			Flamingo_Inbound_Message::channel_taxonomy
		);

		while ( $parent = array_pop( $ancestors ) ) {
			$parent = get_term( $parent, Flamingo_Inbound_Message::channel_taxonomy );

			if ( empty( $parent ) or is_wp_error( $parent ) ) {
				continue;
			}

			$link = add_query_arg( array(
				'channel' => $parent->slug,
			), menu_page_url( 'flamingo_inbound', false ) );

			$output .= sprintf( '<a href="%1$s" aria-label="%2$s">%3$s</a> / ',
				esc_url( $link ),
				esc_attr( $parent->name ),
				esc_html( $parent->name )
			);
		}

		$link = add_query_arg( array(
			'channel' => $term->slug,
		), menu_page_url( 'flamingo_inbound', false ) );

		$output .= sprintf( '<a href="%1$s" aria-label="%2$s">%3$s</a>',
			esc_url( $link ),
			esc_attr( $term->name ),
			esc_html( $term->name )
		);

		return $output;
	}

	protected function column_date( $item ) {
		$datetime = get_post_datetime( $item->id() );

		if ( false === $datetime ) {
			return '';
		}

		$t_time = sprintf(
			/* translators: 1: date, 2: time */
			__( '%1$s at %2$s', 'flamingo' ),
			/* translators: date format, see https://www.php.net/date */
			$datetime->format( __( 'Y/m/d', 'flamingo' ) ),
			/* translators: time format, see https://www.php.net/date */
			$datetime->format( __( 'g:i a', 'flamingo' ) )
		);

		return $t_time;
	}
}
