<?php
/**
 * Advert4 profiles and their connected accounts.
 *
 * @package Advert4\SocialMediaAutoPost
 *
 * @var list<array<string, mixed>> $profiles
 */

use Advert4\SocialMediaAutoPost\Admin\Icons;
use Advert4\SocialMediaAutoPost\Enums\Network;

defined( 'ABSPATH' ) || exit;
?>
<?php if ( array() === $profiles ) : ?>
	<p class="a4smap-note a4smap-note--warning"><?php Icons::render( 'triangle-alert', 15 ); ?><span><?php esc_html_e( 'Connected, but this workspace has no profiles yet. Create one in Advert4 and connect your accounts to it.', 'social-media-auto-post' ); ?></span></p>
<?php else : ?>
	<p class="a4smap-note a4smap-note--success">
		<?php Icons::render( 'circle-check', 15 ); ?>
		<span>
			<?php
			echo esc_html(
				sprintf(
					/* translators: %d: number of profiles */
					_n( 'Connected — %d profile found.', 'Connected — %d profiles found.', count( $profiles ), 'social-media-auto-post' ),
					count( $profiles )
				)
			);
			?>
		</span>
	</p>
	<div class="a4smap-profiles">
		<?php foreach ( $profiles as $profile ) : ?>
			<div class="a4smap-profile">
				<div class="a4smap-profile__head">
					<span class="a4smap-avatar" aria-hidden="true"><?php echo esc_html( substr( (string) $profile['name'], 0, 1 ) ); ?></span>
					<div>
						<strong><?php echo esc_html( (string) $profile['name'] ); ?></strong>
						<div class="a4smap-muted"><?php echo esc_html( (string) $profile['slug'] ); ?></div>
					</div>
				</div>
				<?php if ( array() === $profile['accounts'] ) : ?>
					<span class="a4smap-muted"><?php esc_html_e( 'No accounts connected yet.', 'social-media-auto-post' ); ?></span>
				<?php endif; ?>
				<?php foreach ( (array) $profile['accounts'] as $account ) : ?>
					<div class="a4smap-account <?php echo 'active' === $account['status'] ? '' : 'is-inactive'; ?>">
						<?php Icons::render_network( (string) $account['provider'], 20, true ); ?>
						<span>
							<strong><?php echo esc_html( (string) $account['name'] ); ?></strong>
							<span class="a4smap-muted"><?php echo esc_html( Network::label( (string) $account['provider'] ) ); ?><?php echo 'active' === $account['status'] ? '' : ' · ' . esc_html( (string) $account['status'] ); ?></span>
						</span>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
