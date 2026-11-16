<?php
/**
 * HTTP client for the Advert4 REST API.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Api;

use Advert4\SocialMediaAutoPost\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Talks to https://advert4.me/api/v1 with a workspace API key.
 */
final class Client {

	/**
	 * Plugin settings.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Create the client.
	 *
	 * @param Settings $settings Plugin settings.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Whether an API key is set.
	 */
	public function is_configured(): bool {
		return $this->settings->has_api_key();
	}

	/**
	 * Profiles of the workspace, each with its connected accounts.
	 *
	 * @return list<array<string, mixed>>
	 * @throws ApiException When the request fails.
	 */
	public function profiles(): array {
		return $this->data_list( $this->request( 'GET', '/profiles' ) );
	}

	/**
	 * Create (and publish, schedule or draft) a post.
	 *
	 * @param array<string, mixed> $payload Request body.
	 * @return array<string, mixed> The Advert4 post.
	 * @throws ApiException When the request fails.
	 */
	public function create_post( array $payload ): array {
		return $this->data( $this->request( 'POST', '/posts', $payload, $this->settings->timeout() ) );
	}

	/**
	 * One post with its status per network.
	 *
	 * @param string $uuid Advert4 post UUID.
	 * @return array<string, mixed>
	 * @throws ApiException When the request fails.
	 */
	public function get_post( string $uuid ): array {
		return $this->data( $this->request( 'GET', '/posts/' . rawurlencode( $uuid ) ) );
	}

	/**
	 * Send a request and decode the JSON response.
	 *
	 * @param string                    $method  HTTP method.
	 * @param string                    $path    Path below the API base URL.
	 * @param array<string, mixed>|null $body    JSON body.
	 * @param int                       $timeout Seconds.
	 * @return array<string, mixed>
	 * @throws ApiException When the request fails.
	 */
	private function request( string $method, string $path, ?array $body = null, int $timeout = 20 ): array {
		if ( ! $this->is_configured() ) {
			throw ApiException::not_configured();
		}

		$args = array(
			'method'      => $method,
			'timeout'     => $timeout,
			'redirection' => 0,
			'headers'     => array(
				'Authorization' => 'Bearer ' . $this->settings->api_key(),
				'Accept'        => 'application/json',
				'User-Agent'    => 'SocialMediaAutoPost/' . A4SMAP_VERSION . '; WordPress/' . get_bloginfo( 'version' ),
			),
		);

		if ( null !== $body ) {
			$args['headers']['Content-Type'] = 'application/json';
			$args['body']                    = (string) wp_json_encode( $body );
		}

		/**
		 * Filter the HTTP arguments of an Advert4 API request.
		 *
		 * @param array<string, mixed> $args   wp_remote_request() arguments.
		 * @param string               $method HTTP method.
		 * @param string               $path   API path.
		 */
		$args = (array) apply_filters( 'a4smap_http_args', $args, $method, $path );

		$response = wp_remote_request( $this->settings->api_url() . $path, $args );

		if ( is_wp_error( $response ) ) {
			throw ApiException::network( $response->get_error_message() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Escaped where the message is displayed.
		}

		$status  = (int) wp_remote_retrieve_response_code( $response );
		$decoded = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( $status < 200 || $status >= 300 ) {
			throw ApiException::from_response( $status, $decoded ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Escaped where the message is displayed.
		}

		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * The "data" object of a resource response.
	 *
	 * @param array<string, mixed> $response Decoded response.
	 * @return array<string, mixed>
	 */
	private function data( array $response ): array {
		return isset( $response['data'] ) && is_array( $response['data'] ) ? $response['data'] : $response;
	}

	/**
	 * The "data" list of a collection response.
	 *
	 * @param array<string, mixed> $response Decoded response.
	 * @return list<array<string, mixed>>
	 */
	private function data_list( array $response ): array {
		$items = $this->data( $response );

		return array_values( array_filter( $items, 'is_array' ) );
	}
}
