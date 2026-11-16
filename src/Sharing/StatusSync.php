<?php
/**
 * Keeps the Advert4 status of shared posts up to date.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Sharing;

use Advert4\SocialMediaAutoPost\Api\ApiException;
use Advert4\SocialMediaAutoPost\Api\Client;
use Advert4\SocialMediaAutoPost\Support\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Reads GET /posts/{uuid} after sending, on webhooks and on demand.
 */
final class StatusSync {

	private const POLL_DELAYS = array( 30, 60, 120, 300, 600, 1800, 3600 );

	/**
	 * API client.
	 *
	 * @var Client
	 */
	private Client $client;

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
	 * Create the service.
	 *
	 * @param Client $client API client.
	 * @param Queue  $queue  Background jobs.
	 * @param Logger $logger Activity log.
	 */
	public function __construct( Client $client, Queue $queue, Logger $logger ) {
		$this->client = $client;
		$this->queue  = $queue;
		$this->logger = $logger;
	}

	/**
	 * Fetch the current status from Advert4.
	 *
	 * @param int $post_id Post id.
	 */
	public function refresh( int $post_id ): ShareState {
		$state = ShareState::for_post( $post_id );

		if ( ! $state->has_remote() ) {
			return $state;
		}

		$previous = $state->status;

		try {
			$remote = $this->client->get_post( $state->uuid );
		} catch ( ApiException $exception ) {
			if ( 404 === $exception->status() ) {
				$state->mark_error( __( 'The post was deleted in Advert4.', 'social-media-auto-post' ) );
				$state->save();
				$this->queue->cancel_refresh( $post_id );

				return $state;
			}

			$this->schedule_next( $state );

			return $state;
		}

		$state->apply_remote( $remote );
		++$state->polls;
		$state->save();

		if ( $state->is_remote_pending() ) {
			$this->schedule_next( $state );
		} else {
			$this->queue->cancel_refresh( $post_id );
		}

		if ( $previous !== $state->status ) {
			$this->log_change( $state );

			/**
			 * Fires when the Advert4 status of a post changes.
			 *
			 * @param int        $post_id  Post id.
			 * @param string     $status   New status.
			 * @param string     $previous Old status.
			 * @param ShareState $state    Full state.
			 */
			do_action( 'a4smap_status_changed', $post_id, $state->status, $previous, $state );
		}

		return $state;
	}

	/**
	 * Check again later while Advert4 is still publishing.
	 *
	 * @param ShareState $state State.
	 */
	private function schedule_next( ShareState $state ): void {
		if ( $state->polls >= count( self::POLL_DELAYS ) ) {
			return;
		}

		$this->queue->schedule_refresh( $state->post_id, self::POLL_DELAYS[ $state->polls ] );
	}

	/**
	 * Write the new status and any network errors to the activity log.
	 *
	 * @param ShareState $state State.
	 */
	private function log_change( ShareState $state ): void {
		$failed = array_filter( $state->targets, static fn ( array $target ): bool => 'failed' === $target['status'] );

		/* translators: %s: Advert4 status */
		$message = sprintf( __( 'Advert4 status: %s.', 'social-media-auto-post' ), $state->label() );

		foreach ( $failed as $target ) {
			$message .= sprintf( ' %s: %s', ucfirst( $target['network'] ), $target['error'] );
		}

		if ( array() === $failed ) {
			$this->logger->info( $message, $state->post_id );
		} else {
			$this->logger->error( $message, $state->post_id );
		}
	}
}
