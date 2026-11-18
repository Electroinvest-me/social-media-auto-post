<?php
/**
 * Settings screen.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Admin;

use Advert4\SocialMediaAutoPost\Api\ApiException;
use Advert4\SocialMediaAutoPost\Api\ProfileRepository;
use Advert4\SocialMediaAutoPost\Contracts\Hookable;
use Advert4\SocialMediaAutoPost\Settings\Settings;
use Advert4\SocialMediaAutoPost\Sharing\PostOverrides;
use Advert4\SocialMediaAutoPost\Sharing\ShareState;
use Advert4\SocialMediaAutoPost\Support\Logger;
use Advert4\SocialMediaAutoPost\Support\View;

defined( 'ABSPATH' ) || exit;

/**
 * Admin menu page with the connection, post type and advanced settings.
 */
final class SettingsPage implements Hookable {

	public const SLUG = 'social-media-auto-post';

	/**
	 * Hook suffix of the page.
	 *
	 * @var string
	 */
	private string $hook_suffix = '';

	/**
	 * Plugin settings.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Advert4 profiles.
	 *
	 * @var ProfileRepository
	 */
	private ProfileRepository $profiles;

	/**
	 * Activity log.
	 *
	 * @var Logger
	 */
	private Logger $logger;

	/**
	 * Overview numbers.
	 *
	 * @var Overview
	 */
	private Overview $overview;

	/**
	 * Create the page.
	 *
	 * @param Settings          $settings Plugin settings.
	 * @param ProfileRepository $profiles Advert4 profiles.
	 * @param Logger            $logger   Activity log.
	 * @param Overview          $overview Overview numbers.
	 */
	public function __construct( Settings $settings, ProfileRepository $profiles, Logger $logger, Overview $overview ) {
		$this->settings = $settings;
		$this->profiles = $profiles;
		$this->logger   = $logger;
		$this->overview = $overview;
	}

	/**
	 * Attach to the admin.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'register_setting' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( A4SMAP_FILE ), array( $this, 'action_links' ) );
		add_filter( 'admin_body_class', array( $this, 'body_class' ) );
	}

	/**
	 * Mark the settings screen so it can use the full-page design.
	 *
	 * @param string $classes Body classes.
	 */
	public function body_class( string $classes ): string {
		$screen = get_current_screen();

		if ( null !== $screen && '' !== $this->hook_suffix && $screen->id === $this->hook_suffix ) {
			$classes .= ' a4smap-screen';
		}

		return $classes;
	}

	/**
	 * Address of the Advert4 web app (derived from the API URL).
	 */
	private function app_url(): string {
		return (string) preg_replace( '#/api/v\d+$#', '', $this->settings->api_url() ) . '/app';
	}

	/**
	 * URL of the settings screen.
	 *
	 * @param string $tab Tab to open.
	 */
	public static function url( string $tab = '' ): string {
		return admin_url( 'admin.php?page=' . self::SLUG ) . ( '' !== $tab ? '#' . $tab : '' );
	}

	/**
	 * Add the menu item.
	 */
	public function menu(): void {
		$this->hook_suffix = (string) add_menu_page(
			__( 'Social Media Auto Post', 'social-media-auto-post' ),
			__( 'Social Auto Post', 'social-media-auto-post' ),
			'manage_options',
			self::SLUG,
			array( $this, 'render' ),
			'dashicons-share',
			81
		);
	}

	/**
	 * Register the option with its sanitizer.
	 */
	public function register_setting(): void {
		register_setting(
			Settings::GROUP,
			Settings::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this->settings, 'sanitize' ),
				'default'           => Settings::defaults(),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Load the assets on the settings screen.
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public function enqueue( string $hook_suffix ): void {
		if ( $hook_suffix !== $this->hook_suffix ) {
			return;
		}

		Assets::enqueue( array( 'settingsNonce' => wp_create_nonce( 'a4smap_settings' ) ) );
		wp_enqueue_media();
	}

	/**
	 * Add "Settings" to the plugin row.
	 *
	 * @param array<int|string, string> $links Action links.
	 * @return array<int|string, string>
	 */
	public function action_links( array $links ): array {
		array_unshift( $links, sprintf( '<a href="%s">%s</a>', esc_url( self::url() ), esc_html__( 'Settings', 'social-media-auto-post' ) ) );

		return $links;
	}

	/**
	 * Print the page.
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$profiles      = array();
		$profile_error = '';

		try {
			$profiles = $this->profiles->all();
		} catch ( ApiException $exception ) {
			$profile_error = $exception->getMessage();
		}

		$post_types = Settings::available_post_types();
		$meta_keys  = array();

		foreach ( array_keys( $post_types ) as $post_type ) {
			$meta_keys[ $post_type ] = $this->meta_keys( $post_type );
		}

		$stats = $this->overview->stats();

		View::render(
			'settings/page',
			array(
				'settings'      => $this->settings,
				'profiles'      => $profiles,
				'profile_error' => $profile_error,
				'post_types'    => $post_types,
				'meta_keys'     => $meta_keys,
				'log'           => $this->logger->entries(),
				'stats'         => $stats,
				'recent'        => $this->overview->recent(),
				'checklist'     => $this->overview->checklist( $profiles, $stats ),
				'app_url'       => $this->app_url(),
			)
		);
	}

	/**
	 * Custom field keys used by a post type, for the meta key suggestions.
	 *
	 * @param string $post_type Post type slug.
	 * @return list<string>
	 */
	private function meta_keys( string $post_type ): array {
		$cache_key = 'a4smap_meta_keys_' . md5( $post_type );
		$cached    = get_transient( $cache_key );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin-only suggestion list, cached in a transient.
		$keys = (array) $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT pm.meta_key FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.post_type = %s ORDER BY pm.meta_key LIMIT 500",
				$post_type
			)
		);

		$allowed_private = array( '_thumbnail_id', '_product_image_gallery', '_video_url' );
		$own             = array( PostOverrides::META_KEY, ShareState::META_KEY, ShareState::COUNT_KEY, '_a4smap_lock' );

		$keys = array_values(
			array_filter(
				array_map( 'strval', $keys ),
				static fn ( string $key ): bool => ! in_array( $key, $own, true ) && ( 0 !== strpos( $key, '_' ) || in_array( $key, $allowed_private, true ) )
			)
		);

		set_transient( $cache_key, $keys, HOUR_IN_SECONDS );

		return $keys;
	}
}
