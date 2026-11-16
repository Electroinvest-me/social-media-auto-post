<?php
/**
 * What Advert4 does with a post it receives.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Enums;

defined( 'ABSPATH' ) || exit;

/**
 * Publish mode.
 */
final class Mode {

	use Choices;

	public const PUBLISH = 'publish';
	public const DRAFT   = 'draft';

	/**
	 * Every value.
	 *
	 * @return list<string>
	 */
	public static function values(): array {
		return array( self::PUBLISH, self::DRAFT );
	}

	/**
	 * Labels keyed by value.
	 *
	 * @return array<string, string>
	 */
	public static function labels(): array {
		return array(
			self::PUBLISH => __( 'Publish on the networks right away', 'social-media-auto-post' ),
			self::DRAFT   => __( 'Save as a draft in Advert4 for review', 'social-media-auto-post' ),
		);
	}
}
