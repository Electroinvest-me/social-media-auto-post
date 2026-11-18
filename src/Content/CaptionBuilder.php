<?php
/**
 * Builds the caption from a template.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Content;

use WP_Post;
use WP_Term;

defined( 'ABSPATH' ) || exit;

/**
 * Replaces placeholders such as {title} and {url} with plain text from the post.
 */
final class CaptionBuilder {

	/**
	 * Placeholders and what they insert, for the help text.
	 *
	 * @return array<string, string>
	 */
	public static function placeholders(): array {
		return array(
			'{title}'           => __( 'Post title', 'social-media-auto-post' ),
			'{excerpt}'         => __( 'Excerpt, or the first 55 words of the content', 'social-media-auto-post' ),
			'{content}'         => __( 'Full content as plain text', 'social-media-auto-post' ),
			'{url}'             => __( 'Permalink', 'social-media-auto-post' ),
			'{shortlink}'       => __( 'Short link', 'social-media-auto-post' ),
			'{site_name}'       => __( 'Site title', 'social-media-auto-post' ),
			'{author}'          => __( 'Author display name', 'social-media-auto-post' ),
			'{date}'            => __( 'Publish date', 'social-media-auto-post' ),
			'{categories}'      => __( 'Categories, comma separated', 'social-media-auto-post' ),
			'{tags}'            => __( 'Tags, comma separated', 'social-media-auto-post' ),
			'{hashtags}'        => __( 'Tags as #hashtags', 'social-media-auto-post' ),
			'{terms:taxonomy}'  => __( 'Terms of any taxonomy, e.g. {terms:product_cat}', 'social-media-auto-post' ),
			'{meta:field_name}' => __( 'Value of a custom field, e.g. {meta:_price}', 'social-media-auto-post' ),
		);
	}

	/**
	 * Render a template for a post.
	 *
	 * @param WP_Post $post     Post.
	 * @param string  $template Template with placeholders.
	 * @param int     $limit    Maximum length (0 = no limit).
	 */
	public function build( WP_Post $post, string $template, int $limit = 0 ): string {
		$template = str_replace( array( "\r\n", "\r" ), "\n", $template );
		$caption  = (string) preg_replace_callback(
			'/\{(meta|terms):([^}\s]+)\}|\{([a-z_]+)\}/',
			function ( array $found ) use ( $post ): string {
				return '' !== $found[1] ? $this->dynamic( $post, $found[1], $found[2] ) : $this->placeholder( $post, $found[3], $found[0] );
			},
			$template
		);

		$caption = (string) preg_replace( "/[ \t]+\n/", "\n", $caption );
		$caption = trim( (string) preg_replace( "/\n{3,}/", "\n\n", $caption ) );

		/**
		 * Filter the caption sent to the networks.
		 *
		 * @param string  $caption  Caption.
		 * @param WP_Post $post     Post.
		 * @param string  $template Template it was built from.
		 */
		$caption = (string) apply_filters( 'a4smap_caption', $caption, $post, $template );

		return $limit > 0 ? $this->truncate( $caption, $limit ) : $caption;
	}

	/**
	 * Value of a fixed placeholder.
	 *
	 * @param WP_Post $post     Post.
	 * @param string  $name     Placeholder name.
	 * @param string  $original Original text, kept when the placeholder is unknown.
	 */
	private function placeholder( WP_Post $post, string $name, string $original ): string {
		switch ( $name ) {
			case 'title':
				return $this->plain( get_the_title( $post ) );
			case 'excerpt':
				return $this->excerpt( $post );
			case 'content':
				return $this->plain( (string) $post->post_content, true );
			case 'url':
				return (string) get_permalink( $post );
			case 'shortlink':
				$shortlink = wp_get_shortlink( $post->ID );

				return '' !== $shortlink ? $shortlink : (string) get_permalink( $post );
			case 'site_name':
				return $this->plain( get_bloginfo( 'name' ) );
			case 'author':
				return $this->plain( (string) get_the_author_meta( 'display_name', (int) $post->post_author ) );
			case 'date':
				return (string) get_the_date( '', $post );
			case 'categories':
				return implode( ', ', $this->term_names( $post, 'category' ) );
			case 'tags':
				return implode( ', ', $this->term_names( $post, 'post_tag' ) );
			case 'hashtags':
				return $this->hashtags( $post );
			default:
				return $original;
		}
	}

