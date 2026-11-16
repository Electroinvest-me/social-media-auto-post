<?php
/**
 * Preview of what will be sent.
 *
 * @package Advert4\SocialMediaAutoPost
 *
 * @var \Advert4\SocialMediaAutoPost\Sharing\SharePlan $plan
 */

use Advert4\SocialMediaAutoPost\Enums\Format;
use Advert4\SocialMediaAutoPost\Enums\Mode;
use Advert4\SocialMediaAutoPost\Enums\Network;

defined( 'ABSPATH' ) || exit;
?>
<div class="a4smap-preview">
	<?php foreach ( $plan->errors as $plan_error ) : ?>
		<p class="a4smap-note a4smap-note--error"><?php echo esc_html( $plan_error ); ?></p>
	<?php endforeach; ?>
	<?php foreach ( $plan->warnings as $warning ) : ?>
		<p class="a4smap-note a4smap-note--warning"><?php echo esc_html( $warning ); ?></p>
	<?php endforeach; ?>

	<dl class="a4smap-facts">
		<dt><?php esc_html_e( 'Format', 'social-media-auto-post' ); ?></dt>
		<dd><?php echo esc_html( Format::label( $plan->format ) ); ?></dd>
		<dt><?php esc_html_e( 'Networks', 'social-media-auto-post' ); ?></dt>
		<dd><?php echo esc_html( array() !== $plan->platforms ? implode( ', ', Network::label_list( $plan->platforms ) ) : __( 'Every account in the profile', 'social-media-auto-post' ) ); ?></dd>
		<dt><?php esc_html_e( 'Profile', 'social-media-auto-post' ); ?></dt>
		<dd><?php echo esc_html( '' !== $plan->profile ? $plan->profile : '—' ); ?></dd>
		<dt><?php esc_html_e( 'In Advert4', 'social-media-auto-post' ); ?></dt>
		<dd><?php echo esc_html( Mode::label( $plan->mode ) ); ?></dd>
	</dl>

	<?php if ( array() !== $plan->media ) : ?>
		<ul class="a4smap-thumbs">
			<?php foreach ( $plan->media as $item ) : ?>
				<li>
					<a href="<?php echo esc_url( $item->url ); ?>" target="_blank" rel="noopener" title="<?php echo esc_attr( $item->url ); ?>">
						<?php if ( '' !== $item->thumbnail() ) : ?>
							<img src="<?php echo esc_url( $item->thumbnail() ); ?>" alt="<?php echo esc_attr( $item->filename() ); ?>" />
						<?php else : ?>
							<span class="dashicons dashicons-format-video" aria-hidden="true"></span>
						<?php endif; ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( '' !== $plan->caption ) : ?>
		<div class="a4smap-caption"><?php echo esc_html( $plan->caption ); ?></div>
	<?php endif; ?>
</div>
