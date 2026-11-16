<?php
/**
 * One image or video sent to Advert4.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Media;

defined( 'ABSPATH' ) || exit;

/**
 * An absolute media URL with its kind and MIME type.
 */
final class MediaItem {

	public const IMAGE = 'image';
	public const VIDEO = 'video';

	public const IMAGE_MIMES = array( 'image/jpeg', 'image/png', 'image/webp' );
	public const VIDEO_MIMES = array( 'video/mp4', 'video/quicktime' );

	/**
	 * Absolute URL Advert4 downloads.
	 *
	 * @var string
	 */
	public string $url;

	/**
	 * Self::IMAGE or self::VIDEO.
	 *
	 * @var string
	 */
	public string $kind;

	/**
	 * MIME type, "" when unknown.
	 *
	 * @var string
	 */
	public string $mime;

	/**
	 * Media library id, 0 for external URLs.
	 *
	 * @var int
	 */
	public int $attachment_id;

	/**
	 * Create the item.
	 *
	 * @param string $url           Absolute URL Advert4 downloads.
	 * @param string $kind          self::IMAGE or self::VIDEO.
	 * @param string $mime          MIME type, "" when unknown.
	 * @param int    $attachment_id Media library id, 0 for external URLs.
	 */
	public function __construct( string $url, string $kind, string $mime = '', int $attachment_id = 0 ) {
		$this->url           = $url;
		$this->kind          = $kind;
		$this->mime          = $mime;
		$this->attachment_id = $attachment_id;
	}

	/**
	 * Whether this is an image.
	 */
	public function is_image(): bool {
		return self::IMAGE === $this->kind;
	}

	/**
	 * Whether this is a video.
	 */
	public function is_video(): bool {
		return self::VIDEO === $this->kind;
	}

	/**
	 * Whether Advert4 accepts the file type (unknown types are left to Advert4 to check).
	 */
	public function is_supported(): bool {
		if ( '' === $this->mime ) {
			return true;
		}

		return in_array( $this->mime, $this->is_video() ? self::VIDEO_MIMES : self::IMAGE_MIMES, true );
	}

	/**
	 * Identity used to drop duplicates.
	 */
	public function key(): string {
		return $this->attachment_id > 0 ? 'id:' . $this->attachment_id : 'url:' . $this->url;
	}

	/**
	 * File name shown in the preview.
	 */
	public function filename(): string {
		return wp_basename( (string) wp_parse_url( $this->url, PHP_URL_PATH ) );
	}

	/**
	 * Small image for the preview ("" for videos without a poster).
	 */
	public function thumbnail(): string {
		if ( $this->attachment_id > 0 ) {
			$thumbnail = wp_get_attachment_image_url( $this->attachment_id, 'thumbnail', ! $this->is_image() );

			if ( is_string( $thumbnail ) && '' !== $thumbnail ) {
				return $thumbnail;
			}
		}

		return $this->is_image() ? $this->url : '';
	}

	/**
	 * Entry of the "media" array in the API request.
	 *
	 * @return array{url: string}
	 */
	public function to_payload(): array {
		return array( 'url' => $this->url );
	}
}
