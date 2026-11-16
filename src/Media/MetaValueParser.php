<?php
/**
 * Reads media references from custom field values.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Media;

use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Turns any common custom field format into attachment ids and URLs.
 *
 * Understands attachment ids ("12"), id lists ("12,34" as WooCommerce stores
 * galleries), URLs and paths, JSON, serialized arrays, ACF image/gallery/file
 * values (ids or arrays with "ID"/"url"), arrays of any of these and HTML
 * with <img>/<video> tags.
 */
final class MetaValueParser {

	/**
	 * Media references found in a value, in order.
	 *
	 * @param mixed $value Meta value.
	 * @param int   $depth Recursion depth.
	 * @return list<int|string> Attachment ids and URLs/paths.
	 */
	public function parse( $value, int $depth = 0 ): array {
		if ( $depth > 5 || null === $value || false === $value || '' === $value ) {
			return array();
		}

		if ( $value instanceof WP_Post ) {
			return array( (int) $value->ID );
		}

		if ( is_int( $value ) || is_float( $value ) ) {
			return $value > 0 ? array( (int) $value ) : array();
		}

		if ( is_array( $value ) ) {
			return $this->parse_array( $value, $depth );
		}

		if ( is_object( $value ) ) {
			return $this->parse_array( get_object_vars( $value ), $depth );
		}

		return is_string( $value ) ? $this->parse_string( $value, $depth ) : array();
	}

	/**
	 * Parse an array value.
	 *
	 * @param array<mixed> $value Value.
	 * @param int          $depth Recursion depth.
	 * @return list<int|string>
	 */
	private function parse_array( array $value, int $depth ): array {
		foreach ( array( 'ID', 'id', 'attachment_id' ) as $key ) {
			if ( isset( $value[ $key ] ) && is_numeric( $value[ $key ] ) && (int) $value[ $key ] > 0 ) {
				return array( (int) $value[ $key ] );
			}
		}

		foreach ( array( 'url', 'src', 'guid', 'link', 'file' ) as $key ) {
			if ( isset( $value[ $key ] ) && is_string( $value[ $key ] ) && '' !== $value[ $key ] ) {
				return $this->parse_string( $value[ $key ], $depth + 1 );
			}
		}

		$found = array();

		foreach ( $value as $item ) {
			foreach ( $this->parse( $item, $depth + 1 ) as $reference ) {
				$found[] = $reference;
			}
		}

		return $found;
	}

	/**
	 * Parse a string value.
	 *
	 * @param string $value Value.
	 * @param int    $depth Recursion depth.
	 * @return list<int|string>
	 */
	private function parse_string( string $value, int $depth ): array {
		$value = trim( $value );

		if ( '' === $value ) {
			return array();
		}

		if ( ctype_digit( $value ) ) {
			return (int) $value > 0 ? array( (int) $value ) : array();
		}

		if ( preg_match( '/^\d+(\s*[,;|]\s*\d+)+$/', $value ) ) {
			return array_values( array_filter( array_map( 'intval', (array) preg_split( '/\s*[,;|]\s*/', $value ) ) ) );
		}

		if ( is_serialized( $value ) ) {
			return $this->parse( maybe_unserialize( $value ), $depth + 1 );
		}

		if ( 0 === strpos( $value, '[' ) || 0 === strpos( $value, '{' ) ) {
			$decoded = json_decode( $value, true );

			if ( is_array( $decoded ) ) {
				return $this->parse_array( $decoded, $depth + 1 );
			}
		}

		if ( false !== strpos( $value, '<' ) ) {
			return $this->parse_html( $value );
		}

		if ( preg_match_all( '#(?:https?:)?//[^\s,;"\'<>]+#i', $value, $matches ) && count( $matches[0] ) > 1 ) {
			return $matches[0];
		}

		return array( $value );
	}

	/**
	 * Media in an HTML fragment.
	 *
	 * @param string $html HTML.
	 * @return list<int|string>
	 */
	private function parse_html( string $html ): array {
		$found = array();

		if ( preg_match_all( '/<(?:img|video|source)\b[^>]*>/i', $html, $tags ) ) {
			foreach ( $tags[0] as $tag ) {
				if ( preg_match( '/\bwp-image-(\d+)\b/', $tag, $id ) ) {
					$found[] = (int) $id[1];
				} elseif ( preg_match( '/\bsrc=(["\'])(.*?)\1/i', $tag, $src ) ) {
					$found[] = html_entity_decode( $src[2], ENT_QUOTES );
				}
			}
		}

		return $found;
	}
}
