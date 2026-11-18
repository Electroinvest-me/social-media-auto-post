<?php
/**
 * Settings card of one post type.
 *
 * @package Advert4\SocialMediaAutoPost
 *
 * @var \WP_Post_Type                                         $post_type
 * @var \Advert4\SocialMediaAutoPost\Settings\PostTypeConfig $config
 * @var list<array<string, mixed>>                            $profiles
 * @var list<string>                                          $meta_keys
 * @var bool                                                  $open
 */

use Advert4\SocialMediaAutoPost\Admin\Icons;
use Advert4\SocialMediaAutoPost\Enums\Format;
use Advert4\SocialMediaAutoPost\Enums\ImageSource;
use Advert4\SocialMediaAutoPost\Enums\Mode;
use Advert4\SocialMediaAutoPost\Enums\Network;
use Advert4\SocialMediaAutoPost\Enums\Trigger;
use Advert4\SocialMediaAutoPost\Enums\VideoSource;
use Advert4\SocialMediaAutoPost\Settings\Settings;

defined( 'ABSPATH' ) || exit;

$type_slug = $post_type->name;
$base      = Settings::OPTION . '[post_types][' . $type_slug . ']';
$field     = static function ( string $key ) use ( $base ): string {
	return $base . '[' . $key . ']';
};
$dom_id    = static function ( string $key ) use ( $type_slug ): string {
	return 'a4smap-' . sanitize_html_class( $type_slug ) . '-' . $key;
};
$datalist  = 'a4smap-meta-keys-' . sanitize_html_class( $type_slug );

$profile_networks = static function ( array $profile ): string {
	$networks = array();

	foreach ( (array) $profile['accounts'] as $account ) {
		if ( 'active' === $account['status'] ) {
			$networks[] = (string) $account['provider'];
		}
	}

	return implode( ',', array_unique( $networks ) );
};

$profile_label = __( 'No profile chosen', 'social-media-auto-post' );

foreach ( $profiles as $profile ) {
	if ( $config->profile === $profile['slug'] || ( '' === $config->profile && 1 === count( $profiles ) ) ) {
		$profile_label = (string) $profile['name'];
	}
}

if ( '' !== $config->profile && array() === $profiles ) {
	$profile_label = $config->profile;
}

