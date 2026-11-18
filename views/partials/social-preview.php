<?php
/**
 * Preview of a post as it will look on the networks.
 *
 * @package Advert4\SocialMediaAutoPost
 *
 * @var \Advert4\SocialMediaAutoPost\Sharing\SharePlan $plan
 * @var list<string>                                    $networks
 * @var string                                          $profile_name
 * @var \WP_Post|null                                   $source_post
 */

use Advert4\SocialMediaAutoPost\Admin\Icons;
use Advert4\SocialMediaAutoPost\Enums\Format;
use Advert4\SocialMediaAutoPost\Enums\Mode;

defined( 'ABSPATH' ) || exit;

$media_count = count( $plan->media );
$first_item  = $plan->media[0] ?? null;

if ( Format::STORY === $plan->format ) {
	$layout = 'story';
} elseif ( null !== $first_item && $first_item->is_video() ) {
	$layout = 'video';
} else {
	$layout = (string) min( 4, max( 1, $media_count ) );
}

$visible_media  = in_array( $layout, array( 'story', 'video' ), true ) ? array_slice( $plan->media, 0, 1 ) : array_slice( $plan->media, 0, 4 );
$hidden_count   = $media_count - count( $visible_media );
$display_name   = '' !== $profile_name ? $profile_name : get_bloginfo( 'name' );
$caption_length = function_exists( 'mb_strlen' ) ? mb_strlen( $plan->caption ) : strlen( $plan->caption );
$is_over_limit  = $plan->caption_limit > 0 && $caption_length > $plan->caption_limit;
?>
<div class="a4smap-preview">
	<?php foreach ( $plan->errors as $plan_error ) : ?>
		<p class="a4smap-note a4smap-note--error"><?php Icons::render( 'circle-x', 15 ); ?><span><?php echo esc_html( $plan_error ); ?></span></p>
	<?php endforeach; ?>
	<?php foreach ( $plan->warnings as $plan_warning ) : ?>
		<p class="a4smap-note a4smap-note--warning"><?php Icons::render( 'triangle-alert', 15 ); ?><span><?php echo esc_html( $plan_warning ); ?></span></p>
	<?php endforeach; ?>

	<article class="a4smap-social" aria-label="<?php esc_attr_e( 'Preview of the social media post', 'social-media-auto-post' ); ?>">
		<header class="a4smap-social__head">
			<span class="a4smap-avatar" aria-hidden="true"><?php echo esc_html( function_exists( 'mb_substr' ) ? mb_substr( $display_name, 0, 1 ) : substr( $display_name, 0, 1 ) ); ?></span>
			<span class="a4smap-social__who">
				<strong><?php echo esc_html( $display_name ); ?></strong>
				<span>
					<?php echo esc_html( Format::label( $plan->format ) ); ?>
					·
					<?php echo esc_html( Mode::DRAFT === $plan->mode ? __( 'Draft in Advert4', 'social-media-auto-post' ) : __( 'Just now', 'social-media-auto-post' ) ); ?>
				</span>
			</span>
			<span class="a4smap-nets">
				<?php foreach ( $networks as $network ) : ?>
					<?php Icons::render_network( $network, 20, true ); ?>
				<?php endforeach; ?>
			</span>
		</header>

		<?php if ( '' !== $plan->caption ) : ?>
			<p class="a4smap-social__caption" data-a4smap-caption><?php echo esc_html( $plan->caption ); ?></p>
			<button type="button" class="a4smap-social__more" data-a4smap-more hidden><?php esc_html_e( 'See more', 'social-media-auto-post' ); ?></button>
		<?php endif; ?>

		<?php if ( array() !== $visible_media ) : ?>
			<div class="a4smap-media a4smap-media--<?php echo esc_attr( $layout ); ?>">
				<?php foreach ( $visible_media as $media_index => $item ) : ?>
					<a class="a4smap-media__item" href="<?php echo esc_url( $item->url ); ?>" target="_blank" rel="noopener" title="<?php echo esc_attr( $item->url ); ?>">
						<?php if ( '' !== $item->thumbnail() ) : ?>
							<img src="<?php echo esc_url( $item->thumbnail() ); ?>" alt="<?php echo esc_attr( $item->filename() ); ?>" loading="lazy" />
						<?php endif; ?>
						<?php if ( $item->is_video() ) : ?>
							<span class="a4smap-media__play"><span><?php Icons::render( 'play', 22 ); ?></span></span>
						<?php endif; ?>
						<?php if ( 3 === $media_index && $hidden_count > 0 ) : ?>
							<span class="a4smap-media__more">+<?php echo esc_html( (string) $hidden_count ); ?></span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<footer class="a4smap-social__foot">
			<span class="<?php echo $is_over_limit ? 'is-over' : ''; ?>">
				<?php
				echo esc_html(
					$plan->caption_limit > 0
						/* translators: 1: caption length, 2: maximum length */
						? sprintf( __( '%1$s / %2$s characters', 'social-media-auto-post' ), number_format_i18n( $caption_length ), number_format_i18n( $plan->caption_limit ) )
						/* translators: %s: caption length */
						: sprintf( __( '%s characters', 'social-media-auto-post' ), number_format_i18n( $caption_length ) )
				);
				?>
			</span>
			<span>
				<?php
				/* translators: %d: number of images and videos */
				echo esc_html( sprintf( _n( '%d media file', '%d media files', $media_count, 'social-media-auto-post' ), $media_count ) );
				?>
			</span>
		</footer>
	</article>

	<?php if ( isset( $source_post ) && $source_post instanceof WP_Post ) : ?>
		<p class="a4smap-preview__source">
			<?php
			/* translators: %s: post title */
			echo esc_html( sprintf( __( 'Preview with your latest post: “%s”', 'social-media-auto-post' ), get_the_title( $source_post ) ) );
			?>
		</p>
	<?php endif; ?>
</div>
