<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Flamingo_Contacts_List_Table extends WP_List_Table {

	public static function define_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'email' => __( 'Email', 'flamingo' ),
			'full_name' => __( 'Name', 'flamingo' ),
			'tags' => __( 'Tags', 'flamingo' ),
			'history' => __( 'History', 'flamingo' ),
			'last_contacted' => __( 'Last contact', 'flamingo' ),
		);

		$columns = apply_filters(
			'manage_flamingo_contact_posts_columns', $columns
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
			'flamingo_contacts_per_page'
		);

		$args = array(
			'posts_per_page' => $per_page,
			'offset' => ( $this->get_pagenum() - 1 ) * $per_page,
			'orderby' => 'meta_value',
			'order' => 'DESC',
			'meta_key' => '_last_contacted',
		);

		if ( ! empty( $_REQUEST['s'] ) ) {
			$args['s'] = $_REQUEST['s'];
		}

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			if ( 'email' === $_REQUEST['orderby'] ) {
				$args['meta_key'] = '_email';
			} elseif ( 'name' === $_REQUEST['orderby'] ) {
				$args['meta_key'] = '_name';
			}
		}

		if (
			! empty( $_REQUEST['order'] ) and
			'asc' === strtolower( $_REQUEST['order'] )
		) {
			$args['order'] = 'ASC';
		}

		if ( ! empty( $_REQUEST['contact_tag_id'] ) ) {
			$args['contact_tag_id'] = explode( ',', $_REQUEST['contact_tag_id'] );
		}

		$this->items = Flamingo_Contact::find( $args );

		$total_items = Flamingo_Contact::count();
		$total_pages = ceil( $total_items / $per_page );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'total_pages' => $total_pages,
			'per_page' => $per_page,
		) );
	}

	public function get_columns() {
		return get_column_headers( get_current_screen() );
	}

	protected function get_sortable_columns() {
		$columns = array(
			'email' => array( 'email', false ),
			'full_name' => array( 'name', false ),
			'last_contacted' => array( 'last_contacted', true ),
		);

		return $columns;
	}

	protected function get_bulk_actions() {
		$actions = array(
			'delete' => __( 'Delete', 'flamingo' ),
		);

		return $actions;
	}

	protected function extra_tablenav( $which ) {
		$tag = 0;

		if ( ! empty( $_REQUEST['contact_tag_id'] ) ) {
			$tag_id = explode( ',', $_REQUEST['contact_tag_id'] );

			$term = get_term( $tag_id[0], Flamingo_Contact::contact_tag_taxonomy );

			if ( ! empty( $term ) and ! is_wp_error( $term ) ) {
				$tag = $term->term_id;
			}
		}

?>
<div class="alignleft actions">
<?php
		if ( 'top' == $which ) {
			$filters = array();

			$filters[] = wp_dropdown_categories( array(
				'taxonomy' => Flamingo_Contact::contact_tag_taxonomy,
				'name' => 'contact_tag_id',
				'show_option_all' => __( 'View all tags', 'flamingo' ),
				'hide_empty' => 1,
				'hide_if_empty' => 1,
				'orderby' => 'name',
				'selected' => $tag,
			) );

			if ( array_filter( $filters ) ) {
				submit_button( __( 'Filter', 'flamingo' ),
					'secondary', false, false, array( 'id' => 'post-query-submit' )
				);
			}

			submit_button( __( 'Export', 'flamingo' ), 'secondary', 'export', false );
		}
?>
</div>
<?php
	}

	protected function column_default( $item, $column_name ) {
		do_action( 'manage_flamingo_contact_posts_custom_column',
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

	protected function column_email( $item ) {
		$edit_link = add_query_arg( array(
			'post' => $item->id(),
			'action' => 'edit',
		), menu_page_url( 'flamingo', false ) );

		if ( current_user_can( 'flamingo_edit_contact', $item->id() ) ) {
			return sprintf(
				'<strong><a class="row-title" href="%1$s" aria-label="%2$s">%3$s</a></strong>',
				esc_url( $edit_link ),
				esc_attr( sprintf(
					/* translators: %s: Item title. */
					__( '&#8220;%s&#8221; (Edit)', 'flamingo' ),
					$item->email
				) ),
				esc_html( $item->email )
			);
		} else {
			return sprintf(
				'<strong>%1$s</strong>',
				esc_html( $item->email )
			);
		}
	}

	protected function handle_row_actions( $item, $column_name, $primary ) {
		if ( $column_name !== $primary ) {
			return '';
		}

		$actions = array();

		$link = add_query_arg( array(
			'post' => $item->id(),
			'action' => 'edit',
		), menu_page_url( 'flamingo', false ) );

		if ( current_user_can( 'flamingo_edit_contact', $item->id() ) ) {
			$actions['edit'] = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( $link ),
				esc_html( __( 'Edit', 'flamingo' ) )
			);
		}

		return $this->row_actions( $actions );
	}

	protected function column_full_name( $item ) {
		return esc_html( $item->name );
	}

	protected function column_tags( $item ) {
		if ( empty( $item->tags ) ) {
			return esc_html( __( 'No tags', 'flamingo' ) );
		}

		$output = '';

		foreach ( (array) $item->tags as $tag ) {
			$term = get_term_by( 'name', $tag,
				Flamingo_Contact::contact_tag_taxonomy
			);

			if ( empty( $term ) or is_wp_error( $term ) ) {
				continue;
			}

			if ( $output ) {
				$output .= ', ';
			}

			$link = add_query_arg( array(
				'contact_tag_id' => $term->term_id,
			), menu_page_url( 'flamingo', false ) );

			$output .= sprintf( '<a href="%1$s" aria-label="%2$s">%3$s</a>',
				esc_url( $link ),
				esc_attr( $term->name ),
				esc_html( $term->name )
			);
		}

		return $output;
	}

	protected function column_history( $item ) {
		$history = array();

		// User
		if ( $user = get_user_by( 'email', $item->email ) ) {
			$link = sprintf( 'user-edit.php?user_id=%d', $user->ID );

			$history[] = sprintf(
				'<a href="%2$s">%1$s</a>',
				esc_html( __( 'User', 'flamingo' ) ),
				admin_url( $link )
			);
		}

		// Comment
		$comment_count = (int) get_comments( array(
			'count' => true,
			'author_email' => $item->email,
			'status' => 'approve',
			'type' => 'comment',
		) );

		if ( 0 < $comment_count ) {
			$link = sprintf( 'edit-comments.php?s=%s', urlencode( $item->email ) );

			$history[] = sprintf(
				'<a href="%2$s">%1$s</a>',
				esc_html( sprintf(
					/* translators: %d: Number of comments. */
					__( 'Comment (%d)', 'flamingo' ),
					$comment_count
				) ),
				admin_url( $link )
			);
		}

		// Contact channels
		$terms = get_terms( array(
			'taxonomy' => Flamingo_Inbound_Message::channel_taxonomy,
		) );

		if ( ! empty( $terms ) and ! is_wp_error( $terms ) ) {
			foreach ( (array) $terms as $term ) {
				Flamingo_Inbound_Message::find( array(
					'channel' => $term->slug,
					's' => $item->email,
				) );

				$count = (int) Flamingo_Inbound_Message::count();

				if ( ! $count ) {
					continue;
				}

				$link = add_query_arg( array(
					'channel' => $term->slug,
					's' => $item->email,
				), menu_page_url( 'flamingo_inbound', false ) );

				$history[] = sprintf(
					'<a href="%2$s">%1$s</a>',
					esc_html( sprintf(
						/* translators: 1: contact channel name, 2: contact count */
						_x( '%1$s (%2$d)', 'contact history', 'flamingo' ),
						$term->name,
						$count
					) ),
					esc_url( $link )
				);
			}
		}

		$output = '';

		foreach ( $history as $item ) {
			$output .= sprintf( '<li>%s</li>', $item );
		}

		return sprintf( '<ul class="contact-history">%s</ul>', $output );
	}

	protected function column_last_contacted( $item ) {
		if (
			empty( $item->last_contacted ) or
			'0000-00-00 00:00:00' === $item->last_contacted
		) {
			return '';
		}

		$datetime = date_create_immutable_from_format(
			'Y-m-d H:i:s',
			$item->last_contacted,
			wp_timezone()
		);

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
