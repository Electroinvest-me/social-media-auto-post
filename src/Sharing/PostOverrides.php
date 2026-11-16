<?php
/**
 * Per-post choices made in the editor meta box.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Sharing;

use Advert4\SocialMediaAutoPost\Enums\Format;
use Advert4\SocialMediaAutoPost\Enums\ImageSource;
use Advert4\SocialMediaAutoPost\Enums\Mode;
use Advert4\SocialMediaAutoPost\Enums\Network;

defined( 'ABSPATH' ) || exit;

/**
 * Overrides of the post type defaults for one post. Null means "use the default".
 */
final class PostOverrides {

	public const META_KEY = '_a4smap_overrides';

	/**
	 * Share this post (null = post type default).
	 *
	 * @var bool|null
	 */
	public ?bool $share;

	/**
	 * Format value.
	 *
	 * @var string|null
	 */
	public ?string $format;

	/**
	 * ImageSource value.
	 *
	 * @var string|null
	 */
	public ?string $image_source;

	/**
	 * Attachments chosen for ImageSource::CUSTOM.
	 *
	 * @var list<int>
	 */
	public array $media_ids;

	/**
	 * Networks.
	 *
	 * @var list<string>|null
	 */
	public ?array $platforms;

	/**
	 * Caption template ("" = post type template).
	 *
	 * @var string
	 */
	public string $caption;

	/**
	 * Mode value.
	 *
	 * @var string|null
	 */
	public ?string $mode;

	/**
	 * Create the overrides.
	 *
	 * @param bool|null         $share        Share this post (null = post type default).
	 * @param string|null       $format       Format value.
	 * @param string|null       $image_source ImageSource value.
	 * @param list<int>         $media_ids    Attachments chosen for ImageSource::CUSTOM.
	 * @param list<string>|null $platforms    Networks.
	 * @param string            $caption      Caption template ("" = post type template).
	 * @param string|null       $mode         Mode value.
	 */
	public function __construct( ?bool $share = null, ?string $format = null, ?string $image_source = null, array $media_ids = array(), ?array $platforms = null, string $caption = '', ?string $mode = null ) {
		$this->share        = $share;
		$this->format       = Format::try_from( $format );
		$this->image_source = ImageSource::try_from( $image_source );
		$this->media_ids    = array_values( array_filter( array_map( 'absint', $media_ids ) ) );
		$this->platforms    = null === $platforms ? null : Network::filter( $platforms );
		$this->caption      = $caption;
		$this->mode         = Mode::try_from( $mode );
	}

	/**
	 * Overrides saved on a post.
	 *
	 * @param int $post_id Post id.
	 */
	public static function for_post( int $post_id ): self {
		$data = get_post_meta( $post_id, self::META_KEY, true );

		return self::from_array( is_array( $data ) ? $data : array() );
	}

	/**
	 * Build from stored values.
	 *
	 * @param array<string, mixed> $data Stored values.
	 */
	public static function from_array( array $data ): self {
		$share = $data['share'] ?? null;

		return new self(
			null === $share ? null : (bool) $share,
			isset( $data['format'] ) ? (string) $data['format'] : null,
			isset( $data['image_source'] ) ? (string) $data['image_source'] : null,
			(array) ( $data['media_ids'] ?? array() ),
			isset( $data['platforms'] ) && is_array( $data['platforms'] ) ? $data['platforms'] : null,
			(string) ( $data['caption'] ?? '' ),
			isset( $data['mode'] ) ? (string) $data['mode'] : null
		);
	}

	/**
	 * Build from the meta box fields.
	 *
	 * @param array<string, mixed> $input             Unslashed $_POST['a4smap'] values.
	 * @param list<string>         $default_platforms Networks of the post type, to detect "unchanged".
	 */
	public static function from_request( array $input, array $default_platforms ): self {
		$text = static function ( string $key ) use ( $input ): string {
			return isset( $input[ $key ] ) && is_scalar( $input[ $key ] ) ? sanitize_text_field( (string) $input[ $key ] ) : '';
		};

		$share = null;

		if ( ! empty( $input['share_field'] ) ) {
			$share = ! empty( $input['share'] );
		}

		$platforms = null;

		if ( ! empty( $input['platforms_field'] ) ) {
			$chosen = Network::filter( $input['platforms'] ?? array() );
			$basis  = Network::filter( $default_platforms );

			$platforms = $chosen === $basis ? null : $chosen;
		}

		return new self(
			$share,
			$text( 'format' ),
			$text( 'image_source' ),
			array_map( 'absint', explode( ',', $text( 'media_ids' ) ) ),
			$platforms,
			isset( $input['caption'] ) && is_string( $input['caption'] ) ? sanitize_textarea_field( $input['caption'] ) : '',
			$text( 'mode' )
		);
	}

	/**
	 * Store on a post.
	 *
	 * @param int $post_id Post id.
	 */
	public function save( int $post_id ): void {
		update_post_meta( $post_id, self::META_KEY, $this->to_array() );
	}

	/**
	 * Whether anything apart from the share flag differs from the defaults.
	 */
	public function is_customized(): bool {
		return null !== $this->format || null !== $this->image_source || null !== $this->platforms || '' !== $this->caption || null !== $this->mode;
	}

	/**
	 * Stored representation.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'share'        => $this->share,
			'format'       => $this->format,
			'image_source' => $this->image_source,
			'media_ids'    => $this->media_ids,
			'platforms'    => $this->platforms,
			'caption'      => $this->caption,
			'mode'         => $this->mode,
		);
	}
}
