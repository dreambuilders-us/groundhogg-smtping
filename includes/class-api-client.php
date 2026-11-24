<?php

namespace SMTPing\Groundhogg;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class API_Client {
	const BASE_URL = 'https://api.smtping.com/api/v1';

	public static function get_api_key() {
		if ( defined( 'GH_SMTPING_API_KEY' ) && GH_SMTPING_API_KEY ) {
			return trim( (string) GH_SMTPING_API_KEY );
		}

		return trim( (string) get_option( 'gh_smtping_api_key', '' ) );
	}

	public function verify( $email ) {
		$email = sanitize_email( $email );

		if ( ! is_email( $email ) ) {
			return new WP_Error( 'gh_smtping_invalid_email', __( 'The contact does not have a valid email address.', 'groundhogg-smtping' ) );
		}

		return $this->request( 'POST', '/verify/single', array( 'email' => $email ) );
	}

	public function credits() {
		return $this->request( 'GET', '/credits' );
	}

	private function request( $method, $path, $body = null ) {
		$api_key = self::get_api_key();

		if ( '' === $api_key ) {
			return new WP_Error( 'gh_smtping_missing_key', __( 'SMTPing API key is not configured.', 'groundhogg-smtping' ) );
		}

		$args = array(
			'method'      => $method,
			'timeout'     => 20,
			'redirection' => 0,
			'headers'     => array(
				'X-API-Key'   => $api_key,
				'Accept'      => 'application/json',
				'Content-Type'=> 'application/json',
			),
		);

		if ( null !== $body ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( self::BASE_URL . $path, $args );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'gh_smtping_network_error', $response->get_error_message() );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 ) {
			$message = is_array( $data ) && ! empty( $data['error'] )
				? sanitize_text_field( $data['error'] )
				: sprintf( __( 'SMTPing returned HTTP %d.', 'groundhogg-smtping' ), $code );

			return new WP_Error( 'gh_smtping_http_' . $code, $message, array( 'status' => $code ) );
		}

		if ( ! is_array( $data ) ) {
			return new WP_Error( 'gh_smtping_bad_json', __( 'SMTPing returned an unreadable response.', 'groundhogg-smtping' ) );
		}

		return $data;
	}
}

