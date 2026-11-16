<?php
/**
 * Where the video of a post is taken from.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Enums;

defined( 'ABSPATH' ) || exit;

/**
 * Video source of a post.
 */
final class VideoSource {

	use Choices;

	public const NONE     = 'none';
	public const META     = 'meta';
	public const CONTENT  = 'content';
	public const ATTACHED = 'attached';

	/**
	 * Every value.
	 *
	 * @return list<string>
	 */
	public static function values(): array {
		return array( self::NONE, self::META, self::CONTENT, self::ATTACHED );
	}

	/**
	 * Labels keyed by value.
	 *
	 * @return array<string, string>
	 */
	public static function labels(): array {
		return array(
			self::NONE     => __( 'Do not look for a video', 'social-media-auto-post' ),
			self::META     => __( 'Custom field (meta key)', 'social-media-auto-post' ),
			self::CONTENT  => __( 'First video in the content', 'social-media-auto-post' ),
			self::ATTACHED => __( 'Video attached to the post', 'social-media-auto-post' ),
		);
	}
}
