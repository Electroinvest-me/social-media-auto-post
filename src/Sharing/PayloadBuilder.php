<?php
/**
 * Turns a WordPress post into an Advert4 API request.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Sharing;

use Advert4\SocialMediaAutoPost\Api\ProfileRepository;
use Advert4\SocialMediaAutoPost\Content\CaptionBuilder;
use Advert4\SocialMediaAutoPost\Enums\Format;
use Advert4\SocialMediaAutoPost\Enums\Mode;
use Advert4\SocialMediaAutoPost\Enums\Network;
use Advert4\SocialMediaAutoPost\Media\MediaItem;
use Advert4\SocialMediaAutoPost\Media\MediaResolver;
use Advert4\SocialMediaAutoPost\Media\MediaSelection;
use Advert4\SocialMediaAutoPost\Settings\PostTypeConfig;
use Advert4\SocialMediaAutoPost\Settings\Settings;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Combines the post type settings, per-post overrides, media and caption.
 */
final class PayloadBuilder {

	/**
	 * Plugin settings.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Media resolver.
	 *
	 * @var MediaResolver
	 */
	private MediaResolver $media;

	/**
	 * Caption builder.
	 *
	 * @var CaptionBuilder
	 */
	private CaptionBuilder $captions;

	/**
	 * Advert4 profiles.
	 *
	 * @var ProfileRepository
	 */
	private ProfileRepository $profiles;

	/**
	 * Create the builder.
	 *
	 * @param Settings          $settings Plugin settings.
	 * @param MediaResolver     $media    Media resolver.
	 * @param CaptionBuilder    $captions Caption builder.
	 * @param ProfileRepository $profiles Advert4 profiles.
	 */
	public function __construct( Settings $settings, MediaResolver $media, CaptionBuilder $captions, ProfileRepository $profiles ) {
		$this->settings = $settings;
		$this->media    = $media;
		$this->captions = $captions;
		$this->profiles = $profiles;
	}

	/**
	 * Plan for a post.
	 *
	 * @param WP_Post            $post      Post.
	 * @param PostOverrides|null $overrides Per-post choices; read from the post when null (pass unsaved ones for a preview).
	 */
	public function build( WP_Post $post, ?PostOverrides $overrides = null ): SharePlan {
		$config    = $this->settings->post_type( $post->post_type );
		$overrides = $overrides ?? PostOverrides::for_post( $post->ID );
		$format    = $overrides->format ?? $config->format;
		$mode      = $overrides->mode ?? $config->mode;
		$platforms = $overrides->platforms ?? $config->platforms;
		$profile   = $this->profile( $config );
		$errors    = array();

		$selection = $this->media->resolve( $post, $config, $overrides, $format );
		$warnings  = $selection->warnings;

		list( $format, $media ) = $this->choose_media( $format, $selection, $errors, $warnings );

		if ( '' === $profile ) {
			$errors[] = __( 'Choose the Advert4 profile for this post type in Social Media Auto Post settings.', 'social-media-auto-post' );
		}

		$template = '' !== trim( $overrides->caption ) ? $overrides->caption : $config->caption;
		$caption  = $this->captions->build( $post, $template, $this->caption_limit( $platforms, $profile ) );

		$payload = array(
			'profile' => $profile,
			'type'    => $format,
			'caption' => $caption,
			'media'   => array_map(
				static function ( MediaItem $item ): array {
					return $item->to_payload();
				},
				$media
			),
			'draft'   => Mode::DRAFT === $mode,
		);

		if ( array() !== $platforms ) {
			$payload['platforms'] = $platforms;
		}

		/**
		 * Filter the Advert4 API request for a post (POST /api/v1/posts).
		 *
		 * @param array<string, mixed> $payload Request body.
		 * @param WP_Post              $post    Post.
		 */
		$payload = (array) apply_filters( 'a4smap_payload', $payload, $post );

		return new SharePlan( $payload, $format, $caption, $media, $platforms, $profile, $mode, $errors, $warnings );
	}

	/**
	 * Profile used for a post type: the configured one, or the only profile of the workspace.
	 *
	 * @param PostTypeConfig $config Post type settings.
	 */
	public function profile( PostTypeConfig $config ): string {
		return '' !== $config->profile ? $config->profile : $this->profiles->default_reference();
	}

	/**
	 * Networks a post type publishes to by default: the configured ones, or every network connected in the profile.
	 *
	 * @param PostTypeConfig $config Post type settings.
	 * @return list<string>
	 */
	public function default_platforms( PostTypeConfig $config ): array {
		return array() !== $config->platforms ? $config->platforms : $this->profiles->networks( $this->profile( $config ) );
	}

	/**
	 * Pick the final format and media.
	 *
	 * @param string         $format    Requested Format value.
	 * @param MediaSelection $selection Media found.
	 * @param list<string>   $errors    Problems (appended).
	 * @param list<string>   $warnings  Notes (appended).
	 * @return array{0: string, 1: list<MediaItem>}
	 */
	private function choose_media( string $format, MediaSelection $selection, array &$errors, array &$warnings ): array {
		$images = $selection->images;
		$video  = $selection->video;

		switch ( $format ) {
			case Format::VIDEO:
				if ( null === $video ) {
					$errors[] = __( 'No video was found for this post.', 'social-media-auto-post' );

					return array( $format, array() );
				}

				return array( Format::VIDEO, array( $video ) );

			case Format::STORY:
				$item = $video ?? ( $images[0] ?? null );

				if ( null === $item ) {
					$errors[] = __( 'A Story needs an image or a video, and none was found.', 'social-media-auto-post' );

					return array( $format, array() );
				}

				return array( Format::STORY, array( $item ) );

			case Format::GALLERY:
				if ( array() === $images ) {
					$errors[] = __( 'No images were found for the gallery.', 'social-media-auto-post' );

					return array( $format, array() );
				}

				if ( 1 === count( $images ) ) {
					$warnings[] = __( 'Only one image was found, so it is posted as a single image.', 'social-media-auto-post' );

					return array( Format::IMAGE, $images );
				}

				return array( Format::GALLERY, $images );

			case Format::IMAGE:
				if ( array() === $images ) {
					$errors[] = __( 'No image was found for this post.', 'social-media-auto-post' );

					return array( $format, array() );
				}

				return array( Format::IMAGE, array( $images[0] ) );

			default:
				if ( null !== $video ) {
					return array( Format::VIDEO, array( $video ) );
				}

				if ( count( $images ) > 1 ) {
					return array( Format::GALLERY, $images );
				}

				if ( array() === $images ) {
					$warnings[] = __( 'No image was found: the post is sent as text only, which Instagram and TikTok do not accept.', 'social-media-auto-post' );
				}

				return array( Format::IMAGE, $images );
		}
	}

	/**
	 * Longest caption every chosen network accepts.
	 *
	 * @param list<string> $platforms Chosen networks ([] = every account of the profile).
	 * @param string       $profile   Profile slug or id.
	 */
	private function caption_limit( array $platforms, string $profile ): int {
		$networks = array() !== $platforms ? $platforms : $this->profiles->networks( $profile );

		if ( array() === $networks ) {
			$networks = Network::values();
		}

		return min( array_map( array( Network::class, 'caption_limit' ), $networks ) );
	}
}
