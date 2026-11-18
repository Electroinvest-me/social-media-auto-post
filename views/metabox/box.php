<?php
/**
 * Editor meta box.
 *
 * @package Advert4\SocialMediaAutoPost
 *
 * @var \WP_Post                                              $post
 * @var \Advert4\SocialMediaAutoPost\Settings\PostTypeConfig $config
 * @var \Advert4\SocialMediaAutoPost\Sharing\PostOverrides   $overrides
 * @var \Advert4\SocialMediaAutoPost\Sharing\ShareState      $state
 * @var bool                                                  $share_default
 * @var bool                                                  $can_share
 * @var list<string>                                          $default_platforms
 * @var \Advert4\SocialMediaAutoPost\Sharing\SharePlan       $plan
 * @var list<string>                                          $networks
 * @var string                                                $profile_name
 * @var bool                                                  $has_api_key
 * @var string                                                $nonce
 */

use Advert4\SocialMediaAutoPost\Admin\Icons;
use Advert4\SocialMediaAutoPost\Admin\MetaBox;
use Advert4\SocialMediaAutoPost\Admin\SettingsPage;
use Advert4\SocialMediaAutoPost\Enums\Format;
use Advert4\SocialMediaAutoPost\Enums\ImageSource;
use Advert4\SocialMediaAutoPost\Enums\Mode;
use Advert4\SocialMediaAutoPost\Enums\Network;
use Advert4\SocialMediaAutoPost\Support\View;

defined( 'ABSPATH' ) || exit;

$field            = static function ( string $key ): string {
	return MetaBox::FIELD . '[' . $key . ']';
};
$chosen_platforms = $overrides->platforms ?? $default_platforms;
$is_published     = 'publish' === $post->post_status;
$custom_media     = array();

foreach ( $overrides->media_ids as $media_id ) {
	$thumbnail = wp_get_attachment_image_url( $media_id, 'thumbnail', true );

	if ( is_string( $thumbnail ) ) {
		$custom_media[ $media_id ] = $thumbnail;
	}
}

