<?php
/**
 * Plugin settings stored in one option.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Settings;

use Advert4\SocialMediaAutoPost\Contracts\Hookable;
use WP_Post_Type;

defined( 'ABSPATH' ) || exit;

/**
 * Reads, sanitizes and caches the plugin settings.
 *
 * The API key and URL may also be defined in wp-config.php with the
 * A4SMAP_API_KEY and A4SMAP_API_URL constants, which take precedence.
 */
final class Settings implements Hookable {

	public const OPTION          = 'a4smap_settings';
	public const GROUP           = 'a4smap';
	public const DEFAULT_API_URL = 'https://advert4.me/api/v1';

	/**
	 * Cached option value.
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $values = null;

	/**
	 * Clear the cache whenever the option changes.
	 */
	public function register(): void {
		add_action( 'add_option_' . self::OPTION, array( $this, 'flush' ) );
		add_action( 'update_option_' . self::OPTION, array( $this, 'flush' ) );
	}

	/**
	 * Default settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'api_key'             => '',
			'api_url'             => '',
			'image_size'          => 'full',
			'skip_protected'      => true,
			'timeout'             => 120,
			'delete_on_uninstall' => false,
			'post_types'          => array(
				'post' => PostTypeConfig::defaults( 'post' ),
			),
		);
	}

	/**
	 * Forget the cached value.
	 */
	public function flush(): void {
		$this->values = null;
	}

	/**
	 * Every setting.
	 *
	 * @return array<string, mixed>
	 */
	public function all(): array {
		if ( null === $this->values ) {
			$stored       = get_option( self::OPTION, array() );
			$this->values = array_merge( self::defaults(), is_array( $stored ) ? $stored : array() );
		}

		return $this->values;
	}

	/**
	 * One setting.
	 *
	 * @param string $key      Setting name.
	 * @param mixed  $fallback Value when the setting is missing.
	 * @return mixed
	 */
	public function get( string $key, $fallback = null ) {
		return $this->all()[ $key ] ?? $fallback;
	}

	/**
	 * Advert4 workspace API key.
	 */
	public function api_key(): string {
		if ( $this->api_key_in_config() ) {
			return (string) constant( 'A4SMAP_API_KEY' );
		}

		return (string) $this->get( 'api_key', '' );
	}

	/**
	 * Whether the API key comes from wp-config.php.
	 */
	public function api_key_in_config(): bool {
		return defined( 'A4SMAP_API_KEY' ) && is_string( constant( 'A4SMAP_API_KEY' ) ) && '' !== constant( 'A4SMAP_API_KEY' );
	}

	/**
	 * Whether an API key is available.
	 */
	public function has_api_key(): bool {
		return '' !== $this->api_key();
	}

	/**
	 * Base URL of the Advert4 API, without a trailing slash.
	 */
	public function api_url(): string {
		$url = defined( 'A4SMAP_API_URL' ) && is_string( constant( 'A4SMAP_API_URL' ) ) ? (string) constant( 'A4SMAP_API_URL' ) : (string) $this->get( 'api_url', '' );

		return untrailingslashit( '' !== $url ? $url : self::DEFAULT_API_URL );
	}

	/**
	 * Image size sent to the networks ("full", "original" or a registered size).
	 */
	public function image_size(): string {
		return (string) $this->get( 'image_size', 'full' );
	}

	/**
	 * Whether password protected posts are never shared.
	 */
	public function skip_protected(): bool {
		return (bool) $this->get( 'skip_protected', true );
	}

	/**
	 * Timeout in seconds for creating a post (Advert4 downloads the media meanwhile).
	 */
	public function timeout(): int {
		return max( 10, min( 600, (int) $this->get( 'timeout', 120 ) ) );
	}

	/**
	 * Whether uninstalling removes the settings and post data.
	 */
	public function delete_on_uninstall(): bool {
		return (bool) $this->get( 'delete_on_uninstall', false );
	}

