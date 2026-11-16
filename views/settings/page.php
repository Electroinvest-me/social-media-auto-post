<?php
/**
 * Settings screen.
 *
 * @package Advert4\SocialMediaAutoPost
 *
 * @var \Advert4\SocialMediaAutoPost\Settings\Settings $settings
 * @var list<array<string, mixed>>                      $profiles
 * @var string                                          $profile_error
 * @var array<string, \WP_Post_Type>                    $post_types
 * @var array<string, list<string>>                     $meta_keys
 * @var list<array<string, mixed>>                      $log
 */

use Advert4\SocialMediaAutoPost\Content\CaptionBuilder;
use Advert4\SocialMediaAutoPost\Settings\Settings;
use Advert4\SocialMediaAutoPost\Support\Site;
use Advert4\SocialMediaAutoPost\Support\View;

defined( 'ABSPATH' ) || exit;

$option        = Settings::OPTION;
$api_key       = (string) $settings->get( 'api_key', '' );
$settings_tabs = array(
	'connection' => __( 'Connection', 'social-media-auto-post' ),
	'post-types' => __( 'Post types', 'social-media-auto-post' ),
	'advanced'   => __( 'Advanced', 'social-media-auto-post' ),
	'activity'   => __( 'Activity', 'social-media-auto-post' ),
	'help'       => __( 'Help', 'social-media-auto-post' ),
);
?>
<div class="wrap a4smap">
	<h1 class="a4smap-heading">
		<span class="dashicons dashicons-share" aria-hidden="true"></span>
		<?php esc_html_e( 'Social Media Auto Post', 'social-media-auto-post' ); ?>
	</h1>
	<p class="a4smap-lead">
		<?php esc_html_e( 'Share your posts, images, galleries and videos to Facebook, Instagram and TikTok through Advert4 — automatically when you publish, or only when you choose.', 'social-media-auto-post' ); ?>
	</p>

	<?php settings_errors(); ?>

	<?php if ( ! Site::is_public() ) : ?>
		<div class="notice notice-warning inline">
			<p>
				<?php esc_html_e( 'This site does not look reachable from the internet (local or private address). Advert4 downloads the images and videos from your site, so publishing will only work once the site has a public URL.', 'social-media-auto-post' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<nav class="nav-tab-wrapper a4smap-tabs" aria-label="<?php esc_attr_e( 'Settings sections', 'social-media-auto-post' ); ?>">
		<?php foreach ( $settings_tabs as $tab_id => $tab_label ) : ?>
			<a href="#<?php echo esc_attr( $tab_id ); ?>" class="nav-tab" data-a4smap-tab="<?php echo esc_attr( $tab_id ); ?>"><?php echo esc_html( $tab_label ); ?></a>
		<?php endforeach; ?>
	</nav>

	<form method="post" action="options.php" class="a4smap-form">
		<?php settings_fields( Settings::GROUP ); ?>

		<section class="a4smap-panel" data-a4smap-panel="connection">
			<h2><?php esc_html_e( 'Connect Advert4', 'social-media-auto-post' ); ?></h2>
			<ol class="a4smap-steps">
				<li>
					<?php
					printf(
						/* translators: %s: link to advert4.me */
						esc_html__( 'Sign in to %s and connect your Facebook Page, Instagram and TikTok accounts to a profile.', 'social-media-auto-post' ),
						'<a href="https://advert4.me" target="_blank" rel="noopener">advert4.me</a>'
					);
					?>
				</li>
				<li><?php esc_html_e( 'Open "API" in Advert4 and create an API key for this site.', 'social-media-auto-post' ); ?></li>
				<li><?php esc_html_e( 'Paste the key below, save, and choose what each post type shares.', 'social-media-auto-post' ); ?></li>
			</ol>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="a4smap-api-key"><?php esc_html_e( 'API key', 'social-media-auto-post' ); ?></label></th>
					<td>
						<?php if ( $settings->api_key_in_config() ) : ?>
							<p><code>A4SMAP_API_KEY</code> <?php esc_html_e( 'is defined in wp-config.php and is used.', 'social-media-auto-post' ); ?></p>
						<?php else : ?>
							<input
								type="password"
								id="a4smap-api-key"
								class="regular-text"
								name="<?php echo esc_attr( $option ); ?>[api_key]"
								value=""
								autocomplete="off"
								spellcheck="false"
								placeholder="<?php echo esc_attr( '' !== $api_key ? sprintf( /* translators: %s: last characters of the saved key */ __( 'Saved key ending in …%s — leave empty to keep it', 'social-media-auto-post' ), substr( $api_key, -4 ) ) : __( 'Paste your Advert4 API key', 'social-media-auto-post' ) ); ?>"
							/>
							<?php if ( '' !== $api_key ) : ?>
								<p>
									<label>
										<input type="checkbox" name="<?php echo esc_attr( $option ); ?>[api_key_remove]" value="1" />
										<?php esc_html_e( 'Remove the saved key', 'social-media-auto-post' ); ?>
									</label>
								</p>
							<?php endif; ?>
							<p class="description"><?php esc_html_e( 'The key belongs to one Advert4 workspace. You can also define it in wp-config.php as A4SMAP_API_KEY.', 'social-media-auto-post' ); ?></p>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Profiles', 'social-media-auto-post' ); ?></th>
					<td>
						<p>
							<button type="button" class="button" data-a4smap-test <?php disabled( ! $settings->has_api_key() ); ?>>
								<?php esc_html_e( 'Test connection and reload profiles', 'social-media-auto-post' ); ?>
							</button>
						</p>
						<div class="a4smap-profiles" data-a4smap-profiles>
							<?php if ( '' !== $profile_error ) : ?>
								<div class="notice notice-error inline"><p><?php echo esc_html( $profile_error ); ?></p></div>
							<?php elseif ( $settings->has_api_key() ) : ?>
								<?php View::render( 'settings/profiles', array( 'profiles' => $profiles ) ); ?>
							<?php else : ?>
								<p class="description"><?php esc_html_e( 'Save your API key first.', 'social-media-auto-post' ); ?></p>
							<?php endif; ?>
						</div>
					</td>
				</tr>
			</table>
		</section>

		<section class="a4smap-panel" data-a4smap-panel="post-types">
			<h2><?php esc_html_e( 'What to share', 'social-media-auto-post' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Turn on the post types you want to share and choose the profile, networks, format, media and caption for each. Every post can override these defaults in the "Social Media Auto Post" box of the editor.', 'social-media-auto-post' ); ?>
			</p>

			<?php foreach ( $post_types as $type_slug => $type_object ) : ?>
				<?php
				View::render(
					'settings/post-type',
					array(
						'post_type' => $type_object,
						'config'    => $settings->post_type( (string) $type_slug ),
						'profiles'  => $profiles,
						'meta_keys' => $meta_keys[ $type_slug ] ?? array(),
					)
				);
				?>
			<?php endforeach; ?>
		</section>

		<section class="a4smap-panel" data-a4smap-panel="advanced">
			<h2><?php esc_html_e( 'Advanced', 'social-media-auto-post' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="a4smap-image-size"><?php esc_html_e( 'Image size', 'social-media-auto-post' ); ?></label></th>
					<td>
						<select id="a4smap-image-size" name="<?php echo esc_attr( $option ); ?>[image_size]">
							<?php foreach ( Settings::image_sizes() as $size_key => $size_label ) : ?>
								<option value="<?php echo esc_attr( $size_key ); ?>" <?php selected( $settings->image_size(), $size_key ); ?>><?php echo esc_html( $size_label ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Size of media library images that is sent. URLs from custom fields are sent as they are.', 'social-media-auto-post' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Protected posts', 'social-media-auto-post' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( $option ); ?>[skip_protected]" value="1" <?php checked( $settings->skip_protected() ); ?> />
							<?php esc_html_e( 'Never share password protected posts', 'social-media-auto-post' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="a4smap-timeout"><?php esc_html_e( 'Request timeout', 'social-media-auto-post' ); ?></label></th>
					<td>
						<input type="number" id="a4smap-timeout" class="small-text" min="10" max="600" name="<?php echo esc_attr( $option ); ?>[timeout]" value="<?php echo esc_attr( (string) $settings->timeout() ); ?>" />
						<?php esc_html_e( 'seconds', 'social-media-auto-post' ); ?>
						<p class="description"><?php esc_html_e( 'Advert4 downloads the media while the post is created; large videos need more time.', 'social-media-auto-post' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="a4smap-api-url"><?php esc_html_e( 'API URL', 'social-media-auto-post' ); ?></label></th>
					<td>
						<input type="url" id="a4smap-api-url" class="regular-text code" name="<?php echo esc_attr( $option ); ?>[api_url]" value="<?php echo esc_attr( (string) $settings->get( 'api_url', '' ) ); ?>" placeholder="<?php echo esc_attr( Settings::DEFAULT_API_URL ); ?>" />
						<p class="description"><?php esc_html_e( 'Leave empty to use advert4.me. Only change this for a self-hosted or test installation.', 'social-media-auto-post' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Status updates', 'social-media-auto-post' ); ?></th>
					<td>
						<code><?php echo esc_html( rest_url( 'a4smap/v1/webhook' ) ); ?></code>
						<p class="description"><?php esc_html_e( 'Advert4 calls this signed address when a post is live, so the result per network appears in the editor. When the site is not public the plugin checks the status itself.', 'social-media-auto-post' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Uninstall', 'social-media-auto-post' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( $option ); ?>[delete_on_uninstall]" value="1" <?php checked( $settings->delete_on_uninstall() ); ?> />
							<?php esc_html_e( 'Delete the settings and sharing history when the plugin is deleted', 'social-media-auto-post' ); ?>
						</label>
					</td>
				</tr>
			</table>
		</section>

		<div class="a4smap-submit">
			<?php submit_button(); ?>
		</div>
	</form>

	<section class="a4smap-panel" data-a4smap-panel="activity">
		<h2><?php esc_html_e( 'Activity', 'social-media-auto-post' ); ?></h2>
		<?php View::render( 'settings/log', array( 'log' => $log ) ); ?>
	</section>

	<section class="a4smap-panel" data-a4smap-panel="help">
		<h2><?php esc_html_e( 'Caption placeholders', 'social-media-auto-post' ); ?></h2>
		<table class="widefat striped a4smap-help-table">
			<tbody>
				<?php foreach ( CaptionBuilder::placeholders() as $placeholder => $placeholder_help ) : ?>
					<tr>
						<td><code><?php echo esc_html( $placeholder ); ?></code></td>
						<td><?php echo esc_html( $placeholder_help ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'Media from custom fields', 'social-media-auto-post' ); ?></h2>
		<p><?php esc_html_e( 'A custom field may hold an attachment ID, a comma separated list of IDs (like the WooCommerce gallery "_product_image_gallery"), a URL, a list of URLs, or an ACF image, gallery or file field. Every value is turned into the full URL of the file.', 'social-media-auto-post' ); ?></p>

		<h2><?php esc_html_e( 'For developers', 'social-media-auto-post' ); ?></h2>
		<ul class="a4smap-hooks">
			<li><code>a4smap_should_share</code> — <?php esc_html_e( 'filter whether a post is shared on publish.', 'social-media-auto-post' ); ?></li>
			<li><code>a4smap_media</code> — <?php esc_html_e( 'filter the images and video of a post.', 'social-media-auto-post' ); ?></li>
			<li><code>a4smap_caption</code> — <?php esc_html_e( 'filter the caption.', 'social-media-auto-post' ); ?></li>
			<li><code>a4smap_payload</code> — <?php esc_html_e( 'filter the whole Advert4 API request (e.g. to add TikTok options).', 'social-media-auto-post' ); ?></li>
			<li><code>a4smap_shared</code>, <code>a4smap_share_failed</code>, <code>a4smap_status_changed</code> — <?php esc_html_e( 'actions after sending and on status changes.', 'social-media-auto-post' ); ?></li>
		</ul>
	</section>
</div>
