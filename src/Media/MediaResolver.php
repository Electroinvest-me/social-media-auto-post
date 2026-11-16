<?php
/**
 * Works out which images and video a post sends.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Media;

use Advert4\SocialMediaAutoPost\Enums\Format;
use Advert4\SocialMediaAutoPost\Enums\ImageSource;
use Advert4\SocialMediaAutoPost\Enums\VideoSource;
use Advert4\SocialMediaAutoPost\Settings\PostTypeConfig;
use Advert4\SocialMediaAutoPost\Settings\Settings;
use Advert4\SocialMediaAutoPost\Sharing\PostOverrides;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Collects the media of a post from the configured sources as absolute URLs.
 */
final class MediaResolver {

	/**
	 * Plugin settings.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Custom field parser.
	 *
	 * @var MetaValueParser
	 */
	private MetaValueParser $parser;

	/**
	 * Content scanner.
	 *
	 * @var ContentScanner
	 */
	private ContentScanner $scanner;

	/**
	 * Create the resolver.
	 *
	 * @param Settings        $settings Plugin settings.
	 * @param MetaValueParser $parser   Custom field parser.
	 * @param ContentScanner  $scanner  Content scanner.
	 */
	public function __construct( Settings $settings, MetaValueParser $parser, ContentScanner $scanner ) {
		$this->settings = $settings;
		$this->parser   = $parser;
		$this->scanner  = $scanner;
	}

	/**
	 * Media of a post.
	 *
	 * @param WP_Post        $post      Post.
	 * @param PostTypeConfig $config    Post type settings.
	 * @param PostOverrides  $overrides Per-post choices.
	 * @param string         $format    Effective Format value.
	 */
	public function resolve( WP_Post $post, PostTypeConfig $config, PostOverrides $overrides, string $format ): MediaSelection {
		$source = $overrides->image_source ?? $config->image_source;
		$images = array();
		$video  = null;

		if ( ImageSource::CUSTOM === $source ) {
			foreach ( $overrides->media_ids as $id ) {
				$item = $this->from_attachment( $id );

				if ( null !== $item && $item->is_video() ) {
					$video = $video ?? $item;
				} elseif ( null !== $item ) {
					$images[] = $item;
				}
			}
		} elseif ( ImageSource::NONE !== $source ) {
			$images   = $this->images_from( $post, $source, $config->image_meta_key );
			$featured = $this->featured( $post );

			if ( null !== $featured && ImageSource::FEATURED !== $source && $config->featured_first ) {
				array_unshift( $images, $featured );
			}

			if ( array() === $images && null !== $featured && $config->featured_fallback ) {
				$images = array( $featured );
			}
		}

		if ( null === $video && Format::accepts_video( $format ) ) {
			$video = $this->video_for( $post, $config, $format );
		}

		$warnings = array();
		$unique   = array();

		foreach ( $images as $image ) {
			if ( ! $image->is_image() || isset( $unique[ $image->key() ] ) ) {
				continue;
			}

			if ( ! $image->is_supported() ) {
				/* translators: 1: file name, 2: MIME type */
				$warnings[] = sprintf( __( '%1$s was skipped: %2$s files cannot be posted (use JPEG, PNG or WebP).', 'social-media-auto-post' ), $image->filename(), $image->mime );
				continue;
			}

			$unique[ $image->key() ] = $image;
		}

		if ( null !== $video && ! $video->is_supported() ) {
			/* translators: 1: file name, 2: MIME type */
			$warnings[] = sprintf( __( '%1$s was skipped: %2$s videos cannot be posted (use MP4 or MOV).', 'social-media-auto-post' ), $video->filename(), $video->mime );
			$video      = null;
		}

		$images = array_values( $unique );

		if ( count( $images ) > $config->max_images ) {
			/* translators: 1: number of images found, 2: maximum number of images */
			$warnings[] = sprintf( __( '%1$d images were found; only the first %2$d are sent.', 'social-media-auto-post' ), count( $images ), $config->max_images );
			$images     = array_slice( $images, 0, $config->max_images );
		}

		$selection = new MediaSelection( $images, $video, $warnings );

		/**
		 * Filter the media sent for a post.
		 *
		 * @param MediaSelection $selection Images, video and warnings.
		 * @param WP_Post        $post      Post.
		 * @param PostTypeConfig $config    Post type settings.
		 */
		$filtered = apply_filters( 'a4smap_media', $selection, $post, $config );

		return $filtered instanceof MediaSelection ? $filtered : $selection;
	}

