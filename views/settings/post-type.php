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
 */

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
$field     = static fn ( string $key ): string => $base . '[' . $key . ']';
$dom_id    = static fn ( string $key ): string => 'a4smap-' . sanitize_html_class( $type_slug ) . '-' . $key;
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
?>
<div class="a4smap-card" data-a4smap-scope>
	<div class="a4smap-card__header">
		<label class="a4smap-switch">
			<input type="checkbox" name="<?php echo esc_attr( $field( 'enabled' ) ); ?>" value="1" data-a4smap-field="enabled" <?php checked( $config->enabled ); ?> />
			<span class="a4smap-card__title"><?php echo esc_html( $post_type->labels->name ); ?></span>
			<code><?php echo esc_html( $type_slug ); ?></code>
		</label>
	</div>

	<div class="a4smap-card__body" data-a4smap-when="enabled" data-a4smap-values="1">
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'When to share', 'social-media-auto-post' ); ?></th>
				<td>
					<fieldset>
						<?php foreach ( Trigger::options() as $trigger_value => $trigger_label ) : ?>
							<label class="a4smap-radio">
								<input type="radio" name="<?php echo esc_attr( $field( 'trigger' ) ); ?>" value="<?php echo esc_attr( $trigger_value ); ?>" <?php checked( $config->trigger, $trigger_value ); ?> />
								<?php echo esc_html( $trigger_label ); ?>
							</label>
						<?php endforeach; ?>
						<p class="description"><?php esc_html_e( 'Either way, every post has a "Share" checkbox and a "Share now" button in the editor.', 'social-media-auto-post' ); ?></p>
					</fieldset>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="<?php echo esc_attr( $dom_id( 'profile' ) ); ?>"><?php esc_html_e( 'Advert4 profile', 'social-media-auto-post' ); ?></label></th>
				<td>
					<?php if ( array() !== $profiles ) : ?>
						<select id="<?php echo esc_attr( $dom_id( 'profile' ) ); ?>" name="<?php echo esc_attr( $field( 'profile' ) ); ?>" data-a4smap-profile>
							<option value="" data-networks="<?php echo esc_attr( 1 === count( $profiles ) ? $profile_networks( $profiles[0] ) : '' ); ?>">
								<?php echo esc_html( 1 === count( $profiles ) ? sprintf( /* translators: %s: profile name */ __( 'The only profile (%s)', 'social-media-auto-post' ), (string) $profiles[0]['name'] ) : __( '— Choose a profile —', 'social-media-auto-post' ) ); ?>
							</option>
							<?php foreach ( $profiles as $profile ) : ?>
								<option value="<?php echo esc_attr( (string) $profile['slug'] ); ?>" data-networks="<?php echo esc_attr( $profile_networks( $profile ) ); ?>" <?php selected( $config->profile, (string) $profile['slug'] ); ?>>
									<?php echo esc_html( (string) $profile['name'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					<?php else : ?>
						<input type="text" class="regular-text" id="<?php echo esc_attr( $dom_id( 'profile' ) ); ?>" name="<?php echo esc_attr( $field( 'profile' ) ); ?>" value="<?php echo esc_attr( $config->profile ); ?>" placeholder="<?php esc_attr_e( 'Profile slug', 'social-media-auto-post' ); ?>" data-a4smap-profile-input />
						<p class="description"><?php esc_html_e( 'Connect Advert4 on the Connection tab to pick a profile from a list.', 'social-media-auto-post' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Networks', 'social-media-auto-post' ); ?></th>
				<td>
					<fieldset class="a4smap-networks">
						<?php foreach ( Network::options() as $network_value => $network_label ) : ?>
							<label class="a4smap-net a4smap-net--<?php echo esc_attr( $network_value ); ?>" data-a4smap-network="<?php echo esc_attr( $network_value ); ?>">
								<input type="checkbox" name="<?php echo esc_attr( $field( 'platforms' ) ); ?>[]" value="<?php echo esc_attr( $network_value ); ?>" <?php checked( in_array( $network_value, $config->platforms, true ) ); ?> />
								<?php echo esc_html( $network_label ); ?>
							</label>
						<?php endforeach; ?>
					</fieldset>
					<p class="description"><?php esc_html_e( 'Leave all unticked to publish to every account connected in the profile.', 'social-media-auto-post' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="<?php echo esc_attr( $dom_id( 'format' ) ); ?>"><?php esc_html_e( 'Publish as', 'social-media-auto-post' ); ?></label></th>
				<td>
					<select id="<?php echo esc_attr( $dom_id( 'format' ) ); ?>" name="<?php echo esc_attr( $field( 'format' ) ); ?>" data-a4smap-field="format">
						<?php foreach ( Format::options() as $format_value => $format_label ) : ?>
							<option value="<?php echo esc_attr( $format_value ); ?>" <?php selected( $config->format, $format_value ); ?>><?php echo esc_html( $format_label ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php foreach ( Format::values() as $format_value ) : ?>
						<p class="description" data-a4smap-when="format" data-a4smap-values="<?php echo esc_attr( $format_value ); ?>"><?php echo esc_html( Format::description( $format_value ) ); ?></p>
					<?php endforeach; ?>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="<?php echo esc_attr( $dom_id( 'image_source' ) ); ?>"><?php esc_html_e( 'Images from', 'social-media-auto-post' ); ?></label></th>
				<td>
					<select id="<?php echo esc_attr( $dom_id( 'image_source' ) ); ?>" name="<?php echo esc_attr( $field( 'image_source' ) ); ?>" data-a4smap-field="image_source">
						<?php foreach ( ImageSource::options() as $source_value => $source_label ) : ?>
							<option value="<?php echo esc_attr( $source_value ); ?>" <?php selected( $config->image_source, $source_value ); ?>><?php echo esc_html( $source_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>

			<tr data-a4smap-when="image_source" data-a4smap-values="meta">
				<th scope="row"><label for="<?php echo esc_attr( $dom_id( 'image_meta_key' ) ); ?>"><?php esc_html_e( 'Image custom field', 'social-media-auto-post' ); ?></label></th>
				<td>
					<input type="text" class="regular-text code" id="<?php echo esc_attr( $dom_id( 'image_meta_key' ) ); ?>" name="<?php echo esc_attr( $field( 'image_meta_key' ) ); ?>" value="<?php echo esc_attr( $config->image_meta_key ); ?>" list="<?php echo esc_attr( $datalist ); ?>" placeholder="gallery" />
					<p class="description"><?php esc_html_e( 'Meta key that holds attachment IDs, URLs or an ACF image/gallery field.', 'social-media-auto-post' ); ?></p>
				</td>
			</tr>

			<tr data-a4smap-when="image_source" data-a4smap-values="meta,gallery,content,attached">
				<th scope="row"><?php esc_html_e( 'Featured image', 'social-media-auto-post' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $field( 'featured_first' ) ); ?>" value="1" <?php checked( $config->featured_first ); ?> />
						<?php esc_html_e( 'Put the featured image first', 'social-media-auto-post' ); ?>
					</label>
					<br />
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $field( 'featured_fallback' ) ); ?>" value="1" <?php checked( $config->featured_fallback ); ?> />
						<?php esc_html_e( 'Use the featured image when no other image is found', 'social-media-auto-post' ); ?>
					</label>
				</td>
			</tr>

			<tr data-a4smap-when="image_source" data-a4smap-values="featured,meta,gallery,content,attached">
				<th scope="row"><label for="<?php echo esc_attr( $dom_id( 'max_images' ) ); ?>"><?php esc_html_e( 'Most images', 'social-media-auto-post' ); ?></label></th>
				<td>
					<input type="number" class="small-text" min="1" max="35" id="<?php echo esc_attr( $dom_id( 'max_images' ) ); ?>" name="<?php echo esc_attr( $field( 'max_images' ) ); ?>" value="<?php echo esc_attr( (string) $config->max_images ); ?>" />
					<p class="description"><?php esc_html_e( 'Instagram and Facebook carousels take up to 10 images.', 'social-media-auto-post' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="<?php echo esc_attr( $dom_id( 'video_source' ) ); ?>"><?php esc_html_e( 'Video from', 'social-media-auto-post' ); ?></label></th>
				<td>
					<select id="<?php echo esc_attr( $dom_id( 'video_source' ) ); ?>" name="<?php echo esc_attr( $field( 'video_source' ) ); ?>" data-a4smap-field="video_source">
						<?php foreach ( VideoSource::options() as $source_value => $source_label ) : ?>
							<option value="<?php echo esc_attr( $source_value ); ?>" <?php selected( $config->video_source, $source_value ); ?>><?php echo esc_html( $source_label ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'With "Automatic", a video that is found is posted as a Reel instead of the images. MP4 and MOV files are supported.', 'social-media-auto-post' ); ?></p>
				</td>
			</tr>

			<tr data-a4smap-when="video_source" data-a4smap-values="meta">
				<th scope="row"><label for="<?php echo esc_attr( $dom_id( 'video_meta_key' ) ); ?>"><?php esc_html_e( 'Video custom field', 'social-media-auto-post' ); ?></label></th>
				<td>
					<input type="text" class="regular-text code" id="<?php echo esc_attr( $dom_id( 'video_meta_key' ) ); ?>" name="<?php echo esc_attr( $field( 'video_meta_key' ) ); ?>" value="<?php echo esc_attr( $config->video_meta_key ); ?>" list="<?php echo esc_attr( $datalist ); ?>" placeholder="video" />
					<p class="description"><?php esc_html_e( 'Meta key that holds a video attachment ID or URL.', 'social-media-auto-post' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="<?php echo esc_attr( $dom_id( 'caption' ) ); ?>"><?php esc_html_e( 'Caption', 'social-media-auto-post' ); ?></label></th>
				<td>
					<textarea class="large-text code" rows="5" id="<?php echo esc_attr( $dom_id( 'caption' ) ); ?>" name="<?php echo esc_attr( $field( 'caption' ) ); ?>"><?php echo esc_textarea( $config->caption ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'Placeholders:', 'social-media-auto-post' ); ?>
						<code>{title}</code> <code>{excerpt}</code> <code>{content}</code> <code>{url}</code> <code>{hashtags}</code> <code>{categories}</code> <code>{meta:field}</code> —
						<a href="#help" data-a4smap-tab="help"><?php esc_html_e( 'all placeholders', 'social-media-auto-post' ); ?></a>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'In Advert4', 'social-media-auto-post' ); ?></th>
				<td>
					<fieldset>
						<?php foreach ( Mode::options() as $mode_value => $mode_label ) : ?>
							<label class="a4smap-radio">
								<input type="radio" name="<?php echo esc_attr( $field( 'mode' ) ); ?>" value="<?php echo esc_attr( $mode_value ); ?>" <?php checked( $config->mode, $mode_value ); ?> />
								<?php echo esc_html( $mode_label ); ?>
							</label>
						<?php endforeach; ?>
					</fieldset>
				</td>
			</tr>
		</table>
	</div>

	<?php if ( array() !== $meta_keys ) : ?>
		<datalist id="<?php echo esc_attr( $datalist ); ?>">
			<?php foreach ( $meta_keys as $meta_key ) : ?>
				<option value="<?php echo esc_attr( $meta_key ); ?>"></option>
			<?php endforeach; ?>
		</datalist>
	<?php endif; ?>
</div>
