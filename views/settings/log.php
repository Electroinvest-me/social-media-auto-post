<?php
/**
 * Activity log table.
 *
 * @package Advert4\SocialMediaAutoPost
 *
 * @var list<array<string, mixed>> $log
 */

defined( 'ABSPATH' ) || exit;

$date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
?>
<div data-a4smap-log>
	<?php if ( array() === $log ) : ?>
		<p class="description"><?php esc_html_e( 'Nothing has been shared yet. Sent posts, retries and errors appear here.', 'social-media-auto-post' ); ?></p>
	<?php else : ?>
		<p>
			<button type="button" class="button" data-a4smap-clear-log><?php esc_html_e( 'Clear log', 'social-media-auto-post' ); ?></button>
		</p>
		<table class="widefat striped a4smap-log">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'When', 'social-media-auto-post' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Post', 'social-media-auto-post' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Message', 'social-media-auto-post' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $log as $entry ) : ?>
					<?php $entry_post_id = (int) ( $entry['post_id'] ?? 0 ); ?>
					<tr class="a4smap-log--<?php echo esc_attr( (string) ( $entry['level'] ?? 'info' ) ); ?>">
						<td class="a4smap-log__time"><?php echo esc_html( (string) wp_date( $date_format, (int) ( $entry['time'] ?? 0 ) ) ); ?></td>
						<td>
							<?php if ( $entry_post_id > 0 && get_post( $entry_post_id ) ) : ?>
								<a href="<?php echo esc_url( (string) get_edit_post_link( $entry_post_id ) ); ?>"><?php echo esc_html( get_the_title( $entry_post_id ) ?: '#' . $entry_post_id ); ?></a>
							<?php elseif ( $entry_post_id > 0 ) : ?>
								#<?php echo esc_html( (string) $entry_post_id ); ?>
							<?php endif; ?>
						</td>
						<td><span class="a4smap-level"><?php echo esc_html( (string) ( $entry['level'] ?? '' ) ); ?></span> <?php echo esc_html( (string) ( $entry['message'] ?? '' ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
