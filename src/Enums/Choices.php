<?php
/**
 * Shared helpers of the option lists.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Enums;

defined( 'ABSPATH' ) || exit;

/**
 * Validation and labels for classes that define values() and labels().
 */
trait Choices {

	/**
	 * Every allowed value.
	 *
	 * @return list<string>
	 */
	abstract public static function values(): array;

	/**
	 * Labels keyed by value.
	 *
	 * @return array<string, string>
	 */
	abstract public static function labels(): array;

	/**
	 * Whether a value is one of the allowed values.
	 *
	 * @param mixed $value Value to check.
	 */
	public static function is_valid( $value ): bool {
		return is_string( $value ) && in_array( $value, static::values(), true );
	}

	/**
	 * The value when it is allowed, otherwise the fallback.
	 *
	 * @param mixed  $value    Value to check.
	 * @param string $fallback Value to use instead.
	 */
	public static function sanitize( $value, string $fallback ): string {
		return static::is_valid( $value ) ? (string) $value : $fallback;
	}

	/**
	 * The value when it is allowed, otherwise null.
	 *
	 * @param mixed $value Value to check.
	 */
	public static function try_from( $value ): ?string {
		return static::is_valid( $value ) ? (string) $value : null;
	}

	/**
	 * Human readable label of a value.
	 *
	 * @param string $value Value.
	 */
	public static function label( string $value ): string {
		$labels = static::labels();

		return $labels[ $value ] ?? $value;
	}

	/**
	 * Value => label map for select fields.
	 *
	 * @return array<string, string>
	 */
	public static function options(): array {
		return static::labels();
	}
}
