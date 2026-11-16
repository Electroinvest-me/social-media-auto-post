<?php
/**
 * Activity log shown on the settings screen.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Keeps the latest entries in a non-autoloaded option.
 *
 * @phpstan-type Entry array{time: int, level: string, message: string, post_id: int}
 */
final class Logger {

	public const OPTION       = 'a4smap_log';
	private const MAX_ENTRIES = 200;

	/**
	 * Record a successful action.
	 *
	 * @param string $message Message.
	 * @param int    $post_id Related post.
	 */
	public function info( string $message, int $post_id = 0 ): void {
		$this->add( 'info', $message, $post_id );
	}

	/**
	 * Record a problem that will be retried or that needs attention.
	 *
	 * @param string $message Message.
	 * @param int    $post_id Related post.
	 */
	public function warning( string $message, int $post_id = 0 ): void {
		$this->add( 'warning', $message, $post_id );
	}

	/**
	 * Record a failure.
	 *
	 * @param string $message Message.
	 * @param int    $post_id Related post.
	 */
	public function error( string $message, int $post_id = 0 ): void {
		$this->add( 'error', $message, $post_id );
	}

	/**
	 * Latest entries, newest first.
	 *
	 * @return list<Entry>
	 */
	public function entries(): array {
		$entries = get_option( self::OPTION, array() );

		return is_array( $entries ) ? array_values( $entries ) : array();
	}

	/**
	 * Remove every entry.
	 */
	public function clear(): void {
		delete_option( self::OPTION );
	}

	/**
	 * Prepend an entry and trim the log.
	 *
	 * @param string $level   info, warning or error.
	 * @param string $message Message.
	 * @param int    $post_id Related post.
	 */
	private function add( string $level, string $message, int $post_id ): void {
		$entries = $this->entries();

		array_unshift(
			$entries,
			array(
				'time'    => time(),
				'level'   => $level,
				'message' => $message,
				'post_id' => $post_id,
			)
		);

		update_option( self::OPTION, array_slice( $entries, 0, self::MAX_ENTRIES ), false );

		/**
		 * Fires after an entry is added to the activity log.
		 *
		 * @param string $level   info, warning or error.
		 * @param string $message Message.
		 * @param int    $post_id Related post, 0 when none.
		 */
		do_action( 'a4smap_log', $level, $message, $post_id );
	}
}
