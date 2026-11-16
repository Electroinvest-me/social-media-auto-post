<?php
/**
 * Background jobs on WP-Cron.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Sharing;

defined( 'ABSPATH' ) || exit;

/**
 * Schedules sending and status checks so saving a post never waits for the networks.
 */
final class Queue {

	public const PUBLISH = 'a4smap_publish';
	public const REFRESH = 'a4smap_refresh';

	/**
	 * Whether a shutdown handler was already added in this request.
	 *
	 * @var bool
	 */
	private bool $kick_registered = false;

	/**
	 * Posts to process at the end of this request (cron and WP-CLI).
	 *
	 * @var array<int, true>
	 */
	private array $inline = array();

	/**
	 * Send a post in the background.
	 *
	 * @param int $post_id Post id.
	 * @param int $delay   Seconds to wait.
	 */
	public function dispatch( int $post_id, int $delay = 0 ): void {
		$args = array( $post_id );

		wp_clear_scheduled_hook( self::PUBLISH, $args );
		wp_schedule_single_event( time() + max( 0, $delay ), self::PUBLISH, $args );

		if ( 0 === $delay ) {
			$this->kick( $post_id );
		}
	}

	/**
	 * Remove a queued send.
	 *
	 * @param int $post_id Post id.
	 */
	public function cancel( int $post_id ): void {
		wp_clear_scheduled_hook( self::PUBLISH, array( $post_id ) );
		unset( $this->inline[ $post_id ] );
	}

	/**
	 * Whether a send is queued and already due.
	 *
	 * @param int $post_id Post id.
	 */
	public function is_due( int $post_id ): bool {
		$timestamp = wp_next_scheduled( self::PUBLISH, array( $post_id ) );

		return false !== $timestamp && $timestamp <= time();
	}

	/**
	 * Check the Advert4 status of a post later.
	 *
	 * @param int $post_id Post id.
	 * @param int $delay   Seconds to wait.
	 */
	public function schedule_refresh( int $post_id, int $delay ): void {
		wp_clear_scheduled_hook( self::REFRESH, array( $post_id ) );
		wp_schedule_single_event( time() + max( 1, $delay ), self::REFRESH, array( $post_id ) );
	}

	/**
	 * Stop checking the Advert4 status of a post.
	 *
	 * @param int $post_id Post id.
	 */
	public function cancel_refresh( int $post_id ): void {
		wp_clear_scheduled_hook( self::REFRESH, array( $post_id ) );
	}

	/**
	 * Remove every job of the plugin.
	 */
	public static function clear_all(): void {
		wp_unschedule_hook( self::PUBLISH );
		wp_unschedule_hook( self::REFRESH );
	}

	/**
	 * Make a due job run soon: inline at the end of cron/CLI requests, otherwise by spawning WP-Cron.
	 *
	 * @param int $post_id Post id.
	 */
	private function kick( int $post_id ): void {
		$inline = wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI );

		if ( $inline ) {
			$this->inline[ $post_id ] = true;
		}

		if ( $this->kick_registered ) {
			return;
		}

		$this->kick_registered = true;

		add_action(
			'shutdown',
			function (): void {
				foreach ( array_keys( $this->inline ) as $queued_post_id ) {
					if ( false !== wp_next_scheduled( self::PUBLISH, array( $queued_post_id ) ) ) {
						do_action( self::PUBLISH, $queued_post_id ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- self::PUBLISH is "a4smap_publish".
					}
				}

				if ( array() === $this->inline && ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) ) {
					spawn_cron();
				}
			}
		);
	}
}
