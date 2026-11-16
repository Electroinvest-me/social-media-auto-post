<?php
/**
 * How a WordPress post is published on the social networks.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Enums;

defined( 'ABSPATH' ) || exit;

/**
 * Post format sent to Advert4. The values are the Advert4 API post types.
 */
final class Format {

	use Choices;

	public const AUTO    = 'auto';
	public const IMAGE   = 'post';
	public const GALLERY = 'carousel';
	public const VIDEO   = 'reel';
	public const STORY   = 'story';

	/**
	 * Every value.
	 *
	 * @return list<string>
	 */
	public static function values(): array {
		return array( self::AUTO, self::IMAGE, self::GALLERY, self::VIDEO, self::STORY );
	}

	/**
	 * Labels keyed by value.
	 *
	 * @return array<string, string>
	 */
	public static function labels(): array {
		return array(
			self::AUTO    => __( 'Automatic', 'social-media-auto-post' ),
			self::IMAGE   => __( 'Single image post', 'social-media-auto-post' ),
			self::GALLERY => __( 'Gallery (carousel)', 'social-media-auto-post' ),
			self::VIDEO   => __( 'Video (Reel)', 'social-media-auto-post' ),
			self::STORY   => __( 'Story', 'social-media-auto-post' ),
		);
	}

	/**
	 * Short explanation shown next to the select.
	 *
	 * @param string $value Format.
	 */
	public static function description( string $value ): string {
		$descriptions = array(
			self::AUTO    => __( 'Video when one is found, a gallery for several images, otherwise one image.', 'social-media-auto-post' ),
			self::IMAGE   => __( 'Only the first image is sent.', 'social-media-auto-post' ),
			self::GALLERY => __( 'Every image found is sent as one carousel post.', 'social-media-auto-post' ),
			self::VIDEO   => __( 'The first video is sent as a Reel / TikTok video.', 'social-media-auto-post' ),
			self::STORY   => __( 'The first video, or the first image, is sent as a Story.', 'social-media-auto-post' ),
		);

		return $descriptions[ $value ] ?? '';
	}

	/**
	 * Whether a format looks for a video.
	 *
	 * @param string $value Format.
	 */
	public static function accepts_video( string $value ): bool {
		return self::IMAGE !== $value && self::GALLERY !== $value;
	}
}
