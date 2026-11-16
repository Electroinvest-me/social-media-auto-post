<?php
/**
 * Finds media inside post content.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Media;

use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Reads images and videos from blocks, shortcodes and classic HTML.
 */
final class ContentScanner {

	/**
	 * Every image in the content, in order.
	 *
	 * @param WP_Post $post Post.
	 * @return list<int|string> Attachment ids and URLs.
	 */
	public function images( WP_Post $post ): array {
		$content = (string) $post->post_content;
		$found   = $this->image_tags( $content );

		if ( has_blocks( $content ) ) {
			$this->walk(
				parse_blocks( $content ),
				static function ( array $block ) use ( &$found ): bool {
					$attrs = (array) ( $block['attrs'] ?? array() );

					if ( 'core/cover' === $block['blockName'] && ! empty( $attrs['id'] ) && 'video' !== ( $attrs['backgroundType'] ?? 'image' ) ) {
						$found[] = (int) $attrs['id'];
					}

					if ( 'core/media-text' === $block['blockName'] && ! empty( $attrs['mediaId'] ) && 'video' !== ( $attrs['mediaType'] ?? 'image' ) ) {
						$found[] = (int) $attrs['mediaId'];
					}

					return true;
				}
			);
		}

		return array_merge( $found, $this->gallery_shortcodes( $post ) );
	}

	/**
	 * Images of the galleries in the content (gallery blocks and [gallery] shortcodes).
	 *
	 * @param WP_Post $post Post.
	 * @return list<int|string>
	 */
	public function galleries( WP_Post $post ): array {
		$content = (string) $post->post_content;
		$found   = array();

		if ( has_blocks( $content ) ) {
			$this->walk(
				parse_blocks( $content ),
				function ( array $block ) use ( &$found ): bool {
					if ( 'core/gallery' !== $block['blockName'] ) {
						return true;
					}

					$ids = (array) ( $block['attrs']['ids'] ?? array() );

					if ( array() !== $ids ) {
						foreach ( $ids as $id ) {
							$found[] = (int) $id;
						}
					} else {
						foreach ( $this->image_tags( serialize_block( $block ) ) as $reference ) {
							$found[] = $reference;
						}
					}

					return false;
				}
			);
		}

		return array_merge( $found, $this->gallery_shortcodes( $post ) );
	}

	/**
	 * Videos in the content, in order.
	 *
	 * @param WP_Post $post Post.
	 * @return list<int|string>
	 */
	public function videos( WP_Post $post ): array {
		$content = (string) $post->post_content;
		$found   = array();

		if ( has_blocks( $content ) ) {
			$this->walk(
				parse_blocks( $content ),
				static function ( array $block ) use ( &$found ): bool {
					$attrs = (array) ( $block['attrs'] ?? array() );

					if ( 'core/video' === $block['blockName'] && ! empty( $attrs['id'] ) ) {
						$found[] = (int) $attrs['id'];
					}

					if ( 'core/cover' === $block['blockName'] && ! empty( $attrs['id'] ) && 'video' === ( $attrs['backgroundType'] ?? '' ) ) {
						$found[] = (int) $attrs['id'];
					}

					if ( 'core/media-text' === $block['blockName'] && ! empty( $attrs['mediaId'] ) && 'video' === ( $attrs['mediaType'] ?? '' ) ) {
						$found[] = (int) $attrs['mediaId'];
					}

					return true;
				}
			);
		}

		if ( has_shortcode( $content, 'video' ) && preg_match_all( '/' . get_shortcode_regex( array( 'video' ) ) . '/', $content, $shortcodes, PREG_SET_ORDER ) ) {
			foreach ( $shortcodes as $shortcode ) {
				$atts = (array) shortcode_parse_atts( $shortcode[3] );

				foreach ( array( 'src', 'mp4', 'mov', 'm4v' ) as $key ) {
					if ( ! empty( $atts[ $key ] ) && is_string( $atts[ $key ] ) ) {
						$found[] = $atts[ $key ];
						break;
					}
				}
			}
		}

		if ( preg_match_all( '/<(?:video|source)\b[^>]*\bsrc=(["\'])(.*?)\1/i', $content, $tags ) ) {
			foreach ( $tags[2] as $src ) {
				$found[] = html_entity_decode( $src, ENT_QUOTES );
			}
		}

		if ( preg_match_all( '#https?://[^\s"\'<>\]\[]+\.(?:mp4|mov|m4v)\b#i', $content, $links ) ) {
			foreach ( $links[0] as $link ) {
				$found[] = $link;
			}
		}

		return $found;
	}

	/**
	 * Images in <img> tags: the attachment id from the wp-image-{id} class, otherwise the src.
	 *
	 * @param string $html HTML.
	 * @return list<int|string>
	 */
	private function image_tags( string $html ): array {
		$found = array();

		if ( ! preg_match_all( '/<img\b[^>]*>/i', $html, $tags ) ) {
			return $found;
		}

		foreach ( $tags[0] as $tag ) {
			if ( preg_match( '/\bwp-image-(\d+)\b/', $tag, $id ) ) {
				$found[] = (int) $id[1];
			} elseif ( preg_match( '/\bsrc=(["\'])(.*?)\1/i', $tag, $src ) && 0 !== strpos( $src[2], 'data:' ) ) {
				$found[] = html_entity_decode( $src[2], ENT_QUOTES );
			}
		}

		return $found;
	}

	/**
	 * Images of [gallery] shortcodes; a shortcode without ids shows the attached images.
	 *
	 * @param WP_Post $post Post.
	 * @return list<int|string>
	 */
	private function gallery_shortcodes( WP_Post $post ): array {
		$content = (string) $post->post_content;
		$found   = array();

		if ( ! has_shortcode( $content, 'gallery' ) || ! preg_match_all( '/' . get_shortcode_regex( array( 'gallery' ) ) . '/', $content, $shortcodes, PREG_SET_ORDER ) ) {
			return $found;
		}

		foreach ( $shortcodes as $shortcode ) {
			$atts = (array) shortcode_parse_atts( $shortcode[3] );
			$ids  = (string) ( $atts['ids'] ?? $atts['include'] ?? '' );

			if ( '' === $ids ) {
				foreach ( array_keys( get_attached_media( 'image', $post ) ) as $id ) {
					$found[] = (int) $id;
				}
				continue;
			}

			foreach ( explode( ',', $ids ) as $id ) {
				if ( (int) $id > 0 ) {
					$found[] = (int) $id;
				}
			}
		}

		return $found;
	}

	/**
	 * Visit blocks depth first; the visitor returns false to skip a block's children.
	 *
	 * @param array<int, array<string, mixed>> $blocks  Parsed blocks.
	 * @param callable                         $visitor Receives each block.
	 */
	private function walk( array $blocks, callable $visitor ): void {
		foreach ( $blocks as $block ) {
			if ( empty( $block['blockName'] ) ) {
				continue;
			}

			if ( false !== $visitor( $block ) && ! empty( $block['innerBlocks'] ) ) {
				$this->walk( (array) $block['innerBlocks'], $visitor );
			}
		}
	}
}
