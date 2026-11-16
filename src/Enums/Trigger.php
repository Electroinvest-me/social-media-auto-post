<?php
/**
 * When a post type is shared.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Enums;

defined( 'ABSPATH' ) || exit;

/**
 * Share trigger of a post type.
 */
final class Trigger {

	use Choices;

	public const AUTO   = 'auto';
	public const MANUAL = 'manual';

	/**
	 * Every value.
	 *
	 * @return list<string>
	 */
	public static function values(): array {
		return array( self::AUTO, self::MANUAL );
	}

	/**
	 * Labels keyed by value.
	 *
	 * @return array<string, string>
	 */
	public static function labels(): array {
		return array(
			self::AUTO   => __( 'Automatically when a post is published', 'social-media-auto-post' ),
			self::MANUAL => __( 'Only when I tick "Share" on the post', 'social-media-auto-post' ),
		);
	}
}
