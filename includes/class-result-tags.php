<?php

namespace SMTPing\Groundhogg;

use function Groundhogg\get_db;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Result_Tags {
	const OPTION = 'gh_smtping_tag_ids';

	private static $instance;

	private $names = array(
		'safe'   => 'SMTPing: Safe',
		'review' => 'SMTPing: Review',
		'avoid'  => 'SMTPing: Avoid',
		'error'  => 'SMTPing: Error',
	);

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_init', array( $this, 'ensure' ) );
	}

	public function ensure() {
		if ( ! function_exists( 'Groundhogg\\get_db' ) ) {
			return array();
		}

		$stored = get_option( self::OPTION, array() );
		$tags_db = get_db( 'tags' );
		$ids = array();

		foreach ( $this->names as $band => $name ) {
			$id = isset( $stored[ $band ] ) ? absint( $stored[ $band ] ) : 0;
			if ( ! $id || ! $tags_db->exists( $id ) ) {
				$id = absint( $tags_db->add( array(
					'tag_name'        => $name,
					'tag_description' => __( 'Managed by the Groundhogg SMTPing integration.', 'groundhogg-smtping' ),
				) ) );
			}
			$ids[ $band ] = $id;
		}

		update_option( self::OPTION, $ids, false );
		return $ids;
	}

	public function apply( $contact, $band ) {
		$ids = $this->ensure();
		if ( empty( $ids[ $band ] ) ) {
			return false;
		}

		$contact->remove_tag( array_values( array_filter( $ids ) ) );
		return $contact->add_tag( $ids[ $band ] );
	}
}

