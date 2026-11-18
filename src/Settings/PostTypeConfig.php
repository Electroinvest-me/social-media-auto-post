<?php
/**
 * Sharing configuration of one post type.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Settings;

use Advert4\SocialMediaAutoPost\Enums\Format;
use Advert4\SocialMediaAutoPost\Enums\ImageSource;
use Advert4\SocialMediaAutoPost\Enums\Mode;
use Advert4\SocialMediaAutoPost\Enums\Network;
use Advert4\SocialMediaAutoPost\Enums\Trigger;
use Advert4\SocialMediaAutoPost\Enums\VideoSource;

defined( 'ABSPATH' ) || exit;

/**
 * Settings of one post type (e.g. "post", "product"). Treat the properties as read-only.
 */
final class PostTypeConfig {

	public const DEFAULT_CAPTION = "{title}\n\n{excerpt}\n\n{url}";
	public const MAX_MEDIA       = 35;

	/**
	 * Post type slug.
	 *
	 * @var string
	 */
	public string $post_type;

	/**
	 * Whether the post type is shared at all.
	 *
	 * @var bool
	 */
	public bool $enabled;

	/**
	 * Trigger::AUTO or Trigger::MANUAL.
	 *
	 * @var string
	 */
	public string $trigger;

	/**
	 * Advert4 profile slug or id ("" = the only profile of the workspace).
	 *
	 * @var string
	 */
	public string $profile;

	/**
	 * Network values; empty = every account of the profile.
	 *
	 * @var list<string>
	 */
	public array $platforms;

	/**
	 * A Format value.
	 *
	 * @var string
	 */
	public string $format;

	/**
	 * An ImageSource value (never CUSTOM).
	 *
	 * @var string
	 */
	public string $image_source;

	/**
	 * Meta key for ImageSource::META.
	 *
	 * @var string
	 */
	public string $image_meta_key;

	/**
	 * A VideoSource value.
	 *
	 * @var string
	 */
	public string $video_source;

	/**
	 * Meta key for VideoSource::META.
	 *
	 * @var string
	 */
	public string $video_meta_key;

	/**
	 * Put the featured image first in galleries.
	 *
	 * @var bool
	 */
	public bool $featured_first;

	/**
	 * Use the featured image when the source has none.
	 *
	 * @var bool
	 */
	public bool $featured_fallback;

	/**
	 * Most images sent in one post.
	 *
	 * @var int
	 */
	public int $max_images;

	/**
	 * Caption template.
	 *
	 * @var string
	 */
	public string $caption;

	/**
	 * A Mode value.
	 *
	 * @var string
	 */
	public string $mode;

	/**
	 * Build from stored values; unknown or missing values fall back to the defaults.
	 *
	 * @param string               $post_type Post type slug.
	 * @param array<string, mixed> $data      Stored values.
	 */
	public function __construct( string $post_type, array $data = array() ) {
		$data = array_merge( self::defaults( $post_type ), $data );

		$this->post_type         = $post_type;
		$this->enabled           = (bool) $data['enabled'];
		$this->trigger           = Trigger::sanitize( $data['trigger'], Trigger::AUTO );
		$this->profile           = (string) $data['profile'];
		$this->platforms         = Network::filter( $data['platforms'] );
		$this->format            = Format::sanitize( $data['format'], Format::AUTO );
		$this->image_source      = self::image_source( $data['image_source'] );
		$this->image_meta_key    = (string) $data['image_meta_key'];
		$this->video_source      = VideoSource::sanitize( $data['video_source'], VideoSource::NONE );
		$this->video_meta_key    = (string) $data['video_meta_key'];
		$this->featured_first    = (bool) $data['featured_first'];
		$this->featured_fallback = (bool) $data['featured_fallback'];
		$this->max_images        = max( 1, min( self::MAX_MEDIA, (int) $data['max_images'] ) );
		$this->caption           = (string) $data['caption'];
		$this->mode              = Mode::sanitize( $data['mode'], Mode::PUBLISH );
	}

	/**
	 * Default values of a post type.
	 *
	 * @param string $post_type Post type slug.
	 * @return array<string, mixed>
	 */
	public static function defaults( string $post_type ): array {
		return array(
			'enabled'           => 'post' === $post_type,
			'trigger'           => Trigger::AUTO,
			'profile'           => '',
			'platforms'         => array(),
			'format'            => Format::AUTO,
			'image_source'      => ImageSource::FEATURED,
			'image_meta_key'    => 'product' === $post_type ? '_product_image_gallery' : '',
			'video_source'      => VideoSource::NONE,
			'video_meta_key'    => '',
			'featured_first'    => true,
			'featured_fallback' => true,
			'max_images'        => 10,
			'caption'           => self::DEFAULT_CAPTION,
			'mode'              => Mode::PUBLISH,
		);
	}

	/**
	 * Sanitize the values submitted on the settings screen.
	 *
	 * @param string $post_type Post type slug.
	 * @param mixed  $input     Raw submitted values.
	 * @return array<string, mixed>
	 */
	public static function sanitize( string $post_type, $input ): array {
		$input = is_array( $input ) ? $input : array();

		$text = static function ( string $key ) use ( $input ): string {
			return isset( $input[ $key ] ) && is_scalar( $input[ $key ] ) ? sanitize_text_field( (string) $input[ $key ] ) : '';
		};

		$caption = isset( $input['caption'] ) && is_string( $input['caption'] ) ? str_replace( array( "\r\n", "\r" ), "\n", sanitize_textarea_field( $input['caption'] ) ) : self::DEFAULT_CAPTION;

		return array(
			'enabled'           => ! empty( $input['enabled'] ),
			'trigger'           => Trigger::sanitize( $text( 'trigger' ), Trigger::AUTO ),
			'profile'           => $text( 'profile' ),
			'platforms'         => Network::filter( $input['platforms'] ?? array() ),
			'format'            => Format::sanitize( $text( 'format' ), Format::AUTO ),
			'image_source'      => self::image_source( $text( 'image_source' ) ),
			'image_meta_key'    => $text( 'image_meta_key' ),
			'video_source'      => VideoSource::sanitize( $text( 'video_source' ), VideoSource::NONE ),
			'video_meta_key'    => $text( 'video_meta_key' ),
			'featured_first'    => ! empty( $input['featured_first'] ),
			'featured_fallback' => ! empty( $input['featured_fallback'] ),
			'max_images'        => max( 1, min( self::MAX_MEDIA, absint( $input['max_images'] ?? 10 ) ) ),
			'caption'           => '' === trim( $caption ) ? self::DEFAULT_CAPTION : $caption,
			'mode'              => Mode::sanitize( $text( 'mode' ), Mode::PUBLISH ),
		);
	}

	/**
	 * Image source allowed in the settings ("custom" exists only per post).
	 *
	 * @param mixed $value Value.
	 */
	private static function image_source( $value ): string {
		$source = ImageSource::sanitize( $value, ImageSource::FEATURED );

		return ImageSource::CUSTOM === $source ? ImageSource::FEATURED : $source;
	}
}
