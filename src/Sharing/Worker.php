<?php
/**
 * Runs the background jobs.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Sharing;

use Advert4\SocialMediaAutoPost\Contracts\Hookable;

defined( 'ABSPATH' ) || exit;

/**
 * Connects the WP-Cron hooks to the publisher and the status sync.
 */
final class Worker implements Hookable {

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
	 * Create the worker.
	 *
	 * @param Publisher  $publisher Publisher.
	 * @param StatusSync $sync      Status sync.
	 * @param Queue      $queue     Background jobs.
	 */
	public function __construct( Publisher $publisher, StatusSync $sync, Queue $queue ) {
		$this->publisher = $publisher;
		$this->sync      = $sync;
		$this->queue     = $queue;
	}

	/**
	 * Attach the cron hooks.
	 */
	public function register(): void {
		add_action( Queue::PUBLISH, array( $this, 'publish' ) );
		add_action( Queue::REFRESH, array( $this, 'refresh' ) );
	}

	/**
	 * Send a queued post.
	 *
	 * @param int|string $post_id Post id.
	 */
	public function publish( $post_id ): void {
		$post_id = (int) $post_id;

		$this->queue->cancel( $post_id );
		$this->publisher->share( $post_id );
	}

	/**
	 * Check the Advert4 status of a post.
	 *
	 * @param int|string $post_id Post id.
	 */
	public function refresh( $post_id ): void {
		$this->sync->refresh( (int) $post_id );
	}
}