	/**
	 * A media library file as an item.
	 *
	 * @param int $attachment_id Attachment id.
	 */
	public function from_attachment( int $attachment_id ): ?MediaItem {
		$attachment = get_post( $attachment_id );

		if ( ! $attachment instanceof WP_Post || 'attachment' !== $attachment->post_type ) {
			return null;
		}

		$mime = (string) get_post_mime_type( $attachment );

		if ( 0 === strpos( $mime, 'video/' ) ) {
			$url = (string) wp_get_attachment_url( $attachment_id );

			return '' === $url ? null : new MediaItem( $this->absolute_url( $url ), MediaItem::VIDEO, $mime, $attachment_id );
		}

		if ( 0 !== strpos( $mime, 'image/' ) ) {
			return null;
		}

		$url = $this->image_url( $attachment_id );

		if ( '' === $url ) {
			return null;
		}

		$url_mime = $this->mime_from_url( $url );

		return new MediaItem( $this->absolute_url( $url ), MediaItem::IMAGE, '' !== $url_mime ? $url_mime : $mime, $attachment_id );
	}

	/**
	 * A URL or uploads path as an item.
	 *
	 * @param string $url      URL, root-relative path or path inside the uploads folder.
	 * @param string $expected Kind to assume when the extension does not tell.
	 */
	public function from_url( string $url, string $expected ): ?MediaItem {
		$url = $this->absolute_url( $url );

		if ( '' === $url ) {
			return null;
		}

		$mime          = $this->mime_from_url( $url );
		$attachment_id = attachment_url_to_postid( $url );

		if ( $attachment_id > 0 ) {
			$item = $this->from_attachment( $attachment_id );

			if ( null !== $item ) {
				return new MediaItem( $url, $item->kind, '' !== $mime ? $mime : $item->mime, $attachment_id );
			}
		}

		if ( 0 === strpos( $mime, 'video/' ) ) {
			$kind = MediaItem::VIDEO;
		} elseif ( 0 === strpos( $mime, 'image/' ) ) {
			$kind = MediaItem::IMAGE;
		} else {
			$kind = $expected;
		}

		return new MediaItem( $url, $kind, $mime );
	}

	/**
	 * Images from a source.
	 *
	 * @param WP_Post $post     Post.
	 * @param string  $source   ImageSource value.
	 * @param string  $meta_key Meta key for ImageSource::META.
	 * @return list<MediaItem>
	 */
	private function images_from( WP_Post $post, string $source, string $meta_key ): array {
		switch ( $source ) {
			case ImageSource::FEATURED:
				$references = has_post_thumbnail( $post ) ? array( (int) get_post_thumbnail_id( $post ) ) : array();
				break;
			case ImageSource::META:
				$references = $this->meta_references( $post, $meta_key );
				break;
			case ImageSource::GALLERY:
				$references = $this->scanner->galleries( $post );
				break;
			case ImageSource::CONTENT:
				$references = $this->scanner->images( $post );
				break;
			case ImageSource::ATTACHED:
				$references = array_map( 'intval', array_keys( get_attached_media( 'image', $post ) ) );
				break;
			default:
				$references = array();
		}

		return $this->items( $references, MediaItem::IMAGE );
	}

