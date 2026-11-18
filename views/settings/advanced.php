<?php
/**
 * Advanced tab.
 *
 * @package Advert4\SocialMediaAutoPost
 *
 * @var \Advert4\SocialMediaAutoPost\Settings\Settings $settings
 */

use Advert4\SocialMediaAutoPost\Admin\Icons;
use Advert4\SocialMediaAutoPost\Settings\Settings;

defined( 'ABSPATH' ) || exit;

$option = Settings::OPTION;
?>
<div class="a4smap-panel__head">
	<div>
		<h2 class="a4smap-panel__title"><?php esc_html_e( 'Advanced', 'social-media-auto-post' ); ?></h2>
		<p class="a4smap-panel__lead"><?php esc_html_e( 'Fine-tune media quality, privacy and the connection. The defaults work for most sites.', 'social-media-auto-post' ); ?></p>
	</div>
</div>

<div class="a4smap-grid a4smap-grid--2">
	<div class="a4smap-card">
		<div class="a4smap-card__head">
			<span class="a4smap-badge-icon"><?php Icons::render( 'image', 20 ); ?></span>
			<div>
				<h3 class="a4smap-card__title"><?php esc_html_e( 'Media and privacy', 'social-media-auto-post' ); ?></h3>
				<p class="a4smap-card__text"><?php esc_html_e( 'What is sent, and what never leaves your site.', 'social-media-auto-post' ); ?></p>
			</div>
		</div>
		<div class="a4smap-card__body">
			<div class="a4smap-field">
				<label class="a4smap-label" for="a4smap-image-size"><?php esc_html_e( 'Image size', 'social-media-auto-post' ); ?></label>
				<select id="a4smap-image-size" name="<?php echo esc_attr( $option ); ?>[image_size]">
					<?php foreach ( Settings::image_sizes() as $size_key => $size_label ) : ?>
						<option value="<?php echo esc_attr( $size_key ); ?>" <?php selected( $settings->image_size(), $size_key ); ?>><?php echo esc_html( $size_label ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="a4smap-hint"><?php esc_html_e( 'For media library images. URLs from custom fields are sent as they are.', 'social-media-auto-post' ); ?></p>
			</div>
			<div class="a4smap-field">
				<label class="a4smap-switch">
					<input type="checkbox" role="switch" name="<?php echo esc_attr( $option ); ?>[skip_protected]" value="1" <?php checked( $settings->skip_protected() ); ?> />
					<span class="a4smap-switch__track" aria-hidden="true"></span>
					<span class="a4smap-switch__text"><strong><?php esc_html_e( 'Skip password protected posts', 'social-media-auto-post' ); ?></strong><span><?php esc_html_e( 'They are never sent to social media.', 'social-media-auto-post' ); ?></span></span>
				</label>
			</div>
			<div class="a4smap-field">
				<label class="a4smap-switch">
					<input type="checkbox" role="switch" name="<?php echo esc_attr( $option ); ?>[delete_on_uninstall]" value="1" <?php checked( $settings->delete_on_uninstall() ); ?> />
					<span class="a4smap-switch__track" aria-hidden="true"></span>
					<span class="a4smap-switch__text"><strong><?php esc_html_e( 'Delete data on uninstall', 'social-media-auto-post' ); ?></strong><span><?php esc_html_e( 'Remove the settings and sharing history when the plugin is deleted.', 'social-media-auto-post' ); ?></span></span>
				</label>
			</div>
		</div>
	</div>

	<div class="a4smap-card">
		<div class="a4smap-card__head">
			<span class="a4smap-badge-icon"><?php Icons::render( 'plug', 20 ); ?></span>
			<div>
				<h3 class="a4smap-card__title"><?php esc_html_e( 'Connection', 'social-media-auto-post' ); ?></h3>
				<p class="a4smap-card__text"><?php esc_html_e( 'Only change these for a self-hosted or test Advert4.', 'social-media-auto-post' ); ?></p>
			</div>
		</div>
		<div class="a4smap-card__body">
			<div class="a4smap-field">
				<label class="a4smap-label" for="a4smap-api-url"><?php esc_html_e( 'API URL', 'social-media-auto-post' ); ?></label>
				<input type="url" id="a4smap-api-url" class="code" name="<?php echo esc_attr( $option ); ?>[api_url]" value="<?php echo esc_attr( (string) $settings->get( 'api_url', '' ) ); ?>" placeholder="<?php echo esc_attr( Settings::DEFAULT_API_URL ); ?>" />
			</div>
			<div class="a4smap-field">
				<label class="a4smap-label" for="a4smap-timeout"><?php esc_html_e( 'Request timeout (seconds)', 'social-media-auto-post' ); ?></label>
				<input type="number" id="a4smap-timeout" min="10" max="600" name="<?php echo esc_attr( $option ); ?>[timeout]" value="<?php echo esc_attr( (string) $settings->timeout() ); ?>" />
				<p class="a4smap-hint"><?php esc_html_e( 'Advert4 downloads the media while the post is created; large videos need more time.', 'social-media-auto-post' ); ?></p>
			</div>
			<div class="a4smap-field">
				<span class="a4smap-label"><?php esc_html_e( 'Status webhook', 'social-media-auto-post' ); ?></span>
				<code class="a4smap-code"><?php echo esc_html( rest_url( 'a4smap/v1/webhook' ) ); ?></code>
				<p class="a4smap-hint"><?php esc_html_e( 'Advert4 calls this signed address when a post goes live. On private sites the plugin checks the status itself.', 'social-media-auto-post' ); ?></p>
			</div>
		</div>
	</div>
</div>
