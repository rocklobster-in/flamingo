<?php

class Flamingo_Inbound_Message {

	const post_type = 'flamingo_inbound';
	const spam_status = 'flamingo-spam';
	const channel_taxonomy = 'flamingo_inbound_channel';

	private static $found_items = 0;

	private $id;
	public $channel;
	public $submission_status;
	public $subject;
	public $from;
	public $from_name;
	public $from_email;
	public $fields;
	public $meta;
	public $akismet;
	public $recaptcha;
	public $spam;
	public $spam_log;
	public $consent;
	private $timestamp = null;
	private $hash = null;

	public static function register_post_type() {
		register_post_type( self::post_type, array(
			'labels' => array(
				'name' => __( 'Flamingo Inbound Messages', 'flamingo' ),
				'singular_name' => __( 'Flamingo Inbound Message', 'flamingo' ),
			),
			'rewrite' => false,
			'query_var' => false,
		) );

		register_post_status( self::spam_status, array(
			'label' => __( 'Spam', 'flamingo' ),
			'public' => false,
			'exclude_from_search' => true,
			'show_in_admin_all_list' => false,
			'show_in_admin_status_list' => true,
		) );

		register_taxonomy( self::channel_taxonomy, self::post_type, array(
			'labels' => array(
				'name' => __( 'Flamingo Inbound Message Channels', 'flamingo' ),
				'singular_name' => __( 'Flamingo Inbound Message Channel', 'flamingo' ),
			),
			'public' => false,
			'hierarchical' => true,
			'rewrite' => false,
			'query_var' => false,
		) );
	}

