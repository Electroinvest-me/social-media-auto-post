<?php
/**
 * Receives result webhooks from Advert4.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Rest;

use Advert4\SocialMediaAutoPost\Contracts\Hookable;
use Advert4\SocialMediaAutoPost\Sharing\ShareState;
use Advert4\SocialMediaAutoPost\Sharing\StatusSync;
use Advert4\SocialMediaAutoPost\Support\Site;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * POST /wp-json/a4smap/v1/webhook?post={id}&token={hmac}
 *
 * The token in the URL proves the call belongs to the post. The body is
 * only used to match the Advert4 post; the status itself is fetched from
 * the API, so a forged body cannot change anything.
 */
final class WebhookController implements Hookable {

	public const NAMESPACE = 'a4smap/v1';

	/**
	 * Status sync.
	 *
	 * @var StatusSync
	 */
	private StatusSync $sync;

	/**
	 * Create the controller.
	 *
	 * @param StatusSync $sync Status sync.
	 */
	public function __construct( StatusSync $sync ) {
		$this->sync = $sync;
	}

	/**
	 * Register the route.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	/**
	 * Register the webhook route.
	 */
	public function routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/webhook',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => array( $this, 'authorize' ),
				'args'                => array(
					'post'  => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'token' => array(
						'required' => true,
						'type'     => 'string',
					),
				),
			)
		);
	}

	/**
	 * Check the token.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return true|WP_Error
	 */
	public function authorize( WP_REST_Request $request ) {
		$post_id = (int) $request->get_param( 'post' );
		$token   = (string) $request->get_param( 'token' );

		if ( $post_id > 0 && hash_equals( Site::webhook_token( $post_id ), $token ) ) {
			return true;
		}

		return new WP_Error( 'a4smap_forbidden', 'Invalid webhook token.', array( 'status' => 403 ) );
	}

	/**
	 * Refresh the post status.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public function handle( WP_REST_Request $request ): WP_REST_Response {
		$post_id = (int) $request->get_param( 'post' );
		$state   = ShareState::for_post( $post_id );
		$body    = (array) $request->get_json_params();
		$data    = isset( $body['data'] ) && is_array( $body['data'] ) ? $body['data'] : array();
		$uuid    = (string) ( $data['uuid'] ?? '' );

		if ( ! $state->has_remote() || ( '' !== $uuid && $uuid !== $state->uuid ) ) {
			return new WP_REST_Response( array( 'ok' => false ), 202 );
		}

		$state = $this->sync->refresh( $post_id );

		return new WP_REST_Response(
			array(
				'ok'     => true,
				'status' => $state->status,
			),
			200
		);
	}
}