$format_icons = array(
	''              => 'layers',
	Format::AUTO    => 'sparkles',
	Format::IMAGE   => 'image',
	Format::GALLERY => 'images',
	Format::VIDEO   => 'clapperboard',
	Format::STORY   => 'smartphone',
);
$format_names = array(
	''              => __( 'Default', 'social-media-auto-post' ),
	Format::AUTO    => __( 'Auto', 'social-media-auto-post' ),
	Format::IMAGE   => __( 'Image', 'social-media-auto-post' ),
	Format::GALLERY => __( 'Gallery', 'social-media-auto-post' ),
	Format::VIDEO   => __( 'Reel', 'social-media-auto-post' ),
	Format::STORY   => __( 'Story', 'social-media-auto-post' ),
);
$mode_names   = array(
	''            => __( 'Default', 'social-media-auto-post' ),
	Mode::PUBLISH => __( 'Publish', 'social-media-auto-post' ),
	Mode::DRAFT   => __( 'Draft', 'social-media-auto-post' ),
);
?>
<div class="a4smap-box" data-a4smap-box data-a4smap-scope data-post="<?php echo esc_attr( (string) $post->ID ); ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>">
	<input type="hidden" name="<?php echo esc_attr( MetaBox::NONCE ); ?>" value="<?php echo esc_attr( $nonce ); ?>" />

	<?php if ( ! $has_api_key ) : ?>
		<p class="a4smap-note a4smap-note--warning">
			<?php Icons::render( 'plug', 15 ); ?>
			<span>
				<?php esc_html_e( 'Advert4 is not connected yet.', 'social-media-auto-post' ); ?>
				<?php if ( current_user_can( 'manage_options' ) ) : ?>
					<a href="<?php echo esc_url( SettingsPage::url( 'connection' ) ); ?>"><?php esc_html_e( 'Connect now', 'social-media-auto-post' ); ?></a>
				<?php endif; ?>
			</span>
		</p>
	<?php endif; ?>

	<div data-a4smap-status>
		<?php
		View::render(
			'metabox/status',
			array(
				'post'      => $post,
				'state'     => $state,
				'can_share' => $can_share,
			)
		);
		?>
	</div>

	<input type="hidden" name="<?php echo esc_attr( $field( 'share_field' ) ); ?>" value="1" />
	<?php if ( $state->is_empty() ) : ?>
		<div class="a4smap-share" data-a4smap-share-toggle>
			<label class="a4smap-switch">
				<input type="checkbox" role="switch" name="<?php echo esc_attr( $field( 'share' ) ); ?>" value="1" <?php checked( $share_default ); ?> />
				<span class="a4smap-switch__track" aria-hidden="true"></span>
				<span class="a4smap-switch__text">
					<strong><?php esc_html_e( 'Share to social media', 'social-media-auto-post' ); ?></strong>
					<span><?php echo esc_html( $is_published ? __( 'When you update this post', 'social-media-auto-post' ) : __( 'As soon as it is published', 'social-media-auto-post' ) ); ?></span>
				</span>
			</label>
			<?php if ( array() !== $networks ) : ?>
				<div class="a4smap-nets">
					<?php foreach ( $networks as $network ) : ?>
						<?php Icons::render_network( $network, 20, true ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	<?php else : ?>
		<input type="hidden" name="<?php echo esc_attr( $field( 'share' ) ); ?>" value="<?php echo esc_attr( $share_default ? '1' : '' ); ?>" />
	<?php endif; ?>

	<details class="a4smap-disclosure" data-a4smap-preview-wrap>
		<summary><?php esc_html_e( 'Preview', 'social-media-auto-post' ); ?><?php Icons::render( 'chevron-down', 16 ); ?></summary>
		<div class="a4smap-disclosure__body">
			<div data-a4smap-preview>
				<?php
				View::render(
					'partials/social-preview',
					array(
						'plan'         => $plan,
						'networks'     => $networks,
						'profile_name' => $profile_name,
						'source_post'  => null,
					)
				);
				?>
			</div>
			<p class="a4smap-hint"><?php esc_html_e( 'Uses the last saved version of the post.', 'social-media-auto-post' ); ?></p>
		</div>
	</details>

	<details class="a4smap-disclosure" <?php echo $overrides->is_customized() ? 'open' : ''; ?>>
		<summary><?php esc_html_e( 'Customize this post', 'social-media-auto-post' ); ?><?php Icons::render( 'chevron-down', 16 ); ?></summary>
		<div class="a4smap-disclosure__body">
			<div class="a4smap-field">
				<span class="a4smap-label"><?php esc_html_e( 'Post as', 'social-media-auto-post' ); ?></span>
				<div class="a4smap-choices a4smap-choices--tiles">
					<?php foreach ( $format_names as $format_value => $format_name ) : ?>
						<label class="a4smap-choice" title="<?php echo esc_attr( '' === $format_value ? sprintf( /* translators: %s: default format */ __( 'Default: %s', 'social-media-auto-post' ), Format::label( $config->format ) ) : Format::description( $format_value ) ); ?>">
							<input type="radio" name="<?php echo esc_attr( $field( 'format' ) ); ?>" value="<?php echo esc_attr( $format_value ); ?>" data-a4smap-preview-input <?php checked( (string) $overrides->format, $format_value ); ?> />
							<span class="a4smap-choice__body">
								<span class="a4smap-choice__icon"><?php Icons::render( $format_icons[ $format_value ], 18 ); ?></span>
								<span class="a4smap-choice__title"><?php echo esc_html( $format_name ); ?></span>
							</span>
						</label>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="a4smap-field">
				<label class="a4smap-label" for="a4smap-image-source"><?php esc_html_e( 'Images from', 'social-media-auto-post' ); ?></label>
				<select id="a4smap-image-source" name="<?php echo esc_attr( $field( 'image_source' ) ); ?>" data-a4smap-field="image_source" data-a4smap-preview-input>
					<option value="">
						<?php
						/* translators: %s: default value from the settings */
						echo esc_html( sprintf( __( 'Default (%s)', 'social-media-auto-post' ), ImageSource::label( $config->image_source ) ) );
						?>
					</option>
					<?php foreach ( ImageSource::options( true ) as $source_value => $source_label ) : ?>
						<option value="<?php echo esc_attr( $source_value ); ?>" <?php selected( (string) $overrides->image_source, $source_value ); ?>><?php echo esc_html( $source_label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="a4smap-field" data-a4smap-when="image_source" data-a4smap-values="custom">
				<input type="hidden" name="<?php echo esc_attr( $field( 'media_ids' ) ); ?>" value="<?php echo esc_attr( implode( ',', array_keys( $custom_media ) ) ); ?>" data-a4smap-media-ids data-a4smap-preview-input />
				<ul class="a4smap-thumbs" data-a4smap-media-list>
					<?php foreach ( $custom_media as $thumbnail ) : ?>
						<li><img src="<?php echo esc_url( $thumbnail ); ?>" alt="" /></li>
					<?php endforeach; ?>
				</ul>
				<div class="a4smap-picker-actions">
					<button type="button" class="a4smap-btn a4smap-btn--ghost" data-a4smap-pick><?php Icons::render( 'image-plus', 15 ); ?><?php esc_html_e( 'Choose media', 'social-media-auto-post' ); ?></button>
					<button type="button" class="a4smap-btn a4smap-btn--link" data-a4smap-clear-media><?php esc_html_e( 'Clear', 'social-media-auto-post' ); ?></button>
				</div>
			</div>

			<div class="a4smap-field">
				<span class="a4smap-label"><?php esc_html_e( 'Networks', 'social-media-auto-post' ); ?></span>
				<input type="hidden" name="<?php echo esc_attr( $field( 'platforms_field' ) ); ?>" value="1" />
				<div class="a4smap-chips">
					<?php foreach ( Network::options() as $network_value => $network_label ) : ?>
						<label class="a4smap-chip">
							<input type="checkbox" name="<?php echo esc_attr( $field( 'platforms' ) ); ?>[]" value="<?php echo esc_attr( $network_value ); ?>" data-a4smap-preview-input <?php checked( in_array( $network_value, $chosen_platforms, true ) ); ?> />
							<span class="a4smap-chip__body">
								<?php Icons::render_network( $network_value, 20, true ); ?>
								<?php echo esc_html( $network_label ); ?>
							</span>
						</label>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="a4smap-field">
				<span class="a4smap-label"><?php esc_html_e( 'In Advert4', 'social-media-auto-post' ); ?></span>
				<div class="a4smap-segmented" role="radiogroup">
					<?php foreach ( $mode_names as $mode_value => $mode_name ) : ?>
						<label title="<?php echo esc_attr( '' === $mode_value ? Mode::label( $config->mode ) : Mode::label( $mode_value ) ); ?>">
							<input type="radio" name="<?php echo esc_attr( $field( 'mode' ) ); ?>" value="<?php echo esc_attr( $mode_value ); ?>" data-a4smap-preview-input <?php checked( (string) $overrides->mode, $mode_value ); ?> />
							<span><?php echo esc_html( $mode_name ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="a4smap-field">
				<label class="a4smap-label" for="a4smap-caption"><?php esc_html_e( 'Caption', 'social-media-auto-post' ); ?></label>
				<textarea id="a4smap-caption" rows="4" name="<?php echo esc_attr( $field( 'caption' ) ); ?>" placeholder="<?php echo esc_attr( $config->caption ); ?>" data-a4smap-preview-input data-a4smap-caption-input><?php echo esc_textarea( $overrides->caption ); ?></textarea>
				<div class="a4smap-tokens">
					<?php foreach ( array( '{title}', '{excerpt}', '{url}', '{hashtags}' ) as $token ) : ?>
						<button type="button" class="a4smap-token" data-a4smap-insert="<?php echo esc_attr( $token ); ?>"><?php echo esc_html( $token ); ?></button>
					<?php endforeach; ?>
				</div>
				<p class="a4smap-hint"><?php esc_html_e( 'Leave empty to use the template from the settings.', 'social-media-auto-post' ); ?></p>
			</div>
		</div>
	</details>
</div>
