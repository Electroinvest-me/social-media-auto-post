<?php
/**
 * Social networks Advert4 publishes to.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Enums;

defined( 'ABSPATH' ) || exit;

/**
 * Network identifiers as used by the Advert4 API ("platforms").
 */
final class Network {

	use Choices;

	public const FACEBOOK  = 'facebook';
	public const INSTAGRAM = 'instagram';
	public const TIKTOK    = 'tiktok';

	/**
	 * Every value.
	 *
	 * @return list<string>
	 */
	public static function values(): array {
		return array( self::FACEBOOK, self::INSTAGRAM, self::TIKTOK );
	}

	/**
	 * Brand names keyed by value.
	 *
	 * @return array<string, string>
	 */
	public static function labels(): array {
		return array(
			self::FACEBOOK  => 'Facebook',
			self::INSTAGRAM => 'Instagram',
			self::TIKTOK    => 'TikTok',
		);
	}

	/**
	 * Brand names of a list of values; unknown values are kept as they are.
	 *
	 * @param list<string> $values Network values.
	 * @return list<string>
	 */
	public static function label_list( array $values ): array {
		return array_map( array( self::class, 'label' ), $values );
	}

	/**
	 * Longest caption the network accepts.
	 *
	 * @param string $value Network.
	 */
	public static function caption_limit( string $value ): int {
		return self::FACEBOOK === $value ? 63206 : 2200;
	}

	/**
	 * Only the valid networks of a list, in the canonical order.
	 *
	 * @param mixed $values Submitted values.
	 * @return list<string>
	 */
	public static function filter( $values ): array {
		return array_values( array_intersect( self::values(), array_map( 'strval', (array) $values ) ) );
	}
}
