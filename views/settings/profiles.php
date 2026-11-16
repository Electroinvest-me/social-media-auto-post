<?php
/**
 * Advert4 profiles and their connected accounts.
 *
 * @package Advert4\SocialMediaAutoPost
 *
 * @var list<array<string, mixed>> $profiles
 */

defined( 'ABSPATH' ) || exit;
?>
<?php if ( array() === $profiles ) : ?>
	<div class="notice notice-warning inline">
		<p><?php esc_html_e( 'Connected, but this workspace has no profiles yet. Create one in Advert4 and connect your accounts to it.', 'social-media-auto-post' ); ?></p>
	</div>
<?php else : ?>
	<p class="a4smap-ok">
		<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
		<?php
		echo esc_html(
			sprintf(
				/* translators: %d: number of profiles */
				_n( 'Connected — %d profile found.', 'Connected — %d profiles found.', count( $profiles ), 'social-media-auto-post' ),
				count( $profiles )
			)
		);
		?>
	</p>
	<ul class="a4smap-profile-list">
		<?php foreach ( $profiles as $profile ) : ?>
			<li>
				<strong><?php echo esc_html( (string) $profile['name'] ); ?></strong>
				<code><?php echo esc_html( (string) $profile['slug'] ); ?></code>
				<?php if ( array() === $profile['accounts'] ) : ?>
					<em><?php esc_html_e( 'no connected accounts', 'social-media-auto-post' ); ?></em>
				<?php endif; ?>
				<?php foreach ( (array) $profile['accounts'] as $account ) : ?>
					<span class="a4smap-net a4smap-net--<?php echo esc_attr( (string) $account['provider'] ); ?> <?php echo 'active' === $account['status'] ? '' : 'is-unavailable'; ?>" title="<?php echo esc_attr( (string) $account['status'] ); ?>">
						<?php echo esc_html( ( '' !== $account['provider_label'] ? $account['provider_label'] : ucfirst( (string) $account['provider'] ) ) . ': ' . $account['name'] ); ?>
					</span>
				<?php endforeach; ?>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
