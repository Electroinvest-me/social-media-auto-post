<?php
/**
 * Sharing status of one post.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Sharing;

use Advert4\SocialMediaAutoPost\Support\Site;

defined( 'ABSPATH' ) || exit;

/**
 * Where a post is in the sharing process and what Advert4 reported per network.
 *
 * Local statuses: queued, sending, error. Every other status is the Advert4
 * post status (draft, scheduled, publishing, published, partial, failed, cancelled).
 *
 * @phpstan-type Target array{network: string, account: string, status: string, permalink: string, error: string}
 */
final class ShareState {

	public const META_KEY  = '_a4smap_state';
	public const COUNT_KEY = '_a4smap_shares';

	public const QUEUED  = 'queued';
	public const SENDING = 'sending';
	public const ERROR   = 'error';

	private const REMOTE_PENDING = array( 'scheduled', 'publishing' );
	private const REMOTE_DONE    = array( 'published', 'partial' );

	/**
	 * Status.
	 *
	 * @var string
	 */
	public string $status = '';

	/**
	 * Advert4 post UUID.
	 *
	 * @var string
	 */
	public string $uuid = '';

	/**
	 * Idempotency key sent as external_id.
	 *
	 * @var string
	 */
	public string $external_id = '';

	/**
	 * Last error or note.
	 *
	 * @var string
	 */
	public string $message = '';

	/**
	 * Validation messages.
	 *
	 * @var list<string>
	 */
	public array $errors = array();

	/**
	 * Result per network.
	 *
	 * @var list<Target>
	 */
	public array $targets = array();

	/**
	 * Send attempts for the current share.
	 *
	 * @var int
	 */
	public int $attempts = 0;

	/**
	 * Status checks since the post was sent.
	 *
	 * @var int
	 */
	public int $polls = 0;

	/**
	 * When the post was accepted by Advert4.
	 *
	 * @var int
	 */
	public int $shared_at = 0;

	/**
	 * Last change.
	 *
	 * @var int
	 */
	public int $updated_at = 0;

	/**
	 * Post id.
	 *
	 * @var int
	 */
	public int $post_id;

	/**
	 * Create an empty state for a post.
	 *
	 * @param int $post_id Post id.
	 */
	public function __construct( int $post_id ) {
		$this->post_id = $post_id;
	}

	/**
	 * State saved on a post.
	 *
	 * @param int $post_id Post id.
	 */
	public static function for_post( int $post_id ): self {
		$state = new self( $post_id );
		$data  = get_post_meta( $post_id, self::META_KEY, true );

		if ( ! is_array( $data ) ) {
			return $state;
		}

		$state->status      = (string) ( $data['status'] ?? '' );
		$state->uuid        = (string) ( $data['uuid'] ?? '' );
		$state->external_id = (string) ( $data['external_id'] ?? '' );
		$state->message     = (string) ( $data['message'] ?? '' );
		$state->errors      = array_values( array_map( 'strval', (array) ( $data['errors'] ?? array() ) ) );
		$state->targets     = array_values( array_filter( (array) ( $data['targets'] ?? array() ), 'is_array' ) );
		$state->attempts    = (int) ( $data['attempts'] ?? 0 );
		$state->polls       = (int) ( $data['polls'] ?? 0 );
		$state->shared_at   = (int) ( $data['shared_at'] ?? 0 );
		$state->updated_at  = (int) ( $data['updated_at'] ?? 0 );

		return $state;
	}

	/**
	 * Store on the post.
	 */
	public function save(): void {
		$this->updated_at = time();

		update_post_meta(
			$this->post_id,
			self::META_KEY,
			array(
				'status'      => $this->status,
				'uuid'        => $this->uuid,
				'external_id' => $this->external_id,
				'message'     => $this->message,
				'errors'      => $this->errors,
				'targets'     => $this->targets,
				'attempts'    => $this->attempts,
				'polls'       => $this->polls,
				'shared_at'   => $this->shared_at,
				'updated_at'  => $this->updated_at,
			)
		);
	}

	/**
	 * Remove from the post (the post counts as never shared again).
	 */
	public function delete(): void {
		delete_post_meta( $this->post_id, self::META_KEY );
	}

	/**
	 * Start a new share with a fresh idempotency key.
	 */
	public function start_new_share(): void {
		$count = (int) get_post_meta( $this->post_id, self::COUNT_KEY, true ) + 1;
		update_post_meta( $this->post_id, self::COUNT_KEY, $count );

		$this->status      = self::QUEUED;
		$this->uuid        = '';
		$this->external_id = sprintf( 'wp-%s-%d-%d', Site::key(), $this->post_id, $count );
		$this->message     = '';
		$this->errors      = array();
		$this->targets     = array();
		$this->attempts    = 0;
		$this->polls       = 0;
		$this->shared_at   = 0;
	}

