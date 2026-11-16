<?php
/**
 * Status column and bulk action on the posts list.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Admin;

use Advert4\SocialMediaAutoPost\Contracts\Hookable;
use Advert4\SocialMediaAutoPost\Settings\Settings;
use Advert4\SocialMediaAutoPost\Sharing\Queue;
use Advert4\SocialMediaAutoPost\Sharing\ShareDecision;
use Advert4\SocialMediaAutoPost\Sharing\ShareState;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a "Social" column and a "Share to social media" bulk action.
 */
final class PostsList implements Hookable {

	public const COLUMN      = 'a4smap';
	public const BULK_ACTION = 'a4smap_share';

	/**
	 * Plugin settings.
	 *
	 * @var Settings
	 */
	private Settings $settings;

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
	 * @param Settings      $settings Plugin settings.
	 * @param ShareDecision $decision Share rules.
	 * @param Queue         $queue    Background jobs.
	 */
	public function __construct( Settings $settings, ShareDecision $decision, Queue $queue ) {
		$this->settings = $settings;
		$this->decision = $decision;
		$this->queue    = $queue;
	}

	/**
	 * Attach when the posts list loads.
	 */
	public function register(): void {
		add_action( 'load-edit.php', array( $this, 'setup' ) );
	}

	/**
	 * Add the hooks for the current post type.
	 */
	public function setup(): void {
		$screen = get_current_screen();

		if ( null === $screen || ! $this->settings->is_enabled( (string) $screen->post_type ) ) {
			return;
		}

		$post_type = (string) $screen->post_type;

		add_filter( "manage_{$post_type}_posts_columns", array( $this, 'columns' ) );
		add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'column' ), 10, 2 );
		add_filter( "bulk_actions-edit-{$post_type}", array( $this, 'bulk_actions' ) );
		add_filter( "handle_bulk_actions-edit-{$post_type}", array( $this, 'handle_bulk' ), 10, 3 );
		add_action( 'admin_notices', array( $this, 'bulk_notice' ) );
		add_action( 'admin_enqueue_scripts', static fn () => Assets::enqueue() );
	}

	/**
	 * Add the column after the title.
	 *
	 * @param array<string, string> $columns Columns.
	 * @return array<string, string>
	 */
	public function columns( array $columns ): array {
		$result = array();

		foreach ( $columns as $key => $label ) {
			$result[ $key ] = $label;

			if ( 'title' === $key ) {
				$result[ self::COLUMN ] = __( 'Social', 'social-media-auto-post' );
			}
		}

		if ( ! isset( $result[ self::COLUMN ] ) ) {
			$result[ self::COLUMN ] = __( 'Social', 'social-media-auto-post' );
		}

		return $result;
	}

	/**
	 * Print the status badge.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post id.
	 */
	public function column( string $column, int $post_id ): void {
		if ( self::COLUMN !== $column ) {
			return;
		}

		$state = ShareState::for_post( $post_id );
		$title = '' !== $state->message ? $state->message : implode(
			', ',
			array_map(
				static fn ( array $target ): string => ucfirst( $target['network'] ) . ': ' . ShareState::target_label( $target['status'] ),
				$state->targets
			)
		);

		printf(
			'<span class="a4smap-badge a4smap-badge--%1$s" title="%2$s">%3$s</span>',
			esc_attr( $state->tone() ),
			esc_attr( $title ),
			esc_html( $state->label() )
		);
	}

	/**
	 * Add the bulk action.
	 *
	 * @param array<string, string> $actions Bulk actions.
	 * @return array<string, string>
	 */
	public function bulk_actions( array $actions ): array {
		$actions[ self::BULK_ACTION ] = __( 'Share to social media', 'social-media-auto-post' );

		return $actions;
	}

	/**
	 * Queue the selected posts that were never shared.
	 *
	 * @param string    $redirect Redirect URL.
	 * @param string    $action   Bulk action.
	 * @param list<int> $post_ids Selected posts.
	 */
	public function handle_bulk( string $redirect, string $action, array $post_ids ): string {
		if ( self::BULK_ACTION !== $action ) {
			return $redirect;
		}

		$queued  = 0;
		$skipped = 0;

		foreach ( $post_ids as $index => $post_id ) {
			$post  = get_post( (int) $post_id );
			$state = ShareState::for_post( (int) $post_id );

			if ( ! $post instanceof WP_Post || ! current_user_can( 'edit_post', $post->ID ) || ! $this->decision->can_share( $post ) || $state->has_remote() || $state->is_in_progress() ) {
				++$skipped;
				continue;
			}

			$state->mark_queued();
			$state->save();
			$this->queue->dispatch( $post->ID, 0 === $index ? 0 : $index * 5 );
			++$queued;
		}

		return add_query_arg(
			array(
				'a4smap_queued'  => $queued,
				'a4smap_skipped' => $skipped,
			),
			$redirect
		);
	}

	/**
	 * Report the bulk action result.
	 */
	public function bulk_notice(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only counters in the redirect URL.
		if ( ! isset( $_GET['a4smap_queued'] ) ) {
			return;
		}

		$queued  = absint( $_GET['a4smap_queued'] );
		$skipped = isset( $_GET['a4smap_skipped'] ) ? absint( $_GET['a4smap_skipped'] ) : 0;
		// phpcs:enable

		$message = sprintf(
			/* translators: %d: number of posts */
			_n( '%d post is being shared to social media.', '%d posts are being shared to social media.', $queued, 'social-media-auto-post' ),
			$queued
		);

		if ( $skipped > 0 ) {
			$message .= ' ' . sprintf(
				/* translators: %d: number of posts */
				_n( '%d post was skipped (not published, already shared or excluded).', '%d posts were skipped (not published, already shared or excluded).', $skipped, 'social-media-auto-post' ),
				$skipped
			);
		}

		printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $message ) );
	}
}
