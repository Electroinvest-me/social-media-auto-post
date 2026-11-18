<?php
/**
 * Help tab.
 *
 * @package Advert4\SocialMediaAutoPost
 */

use Advert4\SocialMediaAutoPost\Admin\Icons;
use Advert4\SocialMediaAutoPost\Content\CaptionBuilder;

defined( 'ABSPATH' ) || exit;

$developer_hooks = array(
	'a4smap_should_share'   => __( 'Filter whether a post is shared when it is published.', 'social-media-auto-post' ),
	'a4smap_media'          => __( 'Filter the images and video of a post.', 'social-media-auto-post' ),
	'a4smap_caption'        => __( 'Filter the caption.', 'social-media-auto-post' ),
	'a4smap_payload'        => __( 'Filter the whole Advert4 API request, e.g. to add TikTok options.', 'social-media-auto-post' ),
	'a4smap_shared'         => __( 'Action after Advert4 accepted a post.', 'social-media-auto-post' ),
	'a4smap_share_failed'   => __( 'Action when a post could not be sent.', 'social-media-auto-post' ),
	'a4smap_status_changed' => __( 'Action when the status on the networks changes.', 'social-media-auto-post' ),
);
?>
<div class="a4smap-panel__head">
	<div>
		<h2 class="a4smap-panel__title"><?php esc_html_e( 'Help', 'social-media-auto-post' ); ?></h2>
		<p class="a4smap-panel__lead"><?php esc_html_e( 'Placeholders for captions, supported custom fields and hooks for developers.', 'social-media-auto-post' ); ?></p>
	</div>
</div>

<div class="a4smap-card">
	<div class="a4smap-card__head">
		<span class="a4smap-badge-icon"><?php Icons::render( 'file-pen-line', 20 ); ?></span>
		<div>
			<h3 class="a4smap-card__title"><?php esc_html_e( 'Caption placeholders', 'social-media-auto-post' ); ?></h3>
			<p class="a4smap-card__text"><?php esc_html_e( 'Use them in caption templates and in the caption of a single post.', 'social-media-auto-post' ); ?></p>
		</div>
	</div>
	<div class="a4smap-card__body">
		<div class="a4smap-help-grid">
			<?php foreach ( CaptionBuilder::placeholders() as $placeholder => $placeholder_help ) : ?>
				<div class="a4smap-help-item">
					<div>
						<code><?php echo esc_html( $placeholder ); ?></code>
						<span><?php echo esc_html( $placeholder_help ); ?></span>
					</div>
					<button type="button" class="a4smap-copy" data-a4smap-copy="<?php echo esc_attr( $placeholder ); ?>" aria-label="<?php esc_attr_e( 'Copy', 'social-media-auto-post' ); ?>"><?php Icons::render( 'copy', 15 ); ?></button>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>

<div class="a4smap-grid a4smap-grid--2">
	<div class="a4smap-card">
		<div class="a4smap-card__head">
			<span class="a4smap-badge-icon"><?php Icons::render( 'images', 20 ); ?></span>
			<div>
				<h3 class="a4smap-card__title"><?php esc_html_e( 'Media from custom fields', 'social-media-auto-post' ); ?></h3>
				<p class="a4smap-card__text"><?php esc_html_e( 'Every value is turned into the full URL of the file.', 'social-media-auto-post' ); ?></p>
			</div>
		</div>
		<div class="a4smap-card__body">
			<ul class="a4smap-list">
				<li class="a4smap-list__item"><code class="a4smap-code">123</code><span class="a4smap-muted"><?php esc_html_e( 'An attachment ID', 'social-media-auto-post' ); ?></span></li>
				<li class="a4smap-list__item"><code class="a4smap-code">12,34,56</code><span class="a4smap-muted"><?php esc_html_e( 'A list of IDs, like the WooCommerce gallery', 'social-media-auto-post' ); ?></span></li>
				<li class="a4smap-list__item"><code class="a4smap-code">https://…/photo.jpg</code><span class="a4smap-muted"><?php esc_html_e( 'A URL or a path in the uploads folder', 'social-media-auto-post' ); ?></span></li>
				<li class="a4smap-list__item"><code class="a4smap-code">ACF</code><span class="a4smap-muted"><?php esc_html_e( 'Image, gallery and file fields', 'social-media-auto-post' ); ?></span></li>
			</ul>
		</div>
	</div>

	<div class="a4smap-card">
		<div class="a4smap-card__head">
			<span class="a4smap-badge-icon"><?php Icons::render( 'sliders-horizontal', 20 ); ?></span>
			<div>
				<h3 class="a4smap-card__title"><?php esc_html_e( 'For developers', 'social-media-auto-post' ); ?></h3>
				<p class="a4smap-card__text"><?php esc_html_e( 'Filters and actions to extend the plugin.', 'social-media-auto-post' ); ?></p>
			</div>
		</div>
		<div class="a4smap-card__body">
			<ul class="a4smap-list">
				<?php foreach ( $developer_hooks as $hook_name => $hook_help ) : ?>
					<li class="a4smap-list__item">
						<span class="a4smap-list__main">
							<code class="a4smap-code"><?php echo esc_html( $hook_name ); ?></code>
							<span class="a4smap-muted" style="display:block;margin-top:4px"><?php echo esc_html( $hook_help ); ?></span>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</div>
