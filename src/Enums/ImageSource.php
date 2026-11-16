<?php
/**
 * Where the images of a post are taken from.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Enums;

defined( 'ABSPATH' ) || exit;

/**
 * Image source of a post. CUSTOM (images picked in the editor) exists only per post.
 */
final class ImageSource {

	use Choices;

	public const FEATURED = 'featured';
	public const META     = 'meta';
	public const GALLERY  = 'gallery';
	public const CONTENT  = 'content';
	public const ATTACHED = 'attached';
	public const NONE     = 'none';
	public const CUSTOM   = 'custom';

	/**
	 * Every value.
	 *
	 * @return list<string>
	 */
	public static function values(): array {
		return array( self::FEATURED, self::META, self::GALLERY, self::CONTENT, self::ATTACHED, self::NONE, self::CUSTOM );
	}

	/**
	 * Labels keyed by value.
	 *
	 * @return array<string, string>
	 */
	public static function labels(): array {
		return array(
			self::FEATURED => __( 'Featured image', 'social-media-auto-post' ),
			self::META     => __( 'Custom field (meta key)', 'social-media-auto-post' ),
			self::GALLERY  => __( 'Galleries in the content', 'social-media-auto-post' ),
			self::CONTENT  => __( 'All images in the content', 'social-media-auto-post' ),
			self::ATTACHED => __( 'Images attached to the post', 'social-media-auto-post' ),
			self::NONE     => __( 'No images (text only)', 'social-media-auto-post' ),
			self::CUSTOM   => __( 'Images I choose for this post', 'social-media-auto-post' ),
		);
	}

	/**
	 * Value => label map for select fields.
	 *
	 * @param bool $with_custom Include the per-post "choose images" option.
	 * @return array<string, string>
	 */
	public static function options( bool $with_custom = false ): array {
		$options = self::labels();

		if ( ! $with_custom ) {
			unset( $options[ self::CUSTOM ] );
		}

		return $options;
	}
}
