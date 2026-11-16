<?php
/**
 * Decides whether a post should be shared.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Sharing;

use Advert4\SocialMediaAutoPost\Enums\Trigger;
use Advert4\SocialMediaAutoPost\Settings\Settings;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Combines the post type trigger with the per-post "Share" checkbox.
 */
final class ShareDecision {

	/**
	 * Plugin settings.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Create the decision service.
	 *
	 * @param Settings $settings Plugin settings.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Whether the post may be sent at all (published, enabled, not protected).
	 *
	 * @param WP_Post $post Post.
	 */
	public function can_share( WP_Post $post ): bool {
		if ( 'publish' !== $post->post_status || ! $this->settings->is_enabled( $post->post_type ) ) {
			return false;
		}

		return ! ( $this->settings->skip_protected() && '' !== (string) $post->post_password );
	}

	/**
	 * Whether the post should be shared without being asked (on publish).
	 *
	 * @param WP_Post            $post      Post.
	 * @param PostOverrides|null $overrides Per-post choices (read from the post when null).
	 */
	public function wants_share( WP_Post $post, ?PostOverrides $overrides = null ): bool {
		if ( ! $this->can_share( $post ) ) {
			return false;
		}

		$overrides = $overrides ?? PostOverrides::for_post( $post->ID );
		$wants     = $overrides->share ?? Trigger::AUTO === $this->settings->post_type( $post->post_type )->trigger;

		/**
		 * Filter whether a post is shared automatically.
		 *
		 * @param bool    $wants Whether the post is shared.
		 * @param WP_Post $post  Post.
		 */
		return (bool) apply_filters( 'a4smap_should_share', $wants, $post );
	}

	/**
	 * Default state of the "Share" checkbox in the editor.
	 *
	 * Posts that were already published before the plugin saw them start
	 * unticked so that editing an old post never shares it by surprise.
	 *
	 * @param WP_Post $post Post.
	 */
	public function checkbox_default( WP_Post $post ): bool {
		$overrides = PostOverrides::for_post( $post->ID );

		if ( null !== $overrides->share ) {
			return $overrides->share;
		}

		if ( 'publish' === $post->post_status ) {
			return false;
		}

		return Trigger::AUTO === $this->settings->post_type( $post->post_type )->trigger;
	}
}
