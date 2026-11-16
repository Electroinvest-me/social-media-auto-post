<?php
/**
 * Contract for services that attach themselves to WordPress hooks.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * A service that registers actions and filters when the plugin boots.
 */
interface Hookable {

	/**
	 * Attach the service to WordPress actions and filters.
	 */
	public function register(): void;
}
