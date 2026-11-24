<?php

namespace SMTPing\Groundhogg;

use Groundhogg\Preferences;
use Groundhogg\Step;
use Groundhogg\Steps\Actions\Action;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Verify_Email_Action extends Action {
	private $safe = array( 'valid', 'alias' );
	private $avoid = array( 'invalid', 'spamtrap', 'disposable', 'blacklisted', 'complainer', 'spambot', 'inbox_full', 'typo' );

	public function get_name() {
		return __( 'Verify with SMTPing', 'groundhogg-smtping' );
	}

	public function get_type() {
		return 'smtping_verify_email';
	}

	public function get_sub_group() {
		return 'crm';
	}

	public function get_description() {
		return __( 'Verify the contact’s email, store the result, and apply a Safe, Review, Avoid, or Error tag.', 'groundhogg-smtping' );
	}

	public function get_icon() {
		return GH_SMTPING_URL . 'assets/icon.svg';
	}

	public function settings( $step ) {
		echo '<p>' . esc_html__( 'Uses the site-wide API key and policy under Settings → Groundhogg SMTPing.', 'groundhogg-smtping' ) . '</p>';
		echo '<p>' . esc_html__( 'Place this immediately after the import trigger. Route subsequent actions using the SMTPing result tags.', 'groundhogg-smtping' ) . '</p>';
	}

	public function get_settings_schema() {
		return array();
	}

	public function generate_step_title( $step ) {
		return __( 'Verify email with SMTPing', 'groundhogg-smtping' );
	}

	public function validate_settings( Step $step ) {
		if ( '' === API_Client::get_api_key() ) {
			$step->add_error( 'gh_smtping_missing_key', __( 'Configure the SMTPing API key before activating this flow.', 'groundhogg-smtping' ) );
		}
	}

	public function run( $contact, $event ) {
		$result = ( new API_Client() )->verify( $contact->get_email() );

		if ( is_wp_error( $result ) ) {
			$contact->update_meta( array(
				'smtping_band'        => 'error',
				'smtping_error_code'  => $result->get_error_code(),
				'smtping_error'       => sanitize_text_field( $result->get_error_message() ),
				'smtping_verified_at' => gmdate( 'c' ),
			) );
			Result_Tags::instance()->apply( $contact, 'error' );

			return new WP_Error( 'gh_smtping_verification_failed', $result->get_error_message() );
		}

		$status = sanitize_key( isset( $result['status'] ) ? $result['status'] : 'unknown' );
		$band = $this->band_for_status( $status );

		$contact->update_meta( array(
			'smtping_status'             => $status,
			'smtping_band'               => $band,
			'smtping_status_description' => sanitize_text_field( isset( $result['statusDescription'] ) ? $result['statusDescription'] : '' ),
			'smtping_is_disposable'      => ! empty( $result['isDisposable'] ) ? 1 : 0,
			'smtping_is_free_domain'     => ! empty( $result['isFreeDomain'] ) ? 1 : 0,
			'smtping_is_role_based'      => ! empty( $result['isRoleBasedDomain'] ) ? 1 : 0,
			'smtping_is_typo'            => ! empty( $result['isTypos'] ) ? 1 : 0,
			'smtping_domain'             => sanitize_text_field( isset( $result['domain'] ) ? $result['domain'] : '' ),
			'smtping_verified_at'        => gmdate( 'c' ),
			'smtping_error_code'         => '',
			'smtping_error'              => '',
		) );

		Result_Tags::instance()->apply( $contact, $band );

		$settings = wp_parse_args( get_option( 'gh_smtping_settings', array() ), array( 'mark_avoid_unmarketable' => true ) );
		if ( 'avoid' === $band && $settings['mark_avoid_unmarketable'] ) {
			$contact->change_marketing_preference( Preferences::SPAM );
		}

		return true;
	}

	private function band_for_status( $status ) {
		if ( in_array( $status, $this->safe, true ) ) {
			return 'safe';
		}

		if ( in_array( $status, $this->avoid, true ) ) {
			return 'avoid';
		}

		return 'review';
	}
}