	/**
	 * Value of {meta:key} or {terms:taxonomy}.
	 *
	 * @param WP_Post $post Post.
	 * @param string  $type "meta" or "terms".
	 * @param string  $key  Meta key or taxonomy.
	 */
	private function dynamic( WP_Post $post, string $type, string $key ): string {
		if ( 'terms' === $type ) {
			return implode( ', ', $this->term_names( $post, $key ) );
		}

		$value = get_post_meta( $post->ID, $key, true );

		return is_scalar( $value ) ? $this->plain( (string) $value ) : '';
	}

	/**
	 * Manual excerpt, or the beginning of the content.
	 *
	 * @param WP_Post $post Post.
	 */
	private function excerpt( WP_Post $post ): string {
		if ( '' !== trim( (string) $post->post_excerpt ) ) {
			return $this->plain( (string) $post->post_excerpt );
		}

		$words = (int) apply_filters( 'a4smap_excerpt_length', 55 );

		return wp_trim_words( $this->plain( (string) $post->post_content, true ), $words, '…' );
	}

	/**
	 * Names of the terms of a taxonomy.
	 *
	 * @param WP_Post $post     Post.
	 * @param string  $taxonomy Taxonomy.
	 * @return list<string>
	 */
	private function term_names( WP_Post $post, string $taxonomy ): array {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return array();
		}

		$terms = get_the_terms( $post, $taxonomy );

		if ( ! is_array( $terms ) ) {
			return array();
		}

		return array_values(
			array_map(
				fn ( WP_Term $term ): string => $this->plain( $term->name ),
				array_filter( $terms, static fn ( $term ): bool => $term instanceof WP_Term && 'uncategorized' !== $term->slug )
			)
		);
	}

	/**
	 * Tags as hashtags ("Summer sale" becomes #SummerSale).
	 *
	 * @param WP_Post $post Post.
	 */
	private function hashtags( WP_Post $post ): string {
		$tags = array();

		foreach ( $this->term_names( $post, 'post_tag' ) as $name ) {
			$words = preg_split( '/[^\p{L}\p{N}_]+/u', $name, -1, PREG_SPLIT_NO_EMPTY );
			$tag   = implode( '', array_map( array( $this, 'capitalize' ), (array) $words ) );

			if ( '' !== $tag ) {
				$tags[ '#' . $tag ] = true;
			}
		}

		return implode( ' ', array_keys( $tags ) );
	}

	/**
	 * Upper-case the first letter of a word (multibyte safe when mbstring is available).
	 *
	 * @param string $word Word.
	 */
	private function capitalize( string $word ): string {
		$first = mb_substr( $word, 0, 1 );

		return ( function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $first ) : strtoupper( $first ) ) . mb_substr( $word, 1 );
	}

	/**
	 * Plain text from HTML: blocks, shortcodes and tags removed, entities decoded.
	 *
	 * @param string $html             HTML.
	 * @param bool   $keep_paragraphs Keep line breaks between paragraphs.
	 */
	private function plain( string $html, bool $keep_paragraphs = false ): string {
		$text = strip_shortcodes( $html );

		if ( $keep_paragraphs ) {
			$text = (string) preg_replace( '#<br\s*/?>#i', "\n", $text );
			$text = (string) preg_replace( '#</(p|div|h[1-6]|li|blockquote|figure)>#i', "$0\n\n", $text );
		}

		$text = html_entity_decode( wp_strip_all_tags( $text, ! $keep_paragraphs ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		if ( ! $keep_paragraphs ) {
			return trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
		}

		$text = (string) preg_replace( "/[ \t]+/", ' ', $text );
		$text = (string) preg_replace( "/ *\n */", "\n", $text );

		return trim( (string) preg_replace( "/\n{3,}/", "\n\n", $text ) );
	}

	/**
	 * Shorten to a limit at a word boundary.
	 *
	 * @param string $caption Caption.
	 * @param int    $limit   Maximum characters.
	 */
	private function truncate( string $caption, int $limit ): string {
		if ( mb_strlen( $caption ) <= $limit ) {
			return $caption;
		}

		$cut   = mb_substr( $caption, 0, $limit - 1 );
		$space = function_exists( 'mb_strrpos' ) ? mb_strrpos( $cut, ' ' ) : strrpos( $cut, ' ' );

		if ( false !== $space && $space > $limit * 0.8 ) {
			$cut = mb_substr( $cut, 0, $space );
		}

		return rtrim( $cut ) . '…';
	}
}
