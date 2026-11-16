<?php
/**
 * Shares posts when they are published.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Sharing;

use Advert4\SocialMediaAutoPost\Contracts\Hookable;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Queues a post when it is first published, and after the editor saved the meta box.
 *
 * The block editor saves the post over the REST API first and the meta box
 * fields in a second request. A post published over REST is therefore queued
 * with a short delay; the meta box save then sends it right away, or cancels
 * it when "Share" was unticked. The classic editor, Quick Edit, scheduled
 * posts and WP-CLI send right away.
 */
final class AutoShare implements Hookable {

	private const REST_DELAY = 30;

	/**
	 * Share rules.
	 *
	 * @var ShareDecision
	 */
	private ShareDecision $decision;

	/**
	 * Background jobs.
	 *
	 * @var Queue
	 */
	private Queue $queue;

	/**
	 * Create the service.
	 *
	 * @param ShareDecision $decision Share rules.
	 * @param Queue         $queue    Background jobs.
	 */
	public function __construct( ShareDecision $decision, Queue $queue ) {
		$this->decision = $decision;
		$this->queue    = $queue;
	}

	/**
	 * Attach to post status changes.
	 */
	public function register(): void {
		add_action( 'transition_post_status', array( $this, 'on_transition' ), 20, 3 );
	}

	/**
	 * Queue a post that has just been published.
	 *
	 * @param string  $new_status New status.
	 * @param string  $old_status Old status.
	 * @param WP_Post $post       Post.
	 */
	public function on_transition( string $new_status, string $old_status, WP_Post $post ): void {
		if ( 'publish' !== $new_status || 'publish' === $old_status || wp_is_post_revision( $post ) || wp_is_post_autosave( $post ) ) {
			return;
		}

		$state = ShareState::for_post( $post->ID );

		if ( ! $state->is_empty() || ! $this->decision->wants_share( $post ) ) {
			return;
		}

		$state->mark_queued();
		$state->save();

		$this->queue->dispatch( $post->ID, $this->is_rest_request() ? self::REST_DELAY : 0 );
	}

	/**
	 * Re-evaluate a published post after the editor saved the meta box.
	 *
	 * @param WP_Post $post Post.
	 */
	public function after_editor_save( WP_Post $post ): void {
		if ( 'publish' !== $post->post_status ) {
			return;
		}

		$state = ShareState::for_post( $post->ID );

		if ( ! $state->is_empty() && ! $state->is_queued() ) {
			return;
		}

		if ( $this->decision->wants_share( $post ) ) {
			if ( $state->attempts > 0 ) {
				return; // A retry is already scheduled with its back-off delay.
			}

			if ( $state->is_empty() ) {
				$state->mark_queued();
				$state->save();
			}

			$this->queue->dispatch( $post->ID );

			return;
		}

		if ( $state->is_queued() ) {
			$this->queue->cancel( $post->ID );
			$state->delete();
		}
	}

	/**
	 * Whether the current request is a REST API request.
	 */
	private function is_rest_request(): bool {
		if ( function_exists( 'wp_is_serving_rest_request' ) ) {
			return wp_is_serving_rest_request();
		}

		return defined( 'REST_REQUEST' ) && REST_REQUEST;
	}
}
