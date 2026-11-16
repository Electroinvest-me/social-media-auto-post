<?php
/**
 * Plugin Name:       Social Media Auto Post
 * Plugin URI:        https://advert4.me
 * Description:       Auto post your WordPress posts, images, galleries and videos to Facebook, Instagram and TikTok through Advert4 — automatically on publish or only when you choose.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Advert4
 * Author URI:        https://advert4.me
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       social-media-auto-post
 * Domain Path:       /languages
 *
 * @package Advert4\SocialMediaAutoPost
 */

defined( 'ABSPATH' ) || exit;

define( 'A4SMAP_VERSION', '1.0.0' );
define( 'A4SMAP_FILE', __FILE__ );
define( 'A4SMAP_DIR', plugin_dir_path( __FILE__ ) );
define( 'A4SMAP_URL', plugin_dir_url( __FILE__ ) );
define( 'A4SMAP_MIN_PHP', '7.4' );

if ( version_compare( PHP_VERSION, A4SMAP_MIN_PHP, '<' ) ) {
	add_action(
		'admin_notices',
		static function () {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: 1: required PHP version, 2: current PHP version */
						__( 'Social Media Auto Post needs PHP %1$s or newer. This site runs PHP %2$s.', 'social-media-auto-post' ),
						A4SMAP_MIN_PHP,
						PHP_VERSION
					)
				)
			);
		}
	);
	return;
}

spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix = 'Advert4\\SocialMediaAutoPost\\';

		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}

		$path = A4SMAP_DIR . 'src/' . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';

		if ( is_readable( $path ) ) {
			require $path;
		}
	}
);

register_activation_hook( __FILE__, array( \Advert4\SocialMediaAutoPost\Installer::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \Advert4\SocialMediaAutoPost\Installer::class, 'deactivate' ) );

add_action( 'plugins_loaded', array( \Advert4\SocialMediaAutoPost\Plugin::class, 'boot' ) );
