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

use Advert4\SocialMediaAutoPost\Admin\Icons;
use Advert4\SocialMediaAutoPost\Enums\Network;
use Advert4\SocialMediaAutoPost\Sharing\ShareState;

defined( 'ABSPATH' ) || exit;

$is_published = 'publish' === $post->post_status;
$can_click    = $is_published && $can_share && ! ( $state->is_in_progress() && $state->has_remote() ) && ShareState::SENDING !== $state->status;

if ( $state->has_remote() ) {
	$button = array( __( 'Share again', 'social-media-auto-post' ), 'refresh-cw', 'a4smap-btn--ghost' );
} elseif ( $state->is_failed() ) {
	$button = array( __( 'Try again', 'social-media-auto-post' ), 'refresh-cw', 'a4smap-btn--accent' );
} else {
	$button = array( __( 'Share now', 'social-media-auto-post' ), 'send', 'a4smap-btn--accent' );
}
?>
<div class="a4smap-status" data-in-progress="<?php echo $state->is_in_progress() ? '1' : '0'; ?>">
	<div class="a4smap-box__status">
		<span class="a4smap-badge a4smap-badge--<?php echo esc_attr( $state->tone() ); ?>"><?php echo esc_html( $state->label() ); ?></span>
		<?php if ( $state->shared_at > 0 ) : ?>
			<span class="a4smap-muted">
				<?php
				/* translators: %s: time difference, e.g. "5 mins" */
				echo esc_html( sprintf( __( '%s ago', 'social-media-auto-post' ), human_time_diff( $state->shared_at ) ) );
				?>
			</span>
		<?php endif; ?>
	</div>

	<?php if ( '' !== $state->message ) : ?>
		<p class="a4smap-note a4smap-note--<?php echo $state->is_failed() ? 'error' : 'warning'; ?>">
			<?php Icons::render( $state->is_failed() ? 'circle-x' : 'clock', 15 ); ?>
			<span><?php echo esc_html( $state->message ); ?></span>
		</p>
	<?php endif; ?>

	<?php if ( array() !== $state->errors ) : ?>
		<ul class="a4smap-note a4smap-note--error" style="display:block">
			<?php foreach ( $state->errors as $state_error ) : ?>
				<li><?php echo esc_html( $state_error ); ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( array() !== $state->targets ) : ?>
		<ul class="a4smap-results">
			<?php foreach ( $state->targets as $target ) : ?>
				<li class="a4smap-result">
					<span class="a4smap-net-status a4smap-net-status--<?php echo esc_attr( $target['status'] ); ?>"><?php Icons::render_network( $target['network'], 22, true ); ?></span>
					<span>
						<span class="a4smap-result__name"><?php echo esc_html( '' !== $target['account'] ? $target['account'] : Network::label( $target['network'] ) ); ?></span>
						<span class="a4smap-muted"><?php echo esc_html( ShareState::target_label( $target['status'] ) ); ?></span>
					</span>
					<?php if ( '' !== $target['permalink'] ) : ?>
						<a href="<?php echo esc_url( $target['permalink'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View', 'social-media-auto-post' ); ?><?php Icons::render( 'external-link', 12 ); ?></a>
					<?php else : ?>
						<span></span>
					<?php endif; ?>
					<?php if ( '' !== $target['error'] ) : ?>
						<span class="a4smap-result__error"><?php echo esc_html( $target['error'] ); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( $is_published && $can_share ) : ?>
		<div class="a4smap-box__actions">
			<?php if ( $can_click ) : ?>
				<button type="button" class="a4smap-btn a4smap-btn--block <?php echo esc_attr( $button[2] ); ?>" data-a4smap-share>
					<?php Icons::render( $button[1], 15 ); ?>
					<span><?php echo esc_html( $button[0] ); ?></span>
				</button>
			<?php endif; ?>
			<?php if ( $state->has_remote() ) : ?>
				<button type="button" class="a4smap-btn a4smap-btn--link" data-a4smap-refresh><?php Icons::render( 'refresh-cw', 13 ); ?><?php esc_html_e( 'Refresh status', 'social-media-auto-post' ); ?></button>
			<?php endif; ?>
		</div>
	<?php elseif ( ! $is_published ) : ?>
		<p class="a4smap-hint" style="margin-bottom:12px"><?php esc_html_e( 'Nothing is sent before the post is published.', 'social-media-auto-post' ); ?></p>
	<?php endif; ?>
</div>
