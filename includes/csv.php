<?php

function flamingo_csv_row( $inputs = array() ) {
	$row = array();

	foreach ( $inputs as $input ) {
		$row[] = apply_filters( 'flamingo_csv_quotation', $input );
	}

	$separator = apply_filters( 'flamingo_csv_value_separator', ',' );

	return implode( $separator, $row );
}

add_filter( 'flamingo_csv_quotation', 'flamingo_csv_quote' );

function flamingo_csv_quote( $input ) {

	// https://www.contextis.com/en/blog/comma-separated-vulnerabilities
	if ( in_array( substr( $input, 0, 1 ), array( '=', '+', '-', '@' ), true ) ) {
		$prefix = apply_filters( 'flamingo_csv_prefix_in_cell',
			__( "(Security Alert: Suspicious content is detected. See https://contactform7.com/flamingo-211/ for details.)", 'flamingo' )
		);

		$input = trim( sprintf( '%1$s %2$s', $prefix, $input ) );
	}

	return sprintf( '"%s"', str_replace( '"', '""', $input ) );
}
