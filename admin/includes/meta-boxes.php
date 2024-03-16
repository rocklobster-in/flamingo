<?php

function flamingo_contact_submit_meta_box( $post ) {
?>
<div class="submitbox" id="submitlink">
<div id="major-publishing-actions">

<div id="delete-action">
<?php
	if ( current_user_can( 'flamingo_delete_contact', $post->id() ) ) {
		$delete_text = __( 'Delete', 'flamingo' );

		$delete_link = add_query_arg(
			array(
				'post' => $post->id(),
				'action' => 'delete',
			),
			menu_page_url( 'flamingo', false )
		);

		$delete_link = wp_nonce_url(
			$delete_link,
			'flamingo-delete-contact_' . $post->id()
		);

?><a class="submitdelete deletion" href="<?php echo esc_url( $delete_link ); ?>" onclick="if (confirm('<?php echo esc_js( sprintf( __( "You are about to delete this contact '%s'\n 'Cancel' to stop, 'OK' to delete.", 'flamingo' ), $post->email ) ); ?>')) {return true;} return false;"><?php echo esc_html( $delete_text ); ?></a><?php } ?>
</div>

<div id="publishing-action">
<span class="spinner"></span>
<?php if ( ! empty( $post->id() ) ) : ?>
	<input name="save" type="submit" class="button-primary" id="publish" tabindex="4" accesskey="p" value="<?php echo esc_attr( __( 'Update contact', 'flamingo' ) ); ?>" />
<?php else : ?>
	<input name="save" type="submit" class="button-primary" id="publish" tabindex="4" accesskey="p" value="<?php echo esc_attr( __( 'Add contact', 'flamingo' ) ); ?>" />
<?php endif; ?>
</div>

<div class="clear"></div>
</div><!-- #major-publishing-actions -->

<div class="clear"></div>
</div>
<?php
}

function flamingo_contact_tags_meta_box( $post ) {
	$taxonomy = get_taxonomy( Flamingo_Contact::contact_tag_taxonomy );

	if ( ! $taxonomy ) {
		return;
	}

	$tags = wp_get_post_terms( $post->id(), $taxonomy->name );
	$tag_names = $tag_ids = array();

	if ( ! empty( $tags ) and ! is_wp_error( $tags ) ) {
		foreach( $tags as $tag ) {
			$tag_names[] = $tag->name;
			$tag_ids[] = $tag->term_id;
		}
	}

	$tag_names = implode( ', ', $tag_names );

	$most_used_tags = get_terms( Flamingo_Contact::contact_tag_taxonomy, array(
		'orderby' => 'count',
		'order' => 'DESC',
		'number' => 10,
		'exclude' => $tag_ids,
		'fields' => 'names',
	) );

	if ( is_wp_error( $most_used_tags ) ) {
		$most_used_tags = array();
	}

?>
<div class="tagsdiv" id="<?php echo esc_attr( $taxonomy->name ); ?>">
<textarea name="<?php echo "tax_input[$taxonomy->name]"; ?>" rows="3" cols="20" class="the-tags" id="tax-input-<?php echo $taxonomy->name; ?>"><?php echo esc_textarea( $tag_names ); ?></textarea>

<p class="howto"><?php echo esc_html( __( 'Separate tags with commas', 'flamingo' ) ); ?></p>

<?php if ( $most_used_tags ) : ?>
<p class="howto"><?php echo esc_html( __( 'Choose from the most used tags', 'flamingo' ) ); ?>
<br />

<?php foreach ( $most_used_tags as $tag ) {
	echo '<a href="#" class="append-this-to-contact-tags" onclick="appendTag( this.text )">' . esc_html( $tag ) . '</a> ';
} ?>
</p>
<script>
const appendTag = ( tag ) => {
	const tagsInput = document.querySelector(
		'#tax-input-<?php echo esc_js( $taxonomy->name ); ?>'
	);

	const tags = tagsInput.value.split( /\s*,\s*/ );
	tags.push( tag );
	tagsInput.value = tags.filter( tag => '' !== tag ).join( ', ' );

	return false;
};
</script>
<?php endif; ?>
</div>
<?php
}

