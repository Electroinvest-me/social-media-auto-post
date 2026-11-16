<?php
/**
 * Sends posts to Advert4.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Sharing;

use Advert4\SocialMediaAutoPost\Api\ApiException;
use Advert4\SocialMediaAutoPost\Api\Client;
use Advert4\SocialMediaAutoPost\Enums\Format;
use Advert4\SocialMediaAutoPost\Support\Logger;
use Advert4\SocialMediaAutoPost\Support\Site;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the request, calls the API, stores the result and retries temporary failures.
 */
final class Publisher {

	private const LOCK_KEY     = '_a4smap_lock';
	private const LOCK_TIMEOUT = 900;
	private const MAX_ATTEMPTS = 4;
	private const BACKOFF      = array( 60, 300, 900 );

	/**
	 * API client.
	 *
	 * @var Client
	 */
	private Client $client;

	/**
	 * Request builder.
	 *
	 * @var PayloadBuilder
	 */
	private PayloadBuilder $builder;

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
	 * Activity log.
	 *
	 * @var Logger
	 */
	private Logger $logger;

	/**
	 * Create the publisher.
	 *
	 * @param Client         $client   API client.
	 * @param PayloadBuilder $builder  Request builder.
	 * @param ShareDecision  $decision Share rules.
	 * @param Queue          $queue    Background jobs.
	 * @param Logger         $logger   Activity log.
	 */
	public function __construct( Client $client, PayloadBuilder $builder, ShareDecision $decision, Queue $queue, Logger $logger ) {
		$this->client   = $client;
		$this->builder  = $builder;
		$this->decision = $decision;
		$this->queue    = $queue;
		$this->logger   = $logger;
	}

	/**
	 * Send a post.
	 *
	 * @param int  $post_id Post id.
	 * @param bool $again   Share again even though it was shared before.
	 */
	public function share( int $post_id, bool $again = false ): ShareState {
		$state = ShareState::for_post( $post_id );
		$post  = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			return $state;
		}

		if ( ! $this->decision->can_share( $post ) ) {
			if ( $state->is_queued() ) {
				$state->delete();
			}

			return ShareState::for_post( $post_id );
		}

		if ( $state->has_remote() && ! $again ) {
			return $state;
		}

		if ( ! $this->lock( $post_id ) ) {
			return $state;
		}

		try {
			if ( $again || '' === $state->external_id ) {
				$state->start_new_share();
			}

			$this->send( $post, $state );
		} finally {
			$this->unlock( $post_id );
		}

		return $state;
	}

	/**
	 * Build and send the request, then record the outcome.
	 *
	 * @param WP_Post    $post  Post.
	 * @param ShareState $state State to update.
	 */
	private function send( WP_Post $post, ShareState $state ): void {
		$plan = $this->builder->build( $post );

		if ( ! $plan->is_valid() ) {
			$state->mark_error( implode( ' ', $plan->errors ) );
			$state->save();
			$this->logger->error( $state->message, $post->ID );

			/** This action is documented below. */
			do_action( 'a4smap_share_failed', $post->ID, $state->message, $plan );

			return;
		}

		$payload = $plan->payload + array( 'external_id' => $state->external_id );

		if ( Site::is_public() ) {
			$payload['callback_url'] = add_query_arg(
				array(
					'post'  => $post->ID,
					'token' => Site::webhook_token( $post->ID ),
				),
				rest_url( 'a4smap/v1/webhook' )
			);
		}

		$state->mark_sending();
		$state->save();

		try {
			$remote = $this->client->create_post( $payload );
		} catch ( ApiException $exception ) {
			$this->handle_failure( $post, $state, $exception, $plan );

			return;
		}

		$state->apply_remote( $remote );
		$state->shared_at = time();
		$state->save();

		$this->logger->info(
			sprintf(
				/* translators: 1: format label, 2: number of media files, 3: Advert4 status */
				__( 'Sent to Advert4 as %1$s with %2$d media file(s). Status: %3$s.', 'social-media-auto-post' ),
				Format::label( $plan->format ),
				count( $plan->media ),
				$state->label()
			),
			$post->ID
		);

		if ( $state->is_remote_pending() ) {
			$this->queue->schedule_refresh( $post->ID, 30 );
		}

		/**
		 * Fires after Advert4 accepted a post.
		 *
		 * @param int                  $post_id Post id.
		 * @param array<string, mixed> $remote  Advert4 post.
		 * @param array<string, mixed> $payload Request that was sent.
		 */
		do_action( 'a4smap_shared', $post->ID, $remote, $payload );
	}

	/**
	 * Retry temporary failures, record permanent ones.
	 *
	 * @param WP_Post      $post      Post.
	 * @param ShareState   $state     State to update.
	 * @param ApiException $exception Error.
	 * @param SharePlan    $plan      Plan that was sent.
	 */
	private function handle_failure( WP_Post $post, ShareState $state, ApiException $exception, SharePlan $plan ): void {
		if ( $exception->is_retryable() && $state->attempts < self::MAX_ATTEMPTS ) {
			$delay = self::BACKOFF[ min( $state->attempts, count( self::BACKOFF ) ) - 1 ];

			$state->mark_queued(
				sprintf(
					/* translators: 1: error message, 2: minutes until the next attempt */
					__( '%1$s — trying again in %2$d min.', 'social-media-auto-post' ),
					$exception->getMessage(),
					(int) ceil( $delay / 60 )
				)
			);
			$state->save();
			$this->queue->dispatch( $post->ID, $delay );
			$this->logger->warning( $state->message, $post->ID );

			return;
		}

		$state->mark_error( $exception->getMessage(), $exception->errors() );
		$state->save();
		$this->logger->error( trim( $exception->getMessage() . ' ' . implode( ' ', $exception->errors() ) ), $post->ID );

		/**
		 * Fires when a post could not be sent to Advert4.
		 *
		 * @param int       $post_id Post id.
		 * @param string    $message Reason.
		 * @param SharePlan $plan    What was sent.
		 */
		do_action( 'a4smap_share_failed', $post->ID, $exception->getMessage(), $plan );
	}

	/**
	 * Take the per-post lock.
	 *
	 * @param int $post_id Post id.
	 */
	private function lock( int $post_id ): bool {
		if ( add_post_meta( $post_id, self::LOCK_KEY, time(), true ) ) {
			return true;
		}

		$locked_at = (int) get_post_meta( $post_id, self::LOCK_KEY, true );

		if ( $locked_at > 0 && time() - $locked_at < self::LOCK_TIMEOUT ) {
			return false;
		}

		delete_post_meta( $post_id, self::LOCK_KEY );

		return (bool) add_post_meta( $post_id, self::LOCK_KEY, time(), true );
	}

	/**
	 * Release the per-post lock.
	 *
	 * @param int $post_id Post id.
	 */
	private function unlock( int $post_id ): void {
		delete_post_meta( $post_id, self::LOCK_KEY );
	}
}
