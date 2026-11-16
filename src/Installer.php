<?php
/**
 * Activation and deactivation.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost;

use Advert4\SocialMediaAutoPost\Settings\Settings;
use Advert4\SocialMediaAutoPost\Sharing\Queue;
use Advert4\SocialMediaAutoPost\Support\Site;

defined( 'ABSPATH' ) || exit;

/**
 * Creates the default settings and removes scheduled jobs.
 */
final class Installer {

	/**
	 * Plugin activated.
	 */
	public static function activate(): void {
		add_option( Settings::OPTION, Settings::defaults() );
		Site::secret();
	}

	/**
	 * Plugin deactivated: stop background jobs, keep the data.
	 */
	public static function deactivate(): void {
		Queue::clear_all();
	}
}
