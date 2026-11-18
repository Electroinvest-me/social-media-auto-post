<?php
/**
 * Connection tab: API key and Advert4 profiles.
 *
 * @package Advert4\SocialMediaAutoPost
 *
 * @var \Advert4\SocialMediaAutoPost\Settings\Settings $settings
 * @var list<array<string, mixed>>                      $profiles
 * @var string                                          $profile_error
 * @var string                                          $app_url
 */

use Advert4\SocialMediaAutoPost\Admin\Icons;
use Advert4\SocialMediaAutoPost\Settings\Settings;
use Advert4\SocialMediaAutoPost\Support\View;

defined( 'ABSPATH' ) || exit;

$option  = Settings::OPTION;
$api_key = (string) $settings->get( 'api_key', '' );
?>
<div class="a4smap-panel__head">
	<div>
		<h2 class="a4smap-panel__title"><?php esc_html_e( 'Connect Advert4', 'social-media-auto-post' ); ?></h2>
		<p class="a4smap-panel__lead"><?php esc_html_e( 'Advert4 holds the connection to your Facebook Page, Instagram and TikTok accounts. WordPress only needs the API key of your workspace.', 'social-media-auto-post' ); ?></p>
	</div>
</div>

<div class="a4smap-grid a4smap-grid--2">
	<div class="a4smap-card">
		<div class="a4smap-card__head">
			<span class="a4smap-badge-icon"><?php Icons::render( 'key-round', 20 ); ?></span>
			<div>
				<h3 class="a4smap-card__title"><?php esc_html_e( 'API key', 'social-media-auto-post' ); ?></h3>
				<p class="a4smap-card__text">
					<?php
					printf(
						/* translators: %s: link to the Advert4 app */
						esc_html__( 'Create one in %s under API, then paste it here.', 'social-media-auto-post' ),
						'<a href="' . esc_url( $app_url ) . '" target="_blank" rel="noopener">Advert4</a>'
					);
					?>
				</p>
			</div>
		</div>
		<div class="a4smap-card__body">
			<?php if ( $settings->api_key_in_config() ) : ?>
				<p class="a4smap-note a4smap-note--success"><?php Icons::render( 'circle-check', 15 ); ?><span><?php esc_html_e( 'The key is set in wp-config.php (A4SMAP_API_KEY).', 'social-media-auto-post' ); ?></span></p>
			<?php else : ?>
				<label class="screen-reader-text" for="a4smap-api-key"><?php esc_html_e( 'API key', 'social-media-auto-post' ); ?></label>
				<div class="a4smap-input-group">
					<input
						type="password"
						id="a4smap-api-key"
						name="<?php echo esc_attr( $option ); ?>[api_key]"
						value=""
						autocomplete="off"
						spellcheck="false"
						placeholder="<?php echo esc_attr( '' !== $api_key ? '••••••••••••' . substr( $api_key, -4 ) : __( 'Paste your Advert4 API key', 'social-media-auto-post' ) ); ?>"
					/>
					<button type="button" class="a4smap-btn a4smap-btn--ghost" data-a4smap-reveal="#a4smap-api-key" aria-label="<?php esc_attr_e( 'Show or hide the key', 'social-media-auto-post' ); ?>">
						<?php Icons::render( 'eye', 16 ); ?>
					</button>
				</div>
				<?php if ( '' !== $api_key ) : ?>
					<span class="a4smap-key-saved"><?php Icons::render( 'circle-check', 15 ); ?><?php esc_html_e( 'A key is saved. Leave the field empty to keep it.', 'social-media-auto-post' ); ?></span>
					<p>
						<label class="a4smap-muted">
							<input type="checkbox" name="<?php echo esc_attr( $option ); ?>[api_key_remove]" value="1" />
							<?php esc_html_e( 'Remove the saved key', 'social-media-auto-post' ); ?>
						</label>
					</p>
				<?php endif; ?>
				<p class="a4smap-hint"><?php esc_html_e( 'Tip: you can also define A4SMAP_API_KEY in wp-config.php.', 'social-media-auto-post' ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<div class="a4smap-card">
		<div class="a4smap-card__head">
			<span class="a4smap-badge-icon"><?php Icons::render( 'plug', 20 ); ?></span>
			<div style="flex:1">
				<h3 class="a4smap-card__title"><?php esc_html_e( 'Workspace', 'social-media-auto-post' ); ?></h3>
				<p class="a4smap-card__text"><?php esc_html_e( 'Profiles and the accounts connected to them.', 'social-media-auto-post' ); ?></p>
			</div>
			<button type="button" class="a4smap-btn a4smap-btn--ghost" data-a4smap-test <?php disabled( ! $settings->has_api_key() ); ?>>
				<?php Icons::render( 'refresh-cw', 15 ); ?>
				<?php esc_html_e( 'Test connection', 'social-media-auto-post' ); ?>
			</button>
		</div>
		<div class="a4smap-card__body" data-a4smap-profiles>
			<?php if ( '' !== $profile_error ) : ?>
				<p class="a4smap-note a4smap-note--error"><?php Icons::render( 'circle-x', 15 ); ?><span><?php echo esc_html( $profile_error ); ?></span></p>
			<?php elseif ( $settings->has_api_key() ) : ?>
				<?php View::render( 'settings/profiles', array( 'profiles' => $profiles ) ); ?>
			<?php else : ?>
				<div class="a4smap-empty">
					<?php Icons::render( 'key-round', 26 ); ?>
					<?php esc_html_e( 'Save your API key to see your profiles here.', 'social-media-auto-post' ); ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
