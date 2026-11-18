<?php
/**
 * Editor meta box.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Admin;

use Advert4\SocialMediaAutoPost\Contracts\Hookable;
use Advert4\SocialMediaAutoPost\Settings\Settings;
use Advert4\SocialMediaAutoPost\Sharing\AutoShare;
use Advert4\SocialMediaAutoPost\Sharing\PayloadBuilder;
use Advert4\SocialMediaAutoPost\Sharing\PostOverrides;
use Advert4\SocialMediaAutoPost\Sharing\ShareDecision;
use Advert4\SocialMediaAutoPost\Sharing\ShareState;
use Advert4\SocialMediaAutoPost\Support\View;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Sidebar box to share a post, override the defaults and see the result per network.
 */
final class MetaBox implements Hookable {

	public const ID    = 'a4smap-share';
	public const FIELD = 'a4smap';
	public const NONCE = 'a4smap_nonce';

	/**
	 * Plugin settings.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Share rules.
	 *
	 * @var ShareDecision
	 */
	private ShareDecision $decision;

	/**
	 * Request builder.
	 *
	 * @var PayloadBuilder
	 */
	private PayloadBuilder $builder;

	/**
	 * Auto sharing.
	 *
	 * @var AutoShare
	 */
	private AutoShare $auto;

	/**
	 * Create the meta box.
	 *
	 * @param Settings       $settings Plugin settings.
	 * @param ShareDecision  $decision Share rules.
	 * @param PayloadBuilder $builder  Request builder.
	 * @param AutoShare      $auto     Auto sharing.
	 */
	public function __construct( Settings $settings, ShareDecision $decision, PayloadBuilder $builder, AutoShare $auto ) {
		$this->settings = $settings;
		$this->decision = $decision;
		$this->builder  = $builder;
		$this->auto     = $auto;
	}

	/**
	 * Attach to the editor.
	 */
	public function register(): void {
		add_action( 'add_meta_boxes', array( $this, 'add' ), 10, 2 );
		add_action( 'save_post', array( $this, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Nonce action of a post.
	 *
	 * @param int $post_id Post id.
	 */
	public static function nonce_action( int $post_id ): string {
		return 'a4smap_post_' . $post_id;
	}

	/**
	 * Add the box to enabled post types.
	 *
	 * @param string       $post_type Post type.
	 * @param WP_Post|null $post      Post.
	 */
	public function add( string $post_type, $post = null ): void {
		if ( ! $post instanceof WP_Post || ! $this->settings->is_enabled( $post_type ) ) {
			return;
		}

		add_meta_box(
			self::ID,
			__( 'Social Media Auto Post', 'social-media-auto-post' ),
			array( $this, 'render' ),
			$post_type,
			'side',
			'default',
			array( '__back_compat_meta_box' => false )
		);
	}

	/**
	 * Load the assets in the editor.
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public function enqueue( string $hook_suffix ): void {
		if ( 'post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix ) {
			return;
		}

		$screen = get_current_screen();

		if ( null === $screen || ! $this->settings->is_enabled( (string) $screen->post_type ) ) {
			return;
		}

		wp_enqueue_media();
		Assets::enqueue();
	}

	/**
	 * Print the box.
	 *
	 * @param WP_Post $post Post.
	 */
	public function render( WP_Post $post ): void {
		$config = $this->settings->post_type( $post->post_type );
		$plan   = $this->builder->build( $post );

		View::render(
			'metabox/box',
			array(
				'post'              => $post,
				'config'            => $config,
				'overrides'         => PostOverrides::for_post( $post->ID ),
				'state'             => ShareState::for_post( $post->ID ),
				'share_default'     => $this->decision->checkbox_default( $post ),
				'can_share'         => $this->decision->can_share( $post ),
				'default_platforms' => $this->builder->default_platforms( $config ),
				'plan'              => $plan,
				'networks'          => $this->builder->plan_networks( $plan ),
				'profile_name'      => $this->builder->profile_name( $plan->profile ),
				'has_api_key'       => $this->settings->has_api_key(),
				'nonce'             => wp_create_nonce( self::nonce_action( $post->ID ) ),
			)
		);
	}

	/**
	 * Save the meta box fields, then send or cancel the post if needed.
	 *
	 * @param int     $post_id Post id.
	 * @param WP_Post $post    Post.
	 */
	public function save( int $post_id, WP_Post $post ): void {
		$nonce = isset( $_POST[ self::NONCE ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ) : '';

		if ( '' === $nonce || ! wp_verify_nonce( $nonce, self::nonce_action( $post_id ) ) ) {
			return;
		}

		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! $this->settings->is_enabled( $post->post_type ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized field by field in PostOverrides::from_request().
		$input = isset( $_POST[ self::FIELD ] ) && is_array( $_POST[ self::FIELD ] ) ? wp_unslash( $_POST[ self::FIELD ] ) : array();

		$this->save_overrides( $post_id, $post, (array) $input );
		$this->auto->after_editor_save( $post );
	}

	/**
	 * Store the submitted overrides.
	 *
	 * @param int                  $post_id Post id.
	 * @param WP_Post              $post    Post.
	 * @param array<string, mixed> $input   Unslashed fields.
	 */
	public function save_overrides( int $post_id, WP_Post $post, array $input ): PostOverrides {
		$config    = $this->settings->post_type( $post->post_type );
		$overrides = PostOverrides::from_request( $input, $this->builder->default_platforms( $config ) );
		$overrides->save( $post_id );

		return $overrides;
	}
}
