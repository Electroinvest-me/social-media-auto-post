<?php
/**
 * Error returned by the Advert4 API.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Api;

use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * A failed Advert4 API call, with the HTTP status and validation messages.
 */
final class ApiException extends RuntimeException {

	/**
	 * HTTP status (0 when the request did not complete).
	 *
	 * @var int
	 */
	private int $status;

	/**
	 * Validation messages.
	 *
	 * @var list<string>
	 */
	private array $errors;

	/**
	 * Create the exception.
	 *
	 * @param string       $message Readable message.
	 * @param int          $status  HTTP status (0 when the request did not complete).
	 * @param list<string> $errors  Validation messages.
	 */
	public function __construct( string $message, int $status = 0, array $errors = array() ) {
		parent::__construct( $message, $status );

		$this->status = $status;
		$this->errors = $errors;
	}

	/**
	 * No API key was configured.
	 */
	public static function not_configured(): self {
		return new self( __( 'Add your Advert4 API key on the Social Media Auto Post settings screen.', 'social-media-auto-post' ), 401 );
	}

	/**
	 * The request did not reach Advert4 or timed out.
	 *
	 * @param string $detail Transport error.
	 */
	public static function network( string $detail ): self {
		/* translators: %s: error returned by the HTTP library */
		return new self( sprintf( __( 'Could not reach Advert4: %s', 'social-media-auto-post' ), $detail ), 0 );
	}

	/**
	 * Build from an error response.
	 *
	 * @param int   $status HTTP status.
	 * @param mixed $body   Decoded JSON body.
	 */
	public static function from_response( int $status, $body ): self {
		$errors = array();

		if ( is_array( $body ) && isset( $body['errors'] ) && is_array( $body['errors'] ) ) {
			array_walk_recursive(
				$body['errors'],
				static function ( $message ) use ( &$errors ): void {
					if ( is_string( $message ) && '' !== $message ) {
						$errors[] = $message;
					}
				}
			);
		}

		$message = is_array( $body ) && isset( $body['message'] ) && is_string( $body['message'] ) && '' !== $body['message']
			? $body['message']
			: self::default_message( $status );

		return new self( $message, $status, array_values( array_unique( $errors ) ) );
	}

	/**
	 * HTTP status (0 for network errors).
	 */
	public function status(): int {
		return $this->status;
	}

	/**
	 * Validation messages.
	 *
	 * @return list<string>
	 */
	public function errors(): array {
		return $this->errors;
	}

	/**
	 * Whether trying again later may succeed.
	 */
	public function is_retryable(): bool {
		return 0 === $this->status || 408 === $this->status || 425 === $this->status || 429 === $this->status || $this->status >= 500;
	}

	/**
	 * Message for a status without a JSON body.
	 *
	 * @param int $status HTTP status.
	 */
	private static function default_message( int $status ): string {
		if ( 401 === $status ) {
			return __( 'The Advert4 API key is not valid or was revoked.', 'social-media-auto-post' );
		}

		if ( 402 === $status || 403 === $status ) {
			return __( 'This Advert4 workspace cannot use the API. Check its plan and the key permissions.', 'social-media-auto-post' );
		}

		if ( 404 === $status ) {
			return __( 'Not found in Advert4.', 'social-media-auto-post' );
		}

		if ( 429 === $status ) {
			return __( 'Too many requests to Advert4. The post will be sent again shortly.', 'social-media-auto-post' );
		}

		if ( $status >= 500 ) {
			return __( 'Advert4 is temporarily unavailable.', 'social-media-auto-post' );
		}

		/* translators: %d: HTTP status code */
		return sprintf( __( 'Advert4 returned HTTP %d.', 'social-media-auto-post' ), $status );
	}
}
