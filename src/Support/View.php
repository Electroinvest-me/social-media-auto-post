<?php
/**
 * Renders the PHP templates in /views.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Minimal template renderer.
 */
final class View {

	/**
	 * Print a template.
	 *
	 * @param string               $name Template path below /views, without ".php".
	 * @param array<string, mixed> $vars Variables available in the template.
	 */
	public static function render( string $name, array $vars = array() ): void {
		$file = A4SMAP_DIR . 'views/' . $name . '.php';

		if ( ! is_readable( $file ) ) {
			return;
		}

		( static function ( string $a4smap_file, array $a4smap_vars ): void {
			extract( $a4smap_vars, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Template variables.
			require $a4smap_file;
		} )( $file, $vars );
	}

	/**
	 * Render a template into a string.
	 *
	 * @param string               $name Template path below /views, without ".php".
	 * @param array<string, mixed> $vars Variables available in the template.
	 */
	public static function capture( string $name, array $vars = array() ): string {
		ob_start();
		self::render( $name, $vars );

		return (string) ob_get_clean();
	}
}
