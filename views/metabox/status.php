<?php
/**
 * Sharing status in the meta box.
 *
 * @package Advert4\SocialMediaAutoPost
 *
 * @var \WP_Post                                         $post
 * @var \Advert4\SocialMediaAutoPost\Sharing\ShareState $state
 * @var bool                                             $can_share
 */

use Advert4\SocialMediaAutoPost\Enums\Network;
use Advert4\SocialMediaAutoPost\Sharing\ShareState;

defined( 'ABSPATH' ) || exit;

$is_published = 'publish' === $post->post_status;

if ( $state->has_remote() ) {
	$button_label = __( 'Share again', 'social-media-auto-post' );
} elseif ( $state->is_failed() ) {
	$button_label = __( 'Try again', 'social-media-auto-post' );
} else {
	$button_label = __( 'Share now', 'social-media-auto-post' );
}
?>
<div class="a4smap-status" data-in-progress="<?php echo $state->is_in_progress() ? '1' : '0'; ?>">
	<p class="a4smap-status__line">
		<span class="a4smap-badge a4smap-badge--<?php echo esc_attr( $state->tone() ); ?>"><?php echo esc_html( $state->label() ); ?></span>
		<?php if ( $state->shared_at > 0 ) : ?>
			<span class="a4smap-muted">
				<?php
				/* translators: %s: human readable time difference, e.g. "5 mins" */
				echo esc_html( sprintf( __( '%s ago', 'social-media-auto-post' ), human_time_diff( $state->shared_at ) ) );
				?>
			</span>
		<?php endif; ?>
	</p>

	<?php if ( '' !== $state->message ) : ?>
		<p class="a4smap-note a4smap-note--<?php echo $state->is_failed() ? 'error' : 'info'; ?>"><?php echo esc_html( $state->message ); ?></p>
	<?php endif; ?>

	<?php if ( array() !== $state->errors ) : ?>
		<ul class="a4smap-errors">
			<?php foreach ( $state->errors as $state_error ) : ?>
				<li><?php echo esc_html( $state_error ); ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( array() !== $state->targets ) : ?>
		<ul class="a4smap-targets">
			<?php foreach ( $state->targets as $target ) : ?>
				<li class="a4smap-target a4smap-target--<?php echo esc_attr( $target['status'] ); ?>">
					<span class="a4smap-net a4smap-net--<?php echo esc_attr( $target['network'] ); ?>"><?php echo esc_html( Network::label( $target['network'] ) ); ?></span>
					<span class="a4smap-target__status"><?php echo esc_html( ShareState::target_label( $target['status'] ) ); ?></span>
					<?php if ( '' !== $target['permalink'] ) : ?>
						<a href="<?php echo esc_url( $target['permalink'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View', 'social-media-auto-post' ); ?></a>
					<?php endif; ?>
					<?php if ( '' !== $target['error'] ) : ?>
						<span class="a4smap-target__error"><?php echo esc_html( $target['error'] ); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( $is_published && $can_share ) : ?>
		<p class="a4smap-actions">
			<?php if ( ! ( $state->is_in_progress() && $state->has_remote() ) && ShareState::SENDING !== $state->status ) : ?>
				<button type="button" class="button <?php echo $state->has_remote() ? '' : 'button-primary'; ?>" data-a4smap-share><?php echo esc_html( $button_label ); ?></button>
			<?php endif; ?>
			<?php if ( $state->has_remote() ) : ?>
				<button type="button" class="button-link" data-a4smap-refresh><?php esc_html_e( 'Refresh status', 'social-media-auto-post' ); ?></button>
			<?php endif; ?>
		</p>
	<?php elseif ( ! $is_published ) : ?>
		<p class="description"><?php esc_html_e( 'Nothing is sent before the post is published.', 'social-media-auto-post' ); ?></p>
	<?php endif; ?>
</div>