function flamingo_inbound_submit_meta_box( $post ) {
?>
<div class="submitbox" id="submitinbound">
<div id="minor-publishing">
<div id="misc-publishing-actions">
	<fieldset class="misc-pub-section" id="comment-status-radio">
	<legend class="screen-reader-text"><?php echo esc_html( __( 'Inbound message status', 'flamingo' ) ); ?></legend>
	<label><input type="radio"<?php checked( $post->spam, true ); ?> name="inbound[status]" value="spam" /><?php echo esc_html( __( 'Spam', 'flamingo' ) ); ?></label><br />
	<label><input type="radio"<?php checked( $post->spam, false ); ?> name="inbound[status]" value="ham" /><?php echo esc_html( __( 'Not spam', 'flamingo' ) ); ?></label>
	</fieldset>

	<div class="misc-pub-section curtime misc-pub-curtime">
	<span id="timestamp">
<?php
	$submitted_timestamp = get_post_timestamp( $post->id() );

	$submitted_on = sprintf(
		/* translators: Publish box date string. 1: Date, 2: Time. */
		__( '%1$s at %2$s', 'flamingo' ),
		wp_date(
			/* translators: Publish box date format, see https://www.php.net/date */
			_x( 'M j, Y', 'publish box date format', 'flamingo' ),
			$submitted_timestamp
		),
		wp_date(
			/* translators: Publish box time format, see https://www.php.net/date */
			_x( 'H:i', 'publish box time format', 'flamingo' ),
			$submitted_timestamp
		)
	);

	echo sprintf(
		/* translators: %s: message submission date */
		esc_html( __( 'Submitted on: %s', 'flamingo' ) ),
		'<b>' . esc_html( $submitted_on ) . '</b>'
	);
?>
	</span>
	</div>
<?php
	if ( ! empty( $post->submission_status ) ) {
		echo '<div class="misc-pub-section submission-status">', "\n";

		$submission_status = sprintf(
			/* translators: %s: Result of the submission. */
			esc_html( __( 'Submission result: %s', 'flamingo' ) ),
			sprintf( '<b>%s</b>', esc_html( $post->submission_status ) )
		);

		echo sprintf(
			'<span class="dashicons-before %1$s"> %2$s</span>',
			in_array( $post->submission_status, array( 'mail_failed', 'spam' ) )
				? 'dashicons-no' : 'dashicons-yes',
			$submission_status
		);

		echo '</div>', "\n";
	}

	if ( ! empty( $post->spam_log ) ) {
		echo '<div class="misc-pub-section spam-log">', "\n";

		foreach ( (array) $post->spam_log as $log ) {
			$agent = isset( $log['agent'] ) ? trim( $log['agent'] ) : '';
			$reason = isset( $log['reason'] ) ? trim( $log['reason'] ) : '';

			if ( '' !== $reason ) {
				$reason = sprintf(
					/* translators: %s: reason why this message is regarded as spam */
					__( 'Spam log: %s', 'flamingo' ),
					$reason
				);

				echo sprintf(
					'<span class="dashicons-before dashicons-shield %1$s"> %2$s</span>',
					esc_attr( $agent ),
					esc_html( $reason )
				);
			}
		}

		echo '</div>', "\n";
	}
?>
</div><!-- #misc-publishing-actions -->

<div class="clear"></div>
</div><!-- #minor-publishing -->

<div id="major-publishing-actions">
	<div id="delete-action">
<?php
	if ( current_user_can( 'flamingo_delete_inbound_message', $post->id() ) ) {
		if ( ! EMPTY_TRASH_DAYS ) {
			$delete_text = __( 'Delete permanently', 'flamingo' );
		} else {
			$delete_text = __( 'Move to trash', 'flamingo' );
		}

		$delete_link = add_query_arg(
			array(
				'post' => $post->id(),
				'action' => 'trash',
			),
			menu_page_url( 'flamingo_inbound', false )
		);

		$delete_link = wp_nonce_url(
			$delete_link,
			'flamingo-trash-inbound-message_' . $post->id()
		);

		echo sprintf( '<a href="%1$s" class="submitdelete deletion">%2$s</a>',
			esc_url( $delete_link ),
			esc_html( $delete_text )
		);
	}
?>
	</div>

	<div id="publishing-action">
<?php
	submit_button( __( 'Update', 'flamingo' ), 'primary large', 'save', false );
?>
	</div>

	<div class="clear"></div>
</div><!-- #major-publishing-actions -->
</div>
<?php
}