	/**
	 * The video of a post.
	 *
	 * @param WP_Post        $post   Post.
	 * @param PostTypeConfig $config Post type settings.
	 * @param string         $format Effective Format value.
	 */
	private function video_for( WP_Post $post, PostTypeConfig $config, string $format ): ?MediaItem {
		if ( VideoSource::NONE !== $config->video_source ) {
			$sources = array( $config->video_source );
		} elseif ( Format::VIDEO === $format ) {
			$sources = array( VideoSource::ATTACHED, VideoSource::CONTENT );
		} else {
			$sources = array();
		}

		foreach ( $sources as $source ) {
			switch ( $source ) {
				case VideoSource::META:
					$references = $this->meta_references( $post, $config->video_meta_key );
					break;
				case VideoSource::CONTENT:
					$references = $this->scanner->videos( $post );
					break;
				case VideoSource::ATTACHED:
					$references = array_map( 'intval', array_keys( get_attached_media( 'video', $post ) ) );
					break;
				default:
					$references = array();
			}

			foreach ( $this->items( $references, MediaItem::VIDEO ) as $item ) {
				if ( $item->is_video() ) {
					return $item;
				}
			}
		}

		return null;
	}

	/**
	 * Media references stored in a custom field (every row of the key).
	 *
	 * @param WP_Post $post     Post.
	 * @param string  $meta_key Meta key.
	 * @return list<int|string>
	 */
	private function meta_references( WP_Post $post, string $meta_key ): array {
		if ( '' === $meta_key ) {
			return array();
		}

		$references = array();

		foreach ( (array) get_post_meta( $post->ID, $meta_key, false ) as $value ) {
			foreach ( $this->parser->parse( $value ) as $reference ) {
				$references[] = $reference;
			}
		}

		return $references;
	}

	/**
	 * Turn ids and URLs into items.
	 *
	 * @param list<int|string> $references Attachment ids and URLs.
	 * @param string           $expected   Kind to assume for URLs without a known extension.
	 * @return list<MediaItem>
	 */
	private function items( array $references, string $expected ): array {
		$items = array();

		foreach ( $references as $reference ) {
			$item = is_int( $reference ) ? $this->from_attachment( $reference ) : $this->from_url( (string) $reference, $expected );

			if ( null !== $item ) {
				$items[] = $item;
			}
		}

		return $items;
	}

	/**
	 * The featured image as an item.
	 *
	 * @param WP_Post $post Post.
	 */
	private function featured( WP_Post $post ): ?MediaItem {
		return has_post_thumbnail( $post ) ? $this->from_attachment( (int) get_post_thumbnail_id( $post ) ) : null;
	}

	/**
	 * URL of an image in the configured size.
	 *
	 * @param int $attachment_id Attachment id.
	 */
	private function image_url( int $attachment_id ): string {
		$size = $this->settings->image_size();

		if ( 'original' === $size && function_exists( 'wp_get_original_image_url' ) ) {
			return (string) wp_get_original_image_url( $attachment_id );
		}

		if ( 'full' !== $size ) {
			$image = wp_get_attachment_image_src( $attachment_id, $size );

			if ( is_array( $image ) && ! empty( $image[0] ) ) {
				return (string) $image[0];
			}
		}

		return (string) wp_get_attachment_url( $attachment_id );
	}

	/**
	 * Make a URL absolute: protocol-relative, root-relative and uploads-relative paths are completed.
	 *
	 * @param string $url URL or path.
	 */
	private function absolute_url( string $url ): string {
		$url = trim( $url );

		if ( '' === $url ) {
			return '';
		}

		if ( 0 === strpos( $url, '//' ) ) {
			$url = set_url_scheme( $url );
		} elseif ( 0 === strpos( $url, '/' ) ) {
			$home = (string) wp_parse_url( home_url(), PHP_URL_SCHEME ) . '://' . (string) wp_parse_url( home_url(), PHP_URL_HOST );
			$port = wp_parse_url( home_url(), PHP_URL_PORT );
			$url  = $home . ( $port ? ':' . $port : '' ) . $url;
		} elseif ( ! preg_match( '#^https?://#i', $url ) ) {
			$uploads = wp_get_upload_dir();
			$url     = trailingslashit( (string) $uploads['baseurl'] ) . ltrim( $url, '/' );
		}

		return esc_url_raw( $url, array( 'http', 'https' ) );
	}

	/**
	 * MIME type from the file extension of a URL ("" when unknown).
	 *
	 * @param string $url URL.
	 */
	private function mime_from_url( string $url ): string {
		$type = wp_check_filetype( wp_basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ) );

		return is_string( $type['type'] ) ? $type['type'] : '';
	}
}
