<?php
/**
 * Facts about this WordPress site.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Site identity, reachability and the webhook secret.
 */
final class Site {

	public const SECRET_OPTION = 'a4smap_secret';

	/**
	 * Short stable id of this site, used in Advert4 external ids.
	 */
	public static function key(): string {
		return substr( md5( (string) home_url() ), 0, 10 );
	}

	/**
	 * Whether Advert4 can reach this site from the internet (media downloads and webhooks).
	 */
	public static function is_public(): bool {
		$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );

		if ( '' === $host || 'localhost' === $host ) {
			return false;
		}

		foreach ( array( '.local', '.localhost', '.test', '.example', '.invalid', '.lan', '.internal' ) as $suffix ) {
			if ( substr( $host, -strlen( $suffix ) ) === $suffix ) {
				return false;
			}
		}

		if ( false !== filter_var( $host, FILTER_VALIDATE_IP ) ) {
			return false !== filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
		}

		return false !== strpos( $host, '.' );
	}

	/**
	 * Secret used to sign webhook URLs; created on first use.
	 */
	public static function secret(): string {
		$secret = get_option( self::SECRET_OPTION );

		if ( ! is_string( $secret ) || strlen( $secret ) < 32 ) {
			$secret = wp_generate_password( 64, false, false );
			update_option( self::SECRET_OPTION, $secret, false );
		}

		return $secret;
	}

	/**
	 * Token that proves a webhook call belongs to a post.
	 *
	 * @param int $post_id Post id.
	 */
	public static function webhook_token( int $post_id ): string {
		return hash_hmac( 'sha256', 'webhook|' . $post_id, self::secret() );
	}
}
