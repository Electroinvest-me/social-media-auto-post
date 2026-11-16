<?php
/**
 * Admin AJAX actions.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Admin;

use Advert4\SocialMediaAutoPost\Api\ApiException;
use Advert4\SocialMediaAutoPost\Api\ProfileRepository;
use Advert4\SocialMediaAutoPost\Contracts\Hookable;
use Advert4\SocialMediaAutoPost\Settings\Settings;
use Advert4\SocialMediaAutoPost\Sharing\PayloadBuilder;
use Advert4\SocialMediaAutoPost\Sharing\PostOverrides;
use Advert4\SocialMediaAutoPost\Sharing\Publisher;
use Advert4\SocialMediaAutoPost\Sharing\Queue;
use Advert4\SocialMediaAutoPost\Sharing\ShareDecision;
use Advert4\SocialMediaAutoPost\Sharing\ShareState;
use Advert4\SocialMediaAutoPost\Sharing\StatusSync;
use Advert4\SocialMediaAutoPost\Support\Logger;
use Advert4\SocialMediaAutoPost\Support\View;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Connection test, "Share now", status refresh, preview and log actions.
 */
final class Ajax implements Hookable {

	/**
	 * Plugin settings.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Advert4 profiles.
	 *
	 * @var ProfileRepository
	 */
	private ProfileRepository $profiles;

	/**
	 * Publisher.
	 *
	 * @var Publisher
	 */
	private Publisher $publisher;

	/**
	 * Status sync.
	 *
	 * @var StatusSync
	 */
	private StatusSync $sync;

	/**
	 * Background jobs.
	 *
	 * @var Queue
	 */
	private Queue $queue;

	/**
	 * Share rules.
	 *
	 * @var ShareDecision
	 */
	private ShareDecision $decision;

	/**
	 * Request builder.
	 *
	 * @var PayloadBuilder
	 */
	private PayloadBuilder $builder;

	/**
	 * Activity log.
	 *
	 * @var Logger
	 */
	private Logger $logger;

	/**
	 * Create the handler.
	 *
	 * @param Settings          $settings  Plugin settings.
	 * @param ProfileRepository $profiles  Advert4 profiles.
	 * @param Publisher         $publisher Publisher.
	 * @param StatusSync        $sync      Status sync.
	 * @param Queue             $queue     Background jobs.
	 * @param ShareDecision     $decision  Share rules.
	 * @param PayloadBuilder    $builder   Request builder.
	 * @param Logger            $logger    Activity log.
	 */
	public function __construct( Settings $settings, ProfileRepository $profiles, Publisher $publisher, StatusSync $sync, Queue $queue, ShareDecision $decision, PayloadBuilder $builder, Logger $logger ) {
		$this->settings  = $settings;
		$this->profiles  = $profiles;
		$this->publisher = $publisher;
		$this->sync      = $sync;
		$this->queue     = $queue;
		$this->decision  = $decision;
		$this->builder   = $builder;
		$this->logger    = $logger;
	}

	/**
	 * Register the actions.
	 */
	public function register(): void {
		add_action( 'wp_ajax_a4smap_test_connection', array( $this, 'test_connection' ) );
		add_action( 'wp_ajax_a4smap_clear_log', array( $this, 'clear_log' ) );
		add_action( 'wp_ajax_a4smap_share', array( $this, 'share' ) );
		add_action( 'wp_ajax_a4smap_panel', array( $this, 'panel' ) );
		add_action( 'wp_ajax_a4smap_preview', array( $this, 'preview' ) );
	}

	/**
	 * Reload the profiles and show them.
	 */
	public function test_connection(): void {
		$this->authorize_settings();

		try {
			$profiles = $this->profiles->all( true );
		} catch ( ApiException $exception ) {
			wp_send_json_error( array( 'message' => $exception->getMessage() ), 200 );
		}

		wp_send_json_success(
			array(
				'html'     => View::capture( 'settings/profiles', array( 'profiles' => $profiles ) ),
				'profiles' => $profiles,
			)
		);
	}

	/**
	 * Empty the activity log.
	 */
	public function clear_log(): void {
		$this->authorize_settings();
		$this->logger->clear();

		wp_send_json_success();
	}

