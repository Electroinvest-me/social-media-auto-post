<?php
/**
 * Numbers and recent posts for the Overview tab.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Admin;

use Advert4\SocialMediaAutoPost\Settings\Settings;
use Advert4\SocialMediaAutoPost\Sharing\ShareState;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Reads the sharing state stored on posts and summarizes it.
 *
 * @phpstan-type Stats array{shared_30: int, published: int, attention: int, in_progress: int, total: int}
 */
final class Overview {

	private const SCAN_LIMIT = 2000;

	/**
	 * Plugin settings.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Create the service.
	 *
	 * @param Settings $settings Plugin settings.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Counts across the most recent shared posts.
	 *
	 * @return Stats
	 */
	public function stats(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin overview only.
		$values = (array) $wpdb->get_col(
			$wpdb->prepare(
				"SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s ORDER BY meta_id DESC LIMIT %d",
				ShareState::META_KEY,
				self::SCAN_LIMIT
			)
		);

		$stats = array(
			'shared_30'   => 0,
			'published'   => 0,
			'attention'   => 0,
			'in_progress' => 0,
			'total'       => 0,
		);
		$since = time() - 30 * DAY_IN_SECONDS;

		foreach ( $values as $value ) {
			$data = maybe_unserialize( $value );

			if ( ! is_array( $data ) ) {
				continue;
			}

			$status = (string) ( $data['status'] ?? '' );
			++$stats['total'];

			if ( (int) ( $data['shared_at'] ?? 0 ) >= $since ) {
				++$stats['shared_30'];
			}

			if ( in_array( $status, array( 'published', 'partial' ), true ) ) {
				++$stats['published'];
			}

			if ( in_array( $status, array( ShareState::ERROR, 'failed', 'partial' ), true ) ) {
				++$stats['attention'];
			}

			if ( in_array( $status, array( ShareState::QUEUED, ShareState::SENDING, 'publishing', 'scheduled' ), true ) ) {
				++$stats['in_progress'];
			}
		}

		return $stats;
	}

	/**
	 * Most recently changed posts that have a sharing state.
	 *
	 * @param int $limit Number of posts.
	 * @return list<array{post: WP_Post, state: ShareState}>
	 */
	public function recent( int $limit = 6 ): array {
		$post_types = $this->settings->enabled_post_types();

		if ( array() === $post_types ) {
			return array();
		}

		$posts = get_posts(
			array(
				'post_type'        => $post_types,
				'post_status'      => 'any',
				'posts_per_page'   => $limit,
				'orderby'          => 'modified',
				'order'            => 'DESC',
				'meta_key'         => ShareState::META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Small admin query.
				'suppress_filters' => false,
			)
		);

		$recent = array();

		foreach ( $posts as $post ) {
			if ( $post instanceof WP_Post ) {
				$recent[] = array(
					'post'  => $post,
					'state' => ShareState::for_post( $post->ID ),
				);
			}
		}

		return $recent;
	}

	/**
	 * Setup steps and whether each is done.
	 *
	 * @param list<array<string, mixed>> $profiles Advert4 profiles.
	 * @param array{total: int}          $stats    Counts from stats().
	 * @return list<array{title: string, text: string, done: bool, tab: string}>
	 */
	public function checklist( array $profiles, array $stats ): array {
		$configured = array() !== $this->settings->enabled_post_types();

		return array(
			array(
				'title' => __( 'Connect Advert4', 'social-media-auto-post' ),
				'text'  => __( 'Paste the API key of your Advert4 workspace.', 'social-media-auto-post' ),
				'done'  => $this->settings->has_api_key() && array() !== $profiles,
				'tab'   => 'connection',
			),
			array(
				'title' => __( 'Choose what to share', 'social-media-auto-post' ),
				'text'  => __( 'Turn on a post type and pick its networks, format and media.', 'social-media-auto-post' ),
				'done'  => $configured,
				'tab'   => 'post-types',
			),
			array(
				'title' => __( 'Publish your first post', 'social-media-auto-post' ),
				'text'  => __( 'It goes out to your networks as soon as it is published.', 'social-media-auto-post' ),
				'done'  => $stats['total'] > 0,
				'tab'   => 'post-types',
			),
		);
	}
}
