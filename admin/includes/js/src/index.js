import { __ } from '@wordpress/i18n';

document.addEventListener( 'DOMContentLoaded', event => {

	document.querySelectorAll(
		'.submitdelete'
	).forEach( anchor => {
		anchor.addEventListener( 'click', event => {
			const confirmed = window.confirm(
				__( "You are about to delete this item.\n 'Cancel' to stop, 'OK' to delete.", 'flamingo' )
			);

			if ( confirmed ) {
				return true;
			} else {
				event.preventDefault();
			}
		} );
	} );

	postboxes.add_postbox_toggles( flamingo.screenId );
} );
