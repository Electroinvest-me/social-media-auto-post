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
 * @var bool                                                  $has_api_key
 * @var string                                                $nonce
 */

use Advert4\SocialMediaAutoPost\Admin\MetaBox;
use Advert4\SocialMediaAutoPost\Admin\SettingsPage;
use Advert4\SocialMediaAutoPost\Enums\Format;
use Advert4\SocialMediaAutoPost\Enums\ImageSource;
use Advert4\SocialMediaAutoPost\Enums\Mode;
use Advert4\SocialMediaAutoPost\Enums\Network;
use Advert4\SocialMediaAutoPost\Support\View;

defined( 'ABSPATH' ) || exit;

$field            = static fn ( string $key ): string => MetaBox::FIELD . '[' . $key . ']';
$chosen_platforms = $overrides->platforms ?? $default_platforms;
$is_published     = 'publish' === $post->post_status;
$show_checkbox    = $state->is_empty();
$custom_media     = array();

foreach ( $overrides->media_ids as $media_id ) {
	$thumbnail = wp_get_attachment_image_url( $media_id, 'thumbnail', true );

	if ( is_string( $thumbnail ) ) {
		$custom_media[ $media_id ] = $thumbnail;
	}
}
?>
<div class="a4smap-box" data-a4smap-box data-a4smap-scope data-post="<?php echo esc_attr( (string) $post->ID ); ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>">
	<input type="hidden" name="<?php echo esc_attr( MetaBox::NONCE ); ?>" value="<?php echo esc_attr( $nonce ); ?>" />

	<?php if ( ! $has_api_key ) : ?>
		<p class="a4smap-note a4smap-note--warning">
			<?php esc_html_e( 'Advert4 is not connected yet.', 'social-media-auto-post' ); ?>
			<?php if ( current_user_can( 'manage_options' ) ) : ?>
				<a href="<?php echo esc_url( SettingsPage::url() ); ?>"><?php esc_html_e( 'Add the API key', 'social-media-auto-post' ); ?></a>
			<?php endif; ?>
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
	<?php if ( $show_checkbox ) : ?>
		<p class="a4smap-share-toggle" data-a4smap-share-toggle>
			<label>
				<input type="checkbox" name="<?php echo esc_attr( $field( 'share' ) ); ?>" value="1" <?php checked( $share_default ); ?> />
				<?php
				echo esc_html(
					$is_published
						? __( 'Share to social media when I update this post', 'social-media-auto-post' )
						: __( 'Share to social media when published', 'social-media-auto-post' )
				);
				?>
			</label>
		</p>
	<?php else : ?>
		<input type="hidden" name="<?php echo esc_attr( $field( 'share' ) ); ?>" value="<?php echo esc_attr( $share_default ? '1' : '' ); ?>" />
	<?php endif; ?>

	<details class="a4smap-details" <?php echo $overrides->is_customized() ? 'open' : ''; ?>>
		<summary><?php esc_html_e( 'Customize this post', 'social-media-auto-post' ); ?></summary>

		<p>
			<label class="a4smap-label" for="a4smap-format"><?php esc_html_e( 'Publish as', 'social-media-auto-post' ); ?></label>
			<select id="a4smap-format" class="widefat" name="<?php echo esc_attr( $field( 'format' ) ); ?>" data-a4smap-preview-input>
				<option value="">
					<?php
					/* translators: %s: default value from the settings */
					echo esc_html( sprintf( __( 'Default (%s)', 'social-media-auto-post' ), Format::label( $config->format ) ) );
					?>
				</option>
				<?php foreach ( Format::options() as $format_value => $format_label ) : ?>
					<option value="<?php echo esc_attr( $format_value ); ?>" <?php selected( (string) $overrides->format, $format_value ); ?>><?php echo esc_html( $format_label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>

		<p>
			<label class="a4smap-label" for="a4smap-image-source"><?php esc_html_e( 'Images from', 'social-media-auto-post' ); ?></label>
			<select id="a4smap-image-source" class="widefat" name="<?php echo esc_attr( $field( 'image_source' ) ); ?>" data-a4smap-field="image_source" data-a4smap-preview-input>
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
		</p>

		<div class="a4smap-media-picker" data-a4smap-when="image_source" data-a4smap-values="custom">
			<input type="hidden" name="<?php echo esc_attr( $field( 'media_ids' ) ); ?>" value="<?php echo esc_attr( implode( ',', array_keys( $custom_media ) ) ); ?>" data-a4smap-media-ids data-a4smap-preview-input />
			<ul class="a4smap-thumbs" data-a4smap-media-list>
				<?php foreach ( $custom_media as $thumbnail ) : ?>
					<li><img src="<?php echo esc_url( $thumbnail ); ?>" alt="" /></li>
				<?php endforeach; ?>
			</ul>
			<button type="button" class="button" data-a4smap-pick><?php esc_html_e( 'Choose images or a video', 'social-media-auto-post' ); ?></button>
			<button type="button" class="button-link" data-a4smap-clear-media><?php esc_html_e( 'Clear', 'social-media-auto-post' ); ?></button>
		</div>

		<fieldset class="a4smap-fieldset">
			<legend class="a4smap-label"><?php esc_html_e( 'Networks', 'social-media-auto-post' ); ?></legend>
			<input type="hidden" name="<?php echo esc_attr( $field( 'platforms_field' ) ); ?>" value="1" />
			<?php foreach ( Network::options() as $network_value => $network_label ) : ?>
				<label class="a4smap-net a4smap-net--<?php echo esc_attr( $network_value ); ?>">
					<input type="checkbox" name="<?php echo esc_attr( $field( 'platforms' ) ); ?>[]" value="<?php echo esc_attr( $network_value ); ?>" data-a4smap-preview-input <?php checked( in_array( $network_value, $chosen_platforms, true ) ); ?> />
					<?php echo esc_html( $network_label ); ?>
				</label>
			<?php endforeach; ?>
		</fieldset>

		<p>
			<label class="a4smap-label" for="a4smap-mode"><?php esc_html_e( 'In Advert4', 'social-media-auto-post' ); ?></label>
			<select id="a4smap-mode" class="widefat" name="<?php echo esc_attr( $field( 'mode' ) ); ?>" data-a4smap-preview-input>
				<option value="">
					<?php
					/* translators: %s: default value from the settings */
					echo esc_html( sprintf( __( 'Default (%s)', 'social-media-auto-post' ), Mode::label( $config->mode ) ) );
					?>
				</option>
				<?php foreach ( Mode::options() as $mode_value => $mode_label ) : ?>
					<option value="<?php echo esc_attr( $mode_value ); ?>" <?php selected( (string) $overrides->mode, $mode_value ); ?>><?php echo esc_html( $mode_label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>

		<p>
			<label class="a4smap-label" for="a4smap-caption"><?php esc_html_e( 'Caption', 'social-media-auto-post' ); ?></label>
			<textarea id="a4smap-caption" class="widefat" rows="4" name="<?php echo esc_attr( $field( 'caption' ) ); ?>" placeholder="<?php echo esc_attr( $config->caption ); ?>" data-a4smap-preview-input><?php echo esc_textarea( $overrides->caption ); ?></textarea>
			<span class="description"><?php esc_html_e( 'Empty uses the template from the settings. Placeholders such as {title} and {url} work here too.', 'social-media-auto-post' ); ?></span>
		</p>
	</details>

	<details class="a4smap-details" data-a4smap-preview-wrap>
		<summary><?php esc_html_e( 'Preview', 'social-media-auto-post' ); ?></summary>
		<div data-a4smap-preview>
			<?php View::render( 'metabox/preview', array( 'plan' => $plan ) ); ?>
		</div>
		<p class="description"><?php esc_html_e( 'Based on the last saved version of the post.', 'social-media-auto-post' ); ?></p>
	</details>
</div>
