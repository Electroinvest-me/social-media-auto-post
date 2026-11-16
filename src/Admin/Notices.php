<?php
/**
 * Admin notices.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Admin;

use Advert4\SocialMediaAutoPost\Contracts\Hookable;
use Advert4\SocialMediaAutoPost\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Reminds administrators to connect Advert4.
 */
final class Notices implements Hookable {

	/**
	 * Plugin settings.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Create the service.
	 *
	 * @param Settings $settings Plugin settings.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Attach to the admin.
	 */
	public function register(): void {
		add_action( 'admin_notices', array( $this, 'missing_key' ) );
	}

	/**
	 * Ask for the API key on the dashboard and plugins screens.
	 */
	public function missing_key(): void {
		$screen = get_current_screen();

		if ( null === $screen || ! in_array( $screen->id, array( 'dashboard', 'plugins' ), true ) || $this->settings->has_api_key() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-info"><p><strong>%1$s</strong> %2$s <a href="%3$s">%4$s</a></p></div>',
			esc_html__( 'Social Media Auto Post:', 'social-media-auto-post' ),
			esc_html__( 'connect your Advert4 workspace to start sharing posts to Facebook, Instagram and TikTok.', 'social-media-auto-post' ),
			esc_url( SettingsPage::url() ),
			esc_html__( 'Add the API key', 'social-media-auto-post' )
		);
	}
}
