<?php
/**
 * What would be sent to Advert4 for a post.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Sharing;

use Advert4\SocialMediaAutoPost\Media\MediaItem;

defined( 'ABSPATH' ) || exit;

/**
 * The request for a post plus everything needed to preview it.
 */
final class SharePlan {

	/**
	 * API request body.
	 *
	 * @var array<string, mixed>
	 */
	public array $payload;

	/**
	 * Format value that will be used.
	 *
	 * @var string
	 */
	public string $format;

	/**
	 * Caption.
	 *
	 * @var string
	 */
	public string $caption;

	/**
	 * Media in order.
	 *
	 * @var list<MediaItem>
	 */
	public array $media;

	/**
	 * Networks ([] = every account of the profile).
	 *
	 * @var list<string>
	 */
	public array $platforms;

	/**
	 * Profile slug or id.
	 *
	 * @var string
	 */
	public string $profile;

	/**
	 * Mode value.
	 *
	 * @var string
	 */
	public string $mode;

	/**
	 * Problems that stop the post from being sent.
	 *
	 * @var list<string>
	 */
	public array $errors;

	/**
	 * Notes that do not stop it.
	 *
	 * @var list<string>
	 */
	public array $warnings;

	/**
	 * Create the plan.
	 *
	 * @param array<string, mixed> $payload   API request body.
	 * @param string               $format    Format value that will be used.
	 * @param string               $caption   Caption.
	 * @param list<MediaItem>      $media     Media in order.
	 * @param list<string>         $platforms Networks ([] = every account of the profile).
	 * @param string               $profile   Profile slug or id.
	 * @param string               $mode      Mode value.
	 * @param list<string>         $errors    Problems that stop the post from being sent.
	 * @param list<string>         $warnings  Notes that do not stop it.
	 */
	public function __construct( array $payload, string $format, string $caption, array $media, array $platforms, string $profile, string $mode, array $errors = array(), array $warnings = array() ) {
		$this->payload   = $payload;
		$this->format    = $format;
		$this->caption   = $caption;
		$this->media     = $media;
		$this->platforms = $platforms;
		$this->profile   = $profile;
		$this->mode      = $mode;
		$this->errors    = $errors;
		$this->warnings  = $warnings;
	}

	/**
	 * Whether the post can be sent.
	 */
	public function is_valid(): bool {
		return array() === $this->errors;
	}
}
