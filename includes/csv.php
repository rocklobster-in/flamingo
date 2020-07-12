<?php

function flamingo_csv_row( $inputs = array() ) {
	$row = array();

	foreach ( $inputs as $input ) {
		$row[] = apply_filters( 'flamingo_csv_quotation', $input );
	}

	$separator = apply_filters( 'flamingo_csv_value_separator', ',' );

	return implode( $separator, $row );
}

add_filter( 'flamingo_csv_quotation', 'flamingo_csv_quote', 10, 1 );

function flamingo_csv_quote( $input ) {
	$prefix = apply_filters( 'flamingo_csv_field_prefix', '', $input );
	$input = trim( sprintf( '%1$s %2$s', $prefix, $input ) );

	return sprintf( '"%s"', str_replace( '"', '""', $input ) );
}

/*
 * https://contactform7.com/2020/01/15/heads-up-about-spreadsheet-vulnerabilities/
 */
add_filter( 'flamingo_csv_field_prefix',
	'flamingo_csv_field_prefix_text',
	10, 2
);

function flamingo_csv_field_prefix_text( $prefix, $input ) {
	$formula_triggers = array( '=', '+', '-', '@' );

	if ( in_array( substr( $input, 0, 1 ), $formula_triggers, true ) ) {
		/* translators: %s: URL */
		$prefix = __( "(Security Alert: Suspicious content is detected. See %s for details.)", 'flamingo' );

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