$format_icons  = array(
	Format::AUTO    => 'sparkles',
	Format::IMAGE   => 'image',
	Format::GALLERY => 'images',
	Format::VIDEO   => 'clapperboard',
	Format::STORY   => 'smartphone',
);
$format_titles = array(
	Format::AUTO    => __( 'Automatic', 'social-media-auto-post' ),
	Format::IMAGE   => __( 'Image', 'social-media-auto-post' ),
	Format::GALLERY => __( 'Gallery', 'social-media-auto-post' ),
	Format::VIDEO   => __( 'Reel', 'social-media-auto-post' ),
	Format::STORY   => __( 'Story', 'social-media-auto-post' ),
);
$triggers      = array(
	Trigger::AUTO   => array( 'zap', __( 'Automatically', 'social-media-auto-post' ), __( 'Every new post goes out the moment it is published.', 'social-media-auto-post' ) ),
	Trigger::MANUAL => array( 'mouse-pointer-click', __( 'When I choose', 'social-media-auto-post' ), __( 'Only posts where you switch on “Share” in the editor.', 'social-media-auto-post' ) ),
);
$modes         = array(
	Mode::PUBLISH => array( 'send', __( 'Publish right away', 'social-media-auto-post' ) ),
	Mode::DRAFT   => array( 'file-pen-line', __( 'Draft for review', 'social-media-auto-post' ) ),
);
$tokens        = array( '{title}', '{excerpt}', '{content}', '{url}', '{hashtags}', '{categories}', '{tags}', '{site_name}', '{meta:field_name}' );
?>
<div class="a4smap-card a4smap-type <?php echo $config->enabled ? 'is-enabled' : ''; ?> <?php echo $open ? 'is-open' : ''; ?>" data-a4smap-scope data-a4smap-type="<?php echo esc_attr( $type_slug ); ?>">
	<div class="a4smap-type__head">
		<label class="a4smap-switch">
			<input type="checkbox" role="switch" name="<?php echo esc_attr( $field( 'enabled' ) ); ?>" value="1" data-a4smap-field="enabled" data-a4smap-enable <?php checked( $config->enabled ); ?> />
			<span class="a4smap-switch__track" aria-hidden="true"></span>
			<span class="a4smap-type__name"><?php echo esc_html( $post_type->labels->name ); ?></span>
			<code class="a4smap-code"><?php echo esc_html( $type_slug ); ?></code>
		</label>

		<div class="a4smap-type__summary">
			<span class="a4smap-type__pill" data-a4smap-summary="trigger"><span><?php echo esc_html( $triggers[ $config->trigger ][1] ); ?></span></span>
			<span class="a4smap-type__pill" data-a4smap-summary="format"><span><?php echo esc_html( $format_titles[ $config->format ] ); ?></span></span>
			<span class="a4smap-type__pill" data-a4smap-summary="profile"><span><?php echo esc_html( $profile_label ); ?></span></span>
			<span class="a4smap-nets" data-a4smap-summary="networks">
				<?php foreach ( array() !== $config->platforms ? $config->platforms : Network::values() as $network ) : ?>
					<?php Icons::render_network( $network, 20, true ); ?>
				<?php endforeach; ?>
			</span>
		</div>

		<button type="button" class="a4smap-type__toggle" data-a4smap-expand aria-expanded="<?php echo $open ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $dom_id( 'body' ) ); ?>" aria-label="<?php esc_attr_e( 'Show settings', 'social-media-auto-post' ); ?>" <?php echo $config->enabled ? '' : 'hidden'; ?>>
			<?php Icons::render( 'chevron-down', 18 ); ?>
		</button>
	</div>

	<div class="a4smap-type__body" id="<?php echo esc_attr( $dom_id( 'body' ) ); ?>" <?php echo $open ? '' : 'hidden'; ?>>
		<div class="a4smap-type__form">
			<div class="a4smap-section">
				<h3 class="a4smap-section__title"><?php Icons::render( 'zap', 15 ); ?><?php esc_html_e( 'When to share', 'social-media-auto-post' ); ?></h3>
				<div class="a4smap-choices">
					<?php foreach ( $triggers as $trigger_value => $trigger ) : ?>
						<label class="a4smap-choice">
							<input type="radio" name="<?php echo esc_attr( $field( 'trigger' ) ); ?>" value="<?php echo esc_attr( $trigger_value ); ?>" data-label="<?php echo esc_attr( $trigger[1] ); ?>" <?php checked( $config->trigger, $trigger_value ); ?> />
							<span class="a4smap-choice__body">
								<span class="a4smap-choice__icon"><?php Icons::render( $trigger[0], 20 ); ?></span>
								<span>
									<span class="a4smap-choice__title"><?php echo esc_html( $trigger[1] ); ?></span>
									<span class="a4smap-choice__text"><?php echo esc_html( $trigger[2] ); ?></span>
								</span>
							</span>
						</label>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="a4smap-section">
				<h3 class="a4smap-section__title"><?php Icons::render( 'send', 15 ); ?><?php esc_html_e( 'Where', 'social-media-auto-post' ); ?></h3>
				<div class="a4smap-field">
					<label class="a4smap-label" for="<?php echo esc_attr( $dom_id( 'profile' ) ); ?>"><?php esc_html_e( 'Advert4 profile', 'social-media-auto-post' ); ?></label>
					<?php if ( array() !== $profiles ) : ?>
						<select id="<?php echo esc_attr( $dom_id( 'profile' ) ); ?>" name="<?php echo esc_attr( $field( 'profile' ) ); ?>" data-a4smap-profile data-a4smap-preview-input>
							<option value="" data-networks="<?php echo esc_attr( 1 === count( $profiles ) ? $profile_networks( $profiles[0] ) : '' ); ?>" data-label="<?php echo esc_attr( 1 === count( $profiles ) ? (string) $profiles[0]['name'] : __( 'No profile chosen', 'social-media-auto-post' ) ); ?>">
								<?php echo esc_html( 1 === count( $profiles ) ? sprintf( /* translators: %s: profile name */ __( 'The only profile (%s)', 'social-media-auto-post' ), (string) $profiles[0]['name'] ) : __( '— Choose a profile —', 'social-media-auto-post' ) ); ?>
							</option>
							<?php foreach ( $profiles as $profile ) : ?>
								<option value="<?php echo esc_attr( (string) $profile['slug'] ); ?>" data-networks="<?php echo esc_attr( $profile_networks( $profile ) ); ?>" data-label="<?php echo esc_attr( (string) $profile['name'] ); ?>" <?php selected( $config->profile, (string) $profile['slug'] ); ?>>
									<?php echo esc_html( (string) $profile['name'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					<?php else : ?>
						<input type="text" id="<?php echo esc_attr( $dom_id( 'profile' ) ); ?>" name="<?php echo esc_attr( $field( 'profile' ) ); ?>" value="<?php echo esc_attr( $config->profile ); ?>" placeholder="<?php esc_attr_e( 'Profile slug', 'social-media-auto-post' ); ?>" data-a4smap-profile-input />
						<p class="a4smap-hint"><?php esc_html_e( 'Connect Advert4 on the Connection tab to pick a profile from a list.', 'social-media-auto-post' ); ?></p>
					<?php endif; ?>
				</div>

				<div class="a4smap-field">
					<span class="a4smap-label"><?php esc_html_e( 'Networks', 'social-media-auto-post' ); ?></span>
					<div class="a4smap-chips">
						<?php foreach ( Network::options() as $network_value => $network_label ) : ?>
							<label class="a4smap-chip" data-a4smap-network="<?php echo esc_attr( $network_value ); ?>">
								<input type="checkbox" name="<?php echo esc_attr( $field( 'platforms' ) ); ?>[]" value="<?php echo esc_attr( $network_value ); ?>" data-a4smap-preview-input <?php checked( in_array( $network_value, $config->platforms, true ) ); ?> />
								<span class="a4smap-chip__body">
									<?php Icons::render_network( $network_value, 24, true ); ?>
									<?php echo esc_html( $network_label ); ?>
									<span class="a4smap-chip__check"><?php Icons::render( 'check', 14 ); ?></span>
								</span>
							</label>
						<?php endforeach; ?>
					</div>
					<p class="a4smap-hint"><?php esc_html_e( 'Leave all off to post to every account connected in the profile.', 'social-media-auto-post' ); ?></p>
				</div>
			</div>

			<div class="a4smap-section">
				<h3 class="a4smap-section__title"><?php Icons::render( 'sparkles', 15 ); ?><?php esc_html_e( 'Post as', 'social-media-auto-post' ); ?></h3>
				<div class="a4smap-choices a4smap-choices--tiles">
					<?php foreach ( Format::values() as $format_value ) : ?>
						<label class="a4smap-choice">
							<input type="radio" name="<?php echo esc_attr( $field( 'format' ) ); ?>" value="<?php echo esc_attr( $format_value ); ?>" data-a4smap-field="format" data-a4smap-preview-input data-label="<?php echo esc_attr( $format_titles[ $format_value ] ); ?>" <?php checked( $config->format, $format_value ); ?> />
							<span class="a4smap-choice__body">
								<span class="a4smap-choice__icon"><?php Icons::render( $format_icons[ $format_value ], 22 ); ?></span>
								<span class="a4smap-choice__title"><?php echo esc_html( $format_titles[ $format_value ] ); ?></span>
							</span>
						</label>
					<?php endforeach; ?>
				</div>
				<?php foreach ( Format::values() as $format_value ) : ?>
					<p class="a4smap-hint" data-a4smap-when="format" data-a4smap-values="<?php echo esc_attr( $format_value ); ?>"><?php echo esc_html( Format::description( $format_value ) ); ?></p>
				<?php endforeach; ?>
			</div>

			<div class="a4smap-section">
				<h3 class="a4smap-section__title"><?php Icons::render( 'image', 15 ); ?><?php esc_html_e( 'Images and video', 'social-media-auto-post' ); ?></h3>
				<div class="a4smap-row">
					<div class="a4smap-field">
						<label class="a4smap-label" for="<?php echo esc_attr( $dom_id( 'image_source' ) ); ?>"><?php esc_html_e( 'Images from', 'social-media-auto-post' ); ?></label>
						<select id="<?php echo esc_attr( $dom_id( 'image_source' ) ); ?>" name="<?php echo esc_attr( $field( 'image_source' ) ); ?>" data-a4smap-field="image_source" data-a4smap-preview-input>
							<?php foreach ( ImageSource::options() as $source_value => $source_label ) : ?>
								<option value="<?php echo esc_attr( $source_value ); ?>" <?php selected( $config->image_source, $source_value ); ?>><?php echo esc_html( $source_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="a4smap-field" data-a4smap-when="image_source" data-a4smap-values="meta">
						<label class="a4smap-label" for="<?php echo esc_attr( $dom_id( 'image_meta_key' ) ); ?>"><?php esc_html_e( 'Custom field name', 'social-media-auto-post' ); ?></label>
						<input type="text" class="code" id="<?php echo esc_attr( $dom_id( 'image_meta_key' ) ); ?>" name="<?php echo esc_attr( $field( 'image_meta_key' ) ); ?>" value="<?php echo esc_attr( $config->image_meta_key ); ?>" list="<?php echo esc_attr( $datalist ); ?>" placeholder="gallery" data-a4smap-preview-input />
						<p class="a4smap-hint"><?php esc_html_e( 'Attachment IDs, URLs, or an ACF image/gallery field.', 'social-media-auto-post' ); ?></p>
					</div>
				</div>

				<div class="a4smap-stack" style="margin-top:16px" data-a4smap-when="image_source" data-a4smap-values="featured,meta,gallery,content,attached">
					<label class="a4smap-switch" data-a4smap-when="image_source" data-a4smap-values="meta,gallery,content,attached">
						<input type="checkbox" role="switch" name="<?php echo esc_attr( $field( 'featured_first' ) ); ?>" value="1" data-a4smap-preview-input <?php checked( $config->featured_first ); ?> />
						<span class="a4smap-switch__track" aria-hidden="true"></span>
						<span class="a4smap-switch__text"><strong><?php esc_html_e( 'Featured image first', 'social-media-auto-post' ); ?></strong><span><?php esc_html_e( 'Open the gallery with the featured image.', 'social-media-auto-post' ); ?></span></span>
					</label>
					<label class="a4smap-switch" data-a4smap-when="image_source" data-a4smap-values="meta,gallery,content,attached">
						<input type="checkbox" role="switch" name="<?php echo esc_attr( $field( 'featured_fallback' ) ); ?>" value="1" data-a4smap-preview-input <?php checked( $config->featured_fallback ); ?> />
						<span class="a4smap-switch__track" aria-hidden="true"></span>
						<span class="a4smap-switch__text"><strong><?php esc_html_e( 'Fall back to the featured image', 'social-media-auto-post' ); ?></strong><span><?php esc_html_e( 'Used when no other image is found.', 'social-media-auto-post' ); ?></span></span>
					</label>
					<div class="a4smap-field">
						<label class="a4smap-label" for="<?php echo esc_attr( $dom_id( 'max_images' ) ); ?>"><?php esc_html_e( 'Most images per post', 'social-media-auto-post' ); ?></label>
						<input type="number" min="1" max="35" id="<?php echo esc_attr( $dom_id( 'max_images' ) ); ?>" name="<?php echo esc_attr( $field( 'max_images' ) ); ?>" value="<?php echo esc_attr( (string) $config->max_images ); ?>" data-a4smap-preview-input />
						<p class="a4smap-hint"><?php esc_html_e( 'Facebook and Instagram carousels take up to 10.', 'social-media-auto-post' ); ?></p>
					</div>
				</div>

				<div class="a4smap-row" style="margin-top:18px">
					<div class="a4smap-field">
						<label class="a4smap-label" for="<?php echo esc_attr( $dom_id( 'video_source' ) ); ?>"><?php esc_html_e( 'Video from', 'social-media-auto-post' ); ?></label>
						<select id="<?php echo esc_attr( $dom_id( 'video_source' ) ); ?>" name="<?php echo esc_attr( $field( 'video_source' ) ); ?>" data-a4smap-field="video_source" data-a4smap-preview-input>
							<?php foreach ( VideoSource::options() as $source_value => $source_label ) : ?>
								<option value="<?php echo esc_attr( $source_value ); ?>" <?php selected( $config->video_source, $source_value ); ?>><?php echo esc_html( $source_label ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="a4smap-hint"><?php esc_html_e( 'MP4 or MOV. With “Automatic”, a video wins over images.', 'social-media-auto-post' ); ?></p>
					</div>
					<div class="a4smap-field" data-a4smap-when="video_source" data-a4smap-values="meta">
						<label class="a4smap-label" for="<?php echo esc_attr( $dom_id( 'video_meta_key' ) ); ?>"><?php esc_html_e( 'Video custom field', 'social-media-auto-post' ); ?></label>
						<input type="text" class="code" id="<?php echo esc_attr( $dom_id( 'video_meta_key' ) ); ?>" name="<?php echo esc_attr( $field( 'video_meta_key' ) ); ?>" value="<?php echo esc_attr( $config->video_meta_key ); ?>" list="<?php echo esc_attr( $datalist ); ?>" placeholder="video" data-a4smap-preview-input />
					</div>
				</div>
			</div>

			<div class="a4smap-section">
				<h3 class="a4smap-section__title"><?php Icons::render( 'file-pen-line', 15 ); ?><?php esc_html_e( 'Caption', 'social-media-auto-post' ); ?></h3>
				<label class="screen-reader-text" for="<?php echo esc_attr( $dom_id( 'caption' ) ); ?>"><?php esc_html_e( 'Caption template', 'social-media-auto-post' ); ?></label>
				<textarea id="<?php echo esc_attr( $dom_id( 'caption' ) ); ?>" rows="5" name="<?php echo esc_attr( $field( 'caption' ) ); ?>" data-a4smap-preview-input data-a4smap-caption-input><?php echo esc_textarea( $config->caption ); ?></textarea>
				<div class="a4smap-tokens" role="group" aria-label="<?php esc_attr_e( 'Insert a placeholder', 'social-media-auto-post' ); ?>">
					<?php foreach ( $tokens as $token ) : ?>
						<button type="button" class="a4smap-token" data-a4smap-insert="<?php echo esc_attr( $token ); ?>"><?php echo esc_html( $token ); ?></button>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="a4smap-section">
				<h3 class="a4smap-section__title"><?php Icons::render( 'send', 15 ); ?><?php esc_html_e( 'In Advert4', 'social-media-auto-post' ); ?></h3>
				<div class="a4smap-segmented" role="radiogroup">
					<?php foreach ( $modes as $mode_value => $mode_info ) : ?>
						<label>
							<input type="radio" name="<?php echo esc_attr( $field( 'mode' ) ); ?>" value="<?php echo esc_attr( $mode_value ); ?>" data-a4smap-preview-input <?php checked( $config->mode, $mode_value ); ?> />
							<span><?php Icons::render( $mode_info[0], 14 ); ?><?php echo esc_html( $mode_info[1] ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
				<p class="a4smap-hint"><?php esc_html_e( 'Drafts wait in the Advert4 calendar until someone approves them.', 'social-media-auto-post' ); ?></p>
			</div>
		</div>

		<aside class="a4smap-type__aside">
			<div class="a4smap-type__sticky">
				<p class="a4smap-preview__label">
					<span><?php esc_html_e( 'Live preview', 'social-media-auto-post' ); ?></span>
					<button type="button" class="a4smap-btn a4smap-btn--link" data-a4smap-preview-refresh aria-label="<?php esc_attr_e( 'Refresh preview', 'social-media-auto-post' ); ?>"><?php Icons::render( 'refresh-cw', 14 ); ?></button>
				</p>
				<div data-a4smap-settings-preview>
					<div class="a4smap-skeleton" aria-hidden="true"></div>
				</div>
			</div>
		</aside>
	</div>

	<?php if ( array() !== $meta_keys ) : ?>
		<datalist id="<?php echo esc_attr( $datalist ); ?>">
			<?php foreach ( $meta_keys as $meta_key ) : ?>
				<option value="<?php echo esc_attr( $meta_key ); ?>"></option>
			<?php endforeach; ?>
		</datalist>
	<?php endif; ?>
</div>
