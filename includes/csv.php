<?php

abstract class Flamingo_CSV {

	public function get_file_name() {
		return 'flamingo.csv';
	}

	public function send_http_headers() {
		$filename = $this->get_file_name();
		$charset = get_option( 'blog_charset' );

		header( "Content-Description: File Transfer" );
		header( "Content-Disposition: attachment; filename=$filename" );
		header( "Content-Type: text/csv; charset=$charset" );
	}

	public function print_data() {
		echo '';
	}

}


class Flamingo_Contact_CSV extends Flamingo_CSV {

	public function get_file_name() {
		return sprintf(
			'%1$s-flamingo-contact-%2$s.csv',
			sanitize_key( get_bloginfo( 'name' ) ),
			wp_date( 'Y-m-d' )
		);
	}

	public function print_data() {
		$labels = array(
			__( 'Email', 'flamingo' ),
			__( 'Full name', 'flamingo' ),
			__( 'First name', 'flamingo' ),
			__( 'Last name', 'flamingo' ),
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo flamingo_csv_row( $labels );

		$args = array(
			'posts_per_page' => -1,
			'orderby' => 'meta_value',
			'order' => 'ASC',
			'meta_key' => '_email',
		);

		if ( ! empty( $_GET['s'] ) ) {
			$args['s'] = $_GET['s'];
		}

		if ( ! empty( $_GET['orderby'] ) ) {
			if ( 'email' === $_GET['orderby'] ) {
				$args['meta_key'] = '_email';
			} elseif ( 'name' === $_GET['orderby'] ) {
				$args['meta_key'] = '_name';
			}
		}

		if (
			! empty( $_GET['order'] ) and
			'asc' === strtolower( $_GET['order'] )
		) {
			$args['order'] = 'ASC';
		}

		if ( ! empty( $_GET['contact_tag_id'] ) ) {
			$args['contact_tag_id'] = explode( ',', $_GET['contact_tag_id'] );
		}

		$items = Flamingo_Contact::find( $args );

		foreach ( $items as $item ) {
			echo "\r\n";

			$row = array(
				$item->email,
				$item->get_prop( 'name' ),
				$item->get_prop( 'first_name' ),
				$item->get_prop( 'last_name' ),
			);

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo flamingo_csv_row( $row );
		}
	}

}


class Flamingo_Inbound_CSV extends Flamingo_CSV {

	public function get_file_name() {
		return sprintf(
			'%1$s-flamingo-inbound-%2$s.csv',
			sanitize_key( get_bloginfo( 'name' ) ),
			wp_date( 'Y-m-d' )
		);
	}

	public function print_data() {
		$args = array(
			'posts_per_page' => -1,
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

		$items = Flamingo_Inbound_Message::find( $args );

		if ( empty( $items ) ) {
			return;
		}

		$labels = array_keys( $items[0]->fields );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo flamingo_csv_row(
			array_merge( $labels, array( __( 'Date', 'flamingo' ) ) )
		);

		foreach ( $items as $item ) {
			echo "\r\n";

			$row = array();

			foreach ( $labels as $label ) {
				$col = isset( $item->fields[$label] ) ? $item->fields[$label] : '';

				if ( is_array( $col ) ) {
					$col = flamingo_array_flatten( $col );
					$col = array_filter( array_map( 'trim', $col ) );
					$col = implode( ', ', $col );
				}

				$row[] = $col;
			}

			$row[] = get_post_time( 'c', false, $item->id() ); // Date

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo flamingo_csv_row( $row );
		}
	}

}


/**
 * Retrieves text that represents a CSV row.
 */
function flamingo_csv_row( $inputs = array() ) {
	$row = array();

	foreach ( $inputs as $input ) {
		$row[] = apply_filters( 'flamingo_csv_quotation', $input );
	}

	$separator = apply_filters( 'flamingo_csv_value_separator', ',' );

	return implode( $separator, $row );
}


add_filter( 'flamingo_csv_quotation', 'flamingo_csv_quote', 10, 1 );

/**
 * Retrieves text that represents a CSV cell with quotation.
 */
function flamingo_csv_quote( $input ) {
	$prefix = apply_filters( 'flamingo_csv_field_prefix', '', $input );
	$input = trim( sprintf( '%1$s %2$s', $prefix, $input ) );

	return sprintf( '"%s"', str_replace( '"', '""', $input ) );
}


add_filter( 'flamingo_csv_field_prefix',
	'flamingo_csv_field_prefix_text',
	10, 2
);

/**
 * Adds a security alert at the head of a cell.
 *
 * @see https://contactform7.com/2020/01/15/heads-up-about-spreadsheet-vulnerabilities/
 */
function flamingo_csv_field_prefix_text( $prefix, $input ) {
	$formula_triggers = array( '=', '+', '-', '@' );

	if ( in_array( substr( $input, 0, 1 ), $formula_triggers, true ) ) {
		/* translators: %s: URL */
		$prefix = __( '(Security Alert: Suspicious content is detected. See %s for details.)', 'flamingo' );

		if ( in_array( substr( $prefix, 0, 1 ), $formula_triggers, true ) ) {
			$prefix = '\'' . $prefix;
		}

		$prefix = sprintf(
			$prefix,
			esc_url( __( 'https://contactform7.com/heads-up-about-spreadsheet-vulnerabilities', 'flamingo' ) )
		);
	}

	return $prefix;
}