	/**
	 * Configuration of one post type.
	 *
	 * @param string $post_type Post type slug.
	 */
	public function post_type( string $post_type ): PostTypeConfig {
		$rows = (array) $this->get( 'post_types', array() );
		$row  = isset( $rows[ $post_type ] ) && is_array( $rows[ $post_type ] ) ? $rows[ $post_type ] : array( 'enabled' => false );

		return new PostTypeConfig( $post_type, $row );
	}

	/**
	 * Slugs of the post types that are shared.
	 *
	 * @return list<string>
	 */
	public function enabled_post_types(): array {
		$enabled = array();

		foreach ( array_keys( (array) $this->get( 'post_types', array() ) ) as $post_type ) {
			$post_type = (string) $post_type;

			if ( post_type_exists( $post_type ) && $this->post_type( $post_type )->enabled ) {
				$enabled[] = $post_type;
			}
		}

		return $enabled;
	}

	/**
	 * Whether a post type is shared.
	 *
	 * @param string $post_type Post type slug.
	 */
	public function is_enabled( string $post_type ): bool {
		return in_array( $post_type, $this->enabled_post_types(), true );
	}

	/**
	 * Post types that can be configured.
	 *
	 * @return array<string, WP_Post_Type>
	 */
	public static function available_post_types(): array {
		$types = get_post_types( array( 'public' => true ), 'objects' );
		unset( $types['attachment'] );

		/**
		 * Filter the post types listed on the settings screen.
		 *
		 * @param array<string, WP_Post_Type> $types Post type objects keyed by slug.
		 */
		return (array) apply_filters( 'a4smap_post_types', $types );
	}

	/**
	 * Image sizes offered on the settings screen.
	 *
	 * @return array<string, string>
	 */
	public static function image_sizes(): array {
		$sizes = array(
			'full'     => __( 'Full size (recommended)', 'social-media-auto-post' ),
			'original' => __( 'Original upload (before WordPress scaled it down)', 'social-media-auto-post' ),
		);

		foreach ( get_intermediate_image_sizes() as $size ) {
			$sizes[ $size ] = $size;
		}

		return $sizes;
	}

	/**
	 * Sanitize the option before it is saved (register_setting callback).
	 *
	 * @param mixed $input Submitted values.
	 * @return array<string, mixed>
	 */
	public function sanitize( $input ): array {
		$input   = is_array( $input ) ? $input : array();
		$current = $this->all();
		$output  = $current;

		if ( ! empty( $input['api_key_remove'] ) ) {
			$output['api_key'] = '';
		} elseif ( isset( $input['api_key'] ) && is_string( $input['api_key'] ) && '' !== trim( $input['api_key'] ) ) {
			$output['api_key'] = sanitize_text_field( trim( $input['api_key'] ) );
		}

		$api_url           = isset( $input['api_url'] ) && is_string( $input['api_url'] ) ? trim( $input['api_url'] ) : '';
		$output['api_url'] = '' !== $api_url && untrailingslashit( $api_url ) !== self::DEFAULT_API_URL ? esc_url_raw( $api_url, array( 'https', 'http' ) ) : '';

		$image_size           = isset( $input['image_size'] ) && is_string( $input['image_size'] ) ? sanitize_key( $input['image_size'] ) : 'full';
		$output['image_size'] = array_key_exists( $image_size, self::image_sizes() ) ? $image_size : 'full';

		$output['skip_protected']      = ! empty( $input['skip_protected'] );
		$output['delete_on_uninstall'] = ! empty( $input['delete_on_uninstall'] );
		$output['timeout']             = max( 10, min( 600, absint( $input['timeout'] ?? 120 ) ) );

		$submitted            = isset( $input['post_types'] ) && is_array( $input['post_types'] ) ? $input['post_types'] : array();
		$output['post_types'] = array();

		foreach ( array_keys( self::available_post_types() ) as $post_type ) {
			$output['post_types'][ $post_type ] = PostTypeConfig::sanitize( $post_type, $submitted[ $post_type ] ?? array() );
		}

		if ( $output['api_key'] !== $current['api_key'] || $output['api_url'] !== $current['api_url'] ) {
			delete_transient( 'a4smap_profiles' );
		}

		return $output;
	}
}