	public static function find( $args = '' ) {
		$defaults = array(
			'posts_per_page' => 10,
			'offset' => 0,
			'orderby' => 'ID',
			'order' => 'ASC',
			'meta_key' => '',
			'meta_value' => '',
			'post_status' => 'any',
			'tax_query' => array(),
			'channel' => '',
			'channel_id' => 0,
			'hash' => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$args['post_type'] = self::post_type;

		if ( ! empty( $args['channel_id'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => self::channel_taxonomy,
				'terms' => absint( $args['channel_id'] ),
				'field' => 'term_id',
			);
		}

		if ( ! empty( $args['channel'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => self::channel_taxonomy,
				'terms' => $args['channel'],
				'field' => 'slug',
			);
		}

		if ( ! empty( $args['hash'] ) ) {
			$args['meta_query'][] = array(
				'key' => '_hash',
				'value' => $args['hash'],
			);
		}

		$q = new WP_Query();
		$posts = $q->query( $args );

		self::$found_items = $q->found_posts;

		$objs = array();

		foreach ( (array) $posts as $post ) {
			$objs[] = new self( $post );
		}

		return $objs;
	}

	public static function count( $args = '' ) {
		if ( $args ) {
			$args = wp_parse_args( $args, array(
				'offset' => 0,
				'channel' => '',
				'channel_id' => 0,
				'post_status' => 'publish',
			) );

			self::find( $args );
		}

		return absint( self::$found_items );
	}

	public static function add( $args = '' ) {
		$defaults = array(
			'channel' => '',
			'status' => '',
			'subject' => '',
			'from' => '',
			'from_name' => '',
			'from_email' => '',
			'fields' => array(),
			'meta' => array(),
			'akismet' => array(),
			'recaptcha' => array(),
			'spam' => false,
			'spam_log' => array(),
			'consent' => array(),
			'timestamp' => null,
			'posted_data_hash' => null,
		);

		$args = apply_filters( 'flamingo_add_inbound',
			wp_parse_args( $args, $defaults )
		);

		$obj = new self();

		$obj->channel = $args['channel'];
		$obj->submission_status = $args['status'];
		$obj->subject = $args['subject'];
		$obj->from = $args['from'];
		$obj->from_name = $args['from_name'];
		$obj->from_email = $args['from_email'];
		$obj->fields = $args['fields'];
		$obj->meta = $args['meta'];
		$obj->akismet = $args['akismet'];
		$obj->recaptcha = $args['recaptcha'];
		$obj->spam = $args['spam'];
		$obj->spam_log = $args['spam_log'];
		$obj->consent = $args['consent'];

		if ( $args['timestamp'] ) {
			$obj->timestamp = $args['timestamp'];
		}

		if ( $args['posted_data_hash'] ) {
			$obj->hash = $args['posted_data_hash'];
		}

		$obj->save();

		return $obj;
	}

	public function __construct( $post = null ) {
		if ( ! empty( $post ) and $post = get_post( $post ) ) {
			$this->id = $post->ID;
			$this->subject = get_post_meta( $post->ID, '_subject', true );
			$this->from = get_post_meta( $post->ID, '_from', true );
			$this->from_name = get_post_meta( $post->ID, '_from_name', true );
			$this->from_email = get_post_meta( $post->ID, '_from_email', true );
			$this->fields = get_post_meta( $post->ID, '_fields', true );

			if ( ! empty( $this->fields ) ) {
				foreach ( (array) $this->fields as $key => $value ) {
					$meta_key = sanitize_key( '_field_' . $key );

					if ( metadata_exists( 'post', $post->ID, $meta_key ) ) {
						$value = get_post_meta( $post->ID, $meta_key, true );
						$this->fields[$key] = $value;
					}
				}
			}

			$this->submission_status = get_post_meta( $post->ID,
				'_submission_status', true
			);

			$this->meta = get_post_meta( $post->ID, '_meta', true );
			$this->akismet = get_post_meta( $post->ID, '_akismet', true );
			$this->recaptcha = get_post_meta( $post->ID, '_recaptcha', true );
			$this->spam_log = get_post_meta( $post->ID, '_spam_log', true );
			$this->consent = get_post_meta( $post->ID, '_consent', true );

			$terms = wp_get_object_terms( $this->id, self::channel_taxonomy );

			if ( ! empty( $terms ) and ! is_wp_error( $terms ) ) {
				$this->channel = $terms[0]->slug;
			}

			if ( self::spam_status == get_post_status( $post ) ) {
				$this->spam = true;
			} else {
				$this->spam = isset( $this->akismet['spam'] ) && $this->akismet['spam'];
			}

			$this->hash = get_post_meta( $post->ID, '_hash', true );
		}
	}

	public function __get( $name ) {
		/* translators: 1: Property, 2: Version, 3: Class, 4: Method. */
		$message = __( 'The visibility of the %1$s property has been changed in %2$s. Now the property may only be accessed by the %3$s class. You can use the %4$s method instead.', 'flamingo' );

		if ( 'id' == $name ) {
			if ( WP_DEBUG ) {
				trigger_error( sprintf(
					$message,
					sprintf( '<code>%s</code>', 'id' ),
					esc_html( __( 'Flamingo 2.2', 'flamingo' ) ),
					sprintf( '<code>%s</code>', self::class ),
					sprintf( '<code>%s</code>', 'id()' )
				) );
			}

			return $this->id;
		}
	}

	public function id() {
		return $this->id;
	}

	public function save() {
		if ( ! empty( $this->subject ) ) {
			$post_title = $this->subject;
		} else {
			$post_title = __( '(No Title)', 'flamingo' );
		}

		$post_content = array_merge(
			(array) $this->fields,
			(array) $this->consent,
			(array) $this->meta
		);

		$post_content = flamingo_array_flatten( $post_content );
		$post_content = array_filter( array_map( 'trim', $post_content ) );
		$post_content = implode( "\n", $post_content );

		$post_status = $this->spam ? self::spam_status : 'publish';

		$postarr = array(
			'ID' => absint( $this->id ),
			'post_type' => self::post_type,
			'post_status' => $post_status,
			'post_title' => $post_title,
			'post_content' => $post_content,
			'post_date' => $this->get_post_date(),
		);

		if ( $this->timestamp ) {
			$postarr['post_date'] = wp_date( 'Y-m-d H:i:s', $this->timestamp );
		}

		$post_id = wp_insert_post( $postarr );

		if ( $post_id ) {
			$this->id = $post_id;

			if ( $post_status === self::spam_status ) {

				// set spam meta time for later use to trash
				update_post_meta( $post_id, '_spam_meta_time', time() );
			} else {

				// delete spam meta time to stop trashing in cron job
				delete_post_meta( $post_id, '_spam_meta_time' );
			}

			update_post_meta( $post_id, '_submission_status',
				$this->submission_status
			);

			update_post_meta( $post_id, '_subject', $this->subject );
			update_post_meta( $post_id, '_from', $this->from );
			update_post_meta( $post_id, '_from_name', $this->from_name );
			update_post_meta( $post_id, '_from_email', $this->from_email );

			foreach ( $this->fields as $key => $value ) {
				$meta_key = sanitize_key( '_field_' . $key );
				update_post_meta( $post_id, $meta_key, $value );
				$this->fields[$key] = null;
			}

			update_post_meta( $post_id, '_fields', $this->fields );
			update_post_meta( $post_id, '_meta', $this->meta );
			update_post_meta( $post_id, '_akismet', $this->akismet );
			update_post_meta( $post_id, '_recaptcha', $this->recaptcha );
			update_post_meta( $post_id, '_spam_log', $this->spam_log );
			update_post_meta( $post_id, '_consent', $this->consent );
			update_post_meta( $post_id, '_hash', $this->hash );

			if ( term_exists( $this->channel, self::channel_taxonomy ) ) {
				wp_set_object_terms( $this->id, $this->channel,
					self::channel_taxonomy );
			}
		}

		return $post_id;
	}

	private function get_post_date() {
		if ( empty( $this->id ) ) {
			return false;
		}

		$post = get_post( $this->id );

		if ( ! $post ) {
			return false;
		}

		return $post->post_date;
	}

	public function trash() {
		if ( empty( $this->id ) ) {
			return;
		}

		if ( ! EMPTY_TRASH_DAYS ) {
			return $this->delete();
		}

		$post = wp_trash_post( $this->id );

		return (bool) $post;
	}

	public function untrash() {
		if ( empty( $this->id ) ) {
			return;
		}

		$post = wp_untrash_post( $this->id );

		return (bool) $post;
	}

	public function delete() {
		if ( empty( $this->id ) ) {
			return;
		}

		if ( $post = wp_delete_post( $this->id, true ) ) {
			$this->id = 0;
		}

		return (bool) $post;
	}

	public function spam() {
		if ( $this->spam ) {
			return;
		}

		$this->akismet_submit_spam();
		$this->spam = true;

		$user_name = get_user_option( 'user_login' );

		if ( false === $user_name ) {
			$user_name = __( 'Unknown', 'flamingo' );
		}

		if ( empty( $this->spam_log ) ) {
			$this->spam_log = array();
		}

		$this->spam_log[] = array(
			'agent' => 'flamingo',
			'reason' => sprintf(
				/* translators: %s: WordPress user name */
				__( '%s has marked this message as spam.', 'flamingo' ),
				$user_name
			),
		);

		return $this->save();
	}

	public function akismet_submit_spam() {
		if ( empty( $this->id ) or empty( $this->akismet ) ) {
			return;
		}

		if ( isset( $this->akismet['spam'] ) and $this->akismet['spam'] ) {
			return;
		}

		if ( empty( $this->akismet['comment'] ) ) {
			return;
		}

		if ( flamingo_akismet_submit_spam( $this->akismet['comment'] ) ) {
			$this->akismet['spam'] = true;
			update_post_meta( $this->id, '_akismet', $this->akismet );
			return true;
		}
	}

	public function unspam() {
		if ( ! $this->spam ) {
			return;
		}

		$this->akismet_submit_ham();
		$this->spam = false;

		return $this->save();
	}

	public function akismet_submit_ham() {
		if ( empty( $this->id ) or empty( $this->akismet ) ) {
			return;
		}

		if ( isset( $this->akismet['spam'] ) and ! $this->akismet['spam'] ) {
			return;
		}

		if ( empty( $this->akismet['comment'] ) ) {
			return;
		}

		if ( flamingo_akismet_submit_ham( $this->akismet['comment'] ) ) {
			$this->akismet['spam'] = false;
			update_post_meta( $this->id, '_akismet', $this->akismet );
			return true;
		}
	}
}