	/**
	 * Send the post now (or again), using the fields currently in the meta box.
	 */
	public function share(): void {
		$post = $this->authorize_post();

		$this->save_submitted_overrides( $post );

		if ( ! $this->decision->can_share( $post ) ) {
			wp_send_json_error( array( 'message' => __( 'Only published posts of an enabled post type can be shared (password protected posts are skipped).', 'social-media-auto-post' ) ), 200 );
		}

		$state = ShareState::for_post( $post->ID );

		if ( $state->is_in_progress() && $state->has_remote() ) {
			wp_send_json_error( array( 'message' => __( 'Advert4 is still publishing this post.', 'social-media-auto-post' ) ), 200 );
		}

		$this->queue->cancel( $post->ID );
		$state = $this->publisher->share( $post->ID, $state->has_remote() );

		$this->send_panel( $post, $state );
	}

	/**
	 * Current status; also runs a due job and refreshes a post that is still publishing.
	 */
	public function panel(): void {
		$post  = $this->authorize_post();
		$state = ShareState::for_post( $post->ID );

		if ( $state->is_queued() && $state->attempts < 1 && $this->queue->is_due( $post->ID ) ) {
			$this->queue->cancel( $post->ID );
			$state = $this->publisher->share( $post->ID );
		} elseif ( $state->is_remote_pending() && time() - $state->updated_at >= 10 ) {
			$state = $this->sync->refresh( $post->ID );
		}

		$this->send_panel( $post, $state );
	}

	/**
	 * Preview with the fields currently in the meta box (nothing is saved).
	 */
	public function preview(): void {
		$post   = $this->authorize_post();
		$config = $this->settings->post_type( $post->post_type );
		$input  = $this->submitted_fields();

		$overrides = null === $input ? null : PostOverrides::from_request( $input, $this->builder->default_platforms( $config ) );

		wp_send_json_success(
			array(
				'html' => View::capture( 'metabox/preview', array( 'plan' => $this->builder->build( $post, $overrides ) ) ),
			)
		);
	}

	/**
	 * Respond with the status panel.
	 *
	 * @param WP_Post    $post  Post.
	 * @param ShareState $state State.
	 */
	private function send_panel( WP_Post $post, ShareState $state ): void {
		wp_send_json_success(
			array(
				'html'       => View::capture(
					'metabox/status',
					array(
						'post'      => $post,
						'state'     => $state,
						'can_share' => $this->decision->can_share( $post ),
					)
				),
				'status'     => $state->status,
				'inProgress' => $state->is_in_progress(),
			)
		);
	}

	/**
	 * Store the meta box fields sent with the request, if any.
	 *
	 * @param WP_Post $post Post.
	 */
	private function save_submitted_overrides( WP_Post $post ): void {
		$input = $this->submitted_fields();

		if ( null === $input ) {
			return;
		}

		$config = $this->settings->post_type( $post->post_type );
		PostOverrides::from_request( $input, $this->builder->default_platforms( $config ) )->save( $post->ID );
	}

	/**
	 * Meta box fields sent with the request (the nonce was verified by authorize_post()).
	 *
	 * @return array<string, mixed>|null
	 */
	private function submitted_fields(): ?array {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce checked in authorize_post(); sanitized in PostOverrides::from_request().
		$fields = isset( $_POST[ MetaBox::FIELD ] ) && is_array( $_POST[ MetaBox::FIELD ] ) ? wp_unslash( $_POST[ MetaBox::FIELD ] ) : null;

		return is_array( $fields ) ? $fields : null;
	}

	/**
	 * Check the nonce and capability of a post action.
	 */
	private function authorize_post(): WP_Post {
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified on the next line.

		check_ajax_referer( MetaBox::nonce_action( $post_id ), 'nonce' );

		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'You cannot edit this post.', 'social-media-auto-post' ) ), 403 );
		}

		return $post;
	}

	/**
	 * Check the nonce and capability of a settings action.
	 */
	private function authorize_settings(): void {
		check_ajax_referer( 'a4smap_settings', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You cannot change these settings.', 'social-media-auto-post' ) ), 403 );
		}
	}
}
