<?php
/**
 * Media found for a post.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Media;

defined( 'ABSPATH' ) || exit;

/**
 * Images and video found for a post, with notes about skipped files.
 */
final class MediaSelection {

	/**
	 * Images in order.
	 *
	 * @var list<MediaItem>
	 */
	public array $images;

	/**
	 * First video.
	 *
	 * @var MediaItem|null
	 */
	public ?MediaItem $video;

	/**
	 * Notes about skipped files.
	 *
	 * @var list<string>
	 */
	public array $warnings;

	/**
	 * Create the selection.
	 *
	 * @param list<MediaItem> $images   Images in order.
	 * @param MediaItem|null  $video    First video.
	 * @param list<string>    $warnings Notes about skipped files.
	 */
	public function __construct( array $images, ?MediaItem $video, array $warnings = array() ) {
		$this->images   = $images;
		$this->video    = $video;
		$this->warnings = $warnings;
	}
}
