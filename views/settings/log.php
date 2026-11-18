<?php
/**
 * Activity tab.
 *
 * @package Advert4\SocialMediaAutoPost
 *
 * @var list<array<string, mixed>> $log
 */

use Advert4\SocialMediaAutoPost\Admin\Icons;

defined( 'ABSPATH' ) || exit;

$level_icons = array(
	'info'    => 'circle-check',
	'warning' => 'triangle-alert',
	'error'   => 'circle-x',
);
?>
<div class="a4smap-panel__head">
	<div>
		<h2 class="a4smap-panel__title"><?php esc_html_e( 'Activity', 'social-media-auto-post' ); ?></h2>
		<p class="a4smap-panel__lead"><?php esc_html_e( 'Every post sent, every retry and every error, newest first.', 'social-media-auto-post' ); ?></p>
	</div>
	<?php if ( array() !== $log ) : ?>
		<div class="a4smap-hero__actions">
			<div class="a4smap-segmented" role="radiogroup" aria-label="<?php esc_attr_e( 'Filter', 'social-media-auto-post' ); ?>">
				<label><input type="radio" name="a4smap_log_filter" value="all" data-a4smap-log-filter checked /><span><?php esc_html_e( 'All', 'social-media-auto-post' ); ?></span></label>
				<label><input type="radio" name="a4smap_log_filter" value="error" data-a4smap-log-filter /><span><?php esc_html_e( 'Errors', 'social-media-auto-post' ); ?></span></label>
				<label><input type="radio" name="a4smap_log_filter" value="warning" data-a4smap-log-filter /><span><?php esc_html_e( 'Warnings', 'social-media-auto-post' ); ?></span></label>
			</div>
			<button type="button" class="a4smap-btn a4smap-btn--ghost" data-a4smap-clear-log><?php esc_html_e( 'Clear log', 'social-media-auto-post' ); ?></button>
		</div>
	<?php endif; ?>
</div>

<div class="a4smap-card" data-a4smap-log>
	<div class="a4smap-card__body">
		<?php if ( array() === $log ) : ?>
			<div class="a4smap-empty">
				<?php Icons::render( 'activity', 26 ); ?>
				<?php esc_html_e( 'Nothing has happened yet. Sent posts, retries and errors will appear here.', 'social-media-auto-post' ); ?>
			</div>
		<?php else : ?>
			<ol class="a4smap-timeline">
				<?php foreach ( $log as $entry ) : ?>
					<?php
					$entry_level   = (string) ( $entry['level'] ?? 'info' );
					$entry_post_id = (int) ( $entry['post_id'] ?? 0 );
					$entry_time    = (int) ( $entry['time'] ?? 0 );
					?>
					<li class="a4smap-event a4smap-event--<?php echo esc_attr( $entry_level ); ?>" data-level="<?php echo esc_attr( $entry_level ); ?>">
						<span class="a4smap-event__icon"><?php Icons::render( $level_icons[ $entry_level ] ?? 'info', 17 ); ?></span>
						<div>
							<?php if ( $entry_post_id > 0 && get_post( $entry_post_id ) ) : ?>
								<a class="a4smap-event__post" href="<?php echo esc_url( (string) get_edit_post_link( $entry_post_id ) ); ?>"><?php echo esc_html( get_the_title( $entry_post_id ) ?: '#' . $entry_post_id ); ?></a>
							<?php elseif ( $entry_post_id > 0 ) : ?>
								<span class="a4smap-event__post">#<?php echo esc_html( (string) $entry_post_id ); ?></span>
							<?php endif; ?>
							<p class="a4smap-event__message"><?php echo esc_html( (string) ( $entry['message'] ?? '' ) ); ?></p>
						</div>
						<time class="a4smap-event__time" datetime="<?php echo esc_attr( gmdate( 'c', $entry_time ) ); ?>" title="<?php echo esc_attr( (string) wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $entry_time ) ); ?>">
							<?php
							/* translators: %s: time difference, e.g. "5 mins" */
							echo esc_html( sprintf( __( '%s ago', 'social-media-auto-post' ), human_time_diff( $entry_time ) ) );
							?>
						</time>
					</li>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>
	</div>
</div>