function flamingo_contact_name_meta_box( $post ) {
?>
<table class="form-table">
<tbody>

<tr class="contact-prop">
<th><label for="contact_name"><?php echo esc_attr( __( 'Full name', 'flamingo' ) ); ?></th>
<td><input type="text" name="contact[name]" id="contact_name" value="<?php echo esc_attr( $post->get_prop( 'name' ) ); ?>" class="widefat" /></td>
</tr>

<tr class="contact-prop">
<th><label for="contact_first_name"><?php echo esc_attr( __( 'First name', 'flamingo' ) ); ?></th>
<td><input type="text" name="contact[first_name]" id="contact_first_name" value="<?php echo esc_attr( $post->get_prop( 'first_name' ) ); ?>" class="widefat" /></td>
</tr>

<tr class="contact-prop">
<th><label for="contact_last_name"><?php echo esc_attr( __( 'Last name', 'flamingo' ) ); ?></th>
<td><input type="text" name="contact[last_name]" id="contact_last_name" value="<?php echo esc_attr( $post->get_prop( 'last_name' ) ); ?>" class="widefat" /></td>
</tr>

</tbody>
</table>
<?php
}

function flamingo_inbound_fields_meta_box( $post ) {
?>
<table class="widefat message-fields striped">
<tbody>

<?php foreach ( (array) $post->fields as $key => $value ) : ?>
<tr>
<td class="field-title"><?php echo esc_html( $key ); ?></td>
<td class="field-value"><?php echo flamingo_htmlize( $value ); ?></td>
</tr>
<?php endforeach; ?>

</tbody>
</table>
<?php
}

function flamingo_inbound_consent_meta_box( $post ) {
	$consent = $post->consent;

	if ( empty( $consent ) ) {
		return;
	}

?>
<table class="widefat message-fields striped">
<tbody>

<?php foreach ( (array) $consent as $key => $value ) : ?>
<tr>
<td class="field-title"><?php echo esc_html( $key ); ?></td>
<td class="field-value"><?php echo wp_kses( $value, wp_kses_allowed_html( 'data' ) ); ?></td>
</tr>
<?php endforeach; ?>

</tbody>
</table>
<?php
}

function flamingo_inbound_recaptcha_meta_box( $post ) {
?>
<table class="widefat message-fields striped">
<tbody>

<?php foreach ( (array) $post->recaptcha as $key => $value ) : ?>
<tr>
<td class="field-title"><?php echo esc_html( $key ); ?></td>
<td class="field-value"><?php echo esc_html( wp_json_encode( $value, JSON_PRETTY_PRINT ) ); ?></td>
</tr>
<?php endforeach; ?>

</tbody>
</table>
<?php
}

function flamingo_inbound_meta_meta_box( $post ) {
?>
<table class="widefat message-fields striped">
<tbody>

<?php foreach ( (array) $post->meta as $key => $value ) : ?>
<tr>
<td class="field-title"><?php echo esc_html( $key ); ?></td>
<td class="field-value"><?php echo flamingo_htmlize( $value ); ?></td>
</tr>
<?php endforeach; ?>

</tbody>
</table>
<?php
}
