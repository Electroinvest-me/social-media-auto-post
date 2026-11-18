<?php
/**
 * Overview tab: setup checklist, numbers and recent posts.
 *
 * @package Advert4\SocialMediaAutoPost
 *
 * @var array<string, int>          $stats
 * @var list<array<string, mixed>> $recent
 * @var list<array<string, mixed>> $checklist
 */

use Advert4\SocialMediaAutoPost\Admin\Icons;
use Advert4\SocialMediaAutoPost\Sharing\ShareState;

defined( 'ABSPATH' ) || exit;

$done_steps = count(
	array_filter(
		$checklist,
		static function ( array $step ): bool {
			return (bool) $step['done'];
		}
	)
);
$all_done   = count( $checklist ) === $done_steps;

$tiles = array(
	array( __( 'Shared in the last 30 days', 'social-media-auto-post' ), $stats['shared_30'], 'send', true ),
	array( __( 'Live on social media', 'social-media-auto-post' ), $stats['published'], 'circle-check', false ),
	array( __( 'In progress', 'social-media-auto-post' ), $stats['in_progress'], 'clock', false ),
	array( __( 'Need attention', 'social-media-auto-post' ), $stats['attention'], 'triangle-alert', false ),
);
?>
<div class="a4smap-grid a4smap-grid--4">
	<?php foreach ( $tiles as $tile ) : ?>
		<div class="a4smap-stat <?php echo $tile[3] ? 'a4smap-stat--feature' : ''; ?>">
			<span class="a4smap-badge-icon"><?php Icons::render( $tile[2], 18 ); ?></span>
			<span class="a4smap-stat__label"><?php echo esc_html( $tile[0] ); ?></span>
			<span class="a4smap-stat__value"><?php echo esc_html( number_format_i18n( (int) $tile[1] ) ); ?></span>
		</div>
	<?php endforeach; ?>
</div>

<div class="a4smap-grid a4smap-grid--2" style="margin-top:18px">
	<div class="a4smap-card">
		<div class="a4smap-card__head">
			<span class="a4smap-badge-icon"><?php Icons::render( $all_done ? 'circle-check' : 'sparkles', 20 ); ?></span>
			<div>
				<h2 class="a4smap-card__title"><?php echo esc_html( $all_done ? __( 'You are all set', 'social-media-auto-post' ) : __( 'Get started', 'social-media-auto-post' ) ); ?></h2>
				<p class="a4smap-card__text">
					<?php
					/* translators: 1: completed steps, 2: total steps */
					echo esc_html( sprintf( __( '%1$d of %2$d steps done', 'social-media-auto-post' ), $done_steps, count( $checklist ) ) );
					?>
				</p>
			</div>
		</div>
		<div class="a4smap-card__body">
			<div class="a4smap-progress" role="progressbar" aria-valuemin="0" aria-valuemax="<?php echo esc_attr( (string) count( $checklist ) ); ?>" aria-valuenow="<?php echo esc_attr( (string) $done_steps ); ?>">
				<span style="width: <?php echo esc_attr( (string) round( 100 * $done_steps / max( 1, count( $checklist ) ) ) ); ?>%"></span>
			</div>
			<ol class="a4smap-steps">
				<?php foreach ( $checklist as $step_index => $step ) : ?>
					<li class="a4smap-step <?php echo $step['done'] ? 'is-done' : ''; ?>">
						<span class="a4smap-step__mark">
							<?php if ( $step['done'] ) : ?>
								<?php Icons::render( 'check', 14 ); ?>
							<?php else : ?>
								<?php echo esc_html( (string) ( $step_index + 1 ) ); ?>
							<?php endif; ?>
						</span>
						<span class="a4smap-step__body">
							<span class="a4smap-step__title"><?php echo esc_html( (string) $step['title'] ); ?></span>
							<span class="a4smap-muted"><?php echo esc_html( (string) $step['text'] ); ?></span>
						</span>
						<?php if ( ! $step['done'] ) : ?>
							<a class="a4smap-btn a4smap-btn--ghost" href="#<?php echo esc_attr( (string) $step['tab'] ); ?>" data-a4smap-tab="<?php echo esc_attr( (string) $step['tab'] ); ?>"><?php esc_html_e( 'Open', 'social-media-auto-post' ); ?></a>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</div>

	<div class="a4smap-card">
		<div class="a4smap-card__head">
			<span class="a4smap-badge-icon"><?php Icons::render( 'activity', 20 ); ?></span>
			<div>
				<h2 class="a4smap-card__title"><?php esc_html_e( 'Recently shared', 'social-media-auto-post' ); ?></h2>
				<p class="a4smap-card__text"><?php esc_html_e( 'The latest posts and how they did on each network.', 'social-media-auto-post' ); ?></p>
			</div>
		</div>
		<div class="a4smap-card__body">
			<?php if ( array() === $recent ) : ?>
				<div class="a4smap-empty">
					<?php Icons::render( 'send', 26 ); ?>
					<?php esc_html_e( 'Nothing shared yet. Publish a post and it will show up here.', 'social-media-auto-post' ); ?>
				</div>
			<?php else : ?>
				<ul class="a4smap-list">
					<?php foreach ( $recent as $row ) : ?>
						<?php
						/**
						 * Recent row.
						 *
						 * @var \WP_Post $row_post
						 * @var ShareState $row_state
						 */
						$row_post  = $row['post'];
						$row_state = $row['state'];
						?>
						<li class="a4smap-list__item">
							<span class="a4smap-list__main">
								<a class="a4smap-list__title" href="<?php echo esc_url( (string) get_edit_post_link( $row_post->ID ) ); ?>"><?php echo esc_html( get_the_title( $row_post ) ?: __( '(no title)', 'social-media-auto-post' ) ); ?></a>
								<span class="a4smap-muted">
									<?php
									echo esc_html(
										$row_state->shared_at > 0
											/* translators: %s: time difference, e.g. "5 mins" */
											? sprintf( __( '%s ago', 'social-media-auto-post' ), human_time_diff( $row_state->shared_at ) )
											: $row_state->label()
									);
									?>
								</span>
							</span>
							<span class="a4smap-nets">
								<?php foreach ( $row_state->targets as $target ) : ?>
									<span class="a4smap-net-status a4smap-net-status--<?php echo esc_attr( $target['status'] ); ?>" title="<?php echo esc_attr( ShareState::target_label( $target['status'] ) ); ?>">
										<?php Icons::render_network( $target['network'], 22, true ); ?>
									</span>
								<?php endforeach; ?>
							</span>
							<span class="a4smap-badge a4smap-badge--<?php echo esc_attr( $row_state->tone() ); ?>"><?php echo esc_html( $row_state->label() ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
</div>