	/**
	 * Waiting in the queue.
	 *
	 * @param string $message Optional note (e.g. the retry reason).
	 */
	public function mark_queued( string $message = '' ): void {
		if ( '' === $this->external_id ) {
			$this->start_new_share();
		}

		$this->status  = self::QUEUED;
		$this->message = $message;
	}

	/**
	 * Being sent right now.
	 */
	public function mark_sending(): void {
		$this->status  = self::SENDING;
		$this->message = '';
		$this->errors  = array();
		++$this->attempts;
	}

	/**
	 * Could not be sent.
	 *
	 * @param string       $message Reason.
	 * @param list<string> $errors  Details.
	 */
	public function mark_error( string $message, array $errors = array() ): void {
		$this->status  = self::ERROR;
		$this->message = $message;
		$this->errors  = $errors;
	}

	/**
	 * Copy the status of an Advert4 post (API resource).
	 *
	 * @param array<string, mixed> $post Advert4 post.
	 */
	public function apply_remote( array $post ): void {
		$this->uuid    = (string) ( $post['uuid'] ?? $this->uuid );
		$this->status  = (string) ( $post['status'] ?? $this->status );
		$this->targets = array();

		foreach ( (array) ( $post['targets'] ?? array() ) as $target ) {
			if ( ! is_array( $target ) ) {
				continue;
			}

			$account = is_array( $target['account'] ?? null ) ? $target['account'] : array();

			$this->targets[] = array(
				'network'   => (string) ( $account['provider'] ?? '' ),
				'account'   => (string) ( $account['name'] ?? $account['username'] ?? '' ),
				'status'    => (string) ( $target['status'] ?? '' ),
				'permalink' => (string) ( $target['permalink'] ?? '' ),
				'error'     => (string) ( $target['error'] ?? '' ),
			);
		}

		if ( 'failed' !== $this->status ) {
			$this->message = '';
			$this->errors  = array();
		}
	}

	/**
	 * Never queued or sent.
	 */
	public function is_empty(): bool {
		return '' === $this->status;
	}

	/**
	 * Waiting in the queue.
	 */
	public function is_queued(): bool {
		return self::QUEUED === $this->status;
	}

	/**
	 * Accepted by Advert4 (it has an Advert4 post).
	 */
	public function has_remote(): bool {
		return '' !== $this->uuid;
	}

	/**
	 * Queued, being sent, or still publishing in Advert4.
	 */
	public function is_in_progress(): bool {
		return in_array( $this->status, array( self::QUEUED, self::SENDING ), true ) || $this->is_remote_pending();
	}

	/**
	 * Advert4 is still publishing.
	 */
	public function is_remote_pending(): bool {
		return $this->has_remote() && in_array( $this->status, self::REMOTE_PENDING, true );
	}

	/**
	 * Live on at least one network.
	 */
	public function is_published(): bool {
		return in_array( $this->status, self::REMOTE_DONE, true );
	}

	/**
	 * Ended without being published.
	 */
	public function is_failed(): bool {
		return in_array( $this->status, array( self::ERROR, 'failed', 'cancelled' ), true );
	}

	/**
	 * Readable status.
	 */
	public function label(): string {
		$labels = array(
			''            => __( 'Not shared', 'social-media-auto-post' ),
			self::QUEUED  => __( 'Queued', 'social-media-auto-post' ),
			self::SENDING => __( 'Sending…', 'social-media-auto-post' ),
			self::ERROR   => __( 'Not sent', 'social-media-auto-post' ),
			'draft'       => __( 'Draft in Advert4', 'social-media-auto-post' ),
			'scheduled'   => __( 'Scheduled', 'social-media-auto-post' ),
			'publishing'  => __( 'Publishing…', 'social-media-auto-post' ),
			'published'   => __( 'Published', 'social-media-auto-post' ),
			'partial'     => __( 'Partly published', 'social-media-auto-post' ),
			'failed'      => __( 'Failed', 'social-media-auto-post' ),
			'cancelled'   => __( 'Cancelled', 'social-media-auto-post' ),
		);

		return $labels[ $this->status ] ?? ucfirst( $this->status );
	}

	/**
	 * Readable status of one network.
	 *
	 * @param string $status Target status.
	 */
	public static function target_label( string $status ): string {
		$labels = array(
			'pending'    => __( 'Waiting', 'social-media-auto-post' ),
			'processing' => __( 'Publishing…', 'social-media-auto-post' ),
			'published'  => __( 'Published', 'social-media-auto-post' ),
			'failed'     => __( 'Failed', 'social-media-auto-post' ),
			'cancelled'  => __( 'Cancelled', 'social-media-auto-post' ),
		);

		return $labels[ $status ] ?? ucfirst( $status );
	}

	/**
	 * Colour group for the badge: neutral, progress, success, warning or error.
	 */
	public function tone(): string {
		if ( 'partial' === $this->status ) {
			return 'warning';
		}

		if ( $this->is_published() ) {
			return 'success';
		}

		if ( $this->is_failed() ) {
			return 'error';
		}

		return $this->is_in_progress() ? 'progress' : 'neutral';
	}
}
