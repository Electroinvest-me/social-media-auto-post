<?php
/**
 * Settings screen.
 *
 * @package Advert4\SocialMediaAutoPost
 *
 * @var \Advert4\SocialMediaAutoPost\Settings\Settings $settings
 * @var list<array<string, mixed>>                      $profiles
 * @var string                                          $profile_error
 * @var array<string, \WP_Post_Type>                    $post_types
 * @var array<string, list<string>>                     $meta_keys
 * @var list<array<string, mixed>>                      $log
 * @var array<string, int>                              $stats
 * @var list<array<string, mixed>>                      $recent
 * @var list<array<string, mixed>>                      $checklist
 * @var string                                          $app_url
 */

use Advert4\SocialMediaAutoPost\Admin\Icons;
use Advert4\SocialMediaAutoPost\Settings\Settings;
use Advert4\SocialMediaAutoPost\Support\Site;
use Advert4\SocialMediaAutoPost\Support\View;

defined( 'ABSPATH' ) || exit;

$is_connected  = $settings->has_api_key() && '' === $profile_error && array() !== $profiles;
$enabled_count = count( $settings->enabled_post_types() );
$error_count   = count(
	array_filter(
		$log,
		static function ( array $entry ): bool {
			return 'error' === ( $entry['level'] ?? '' ) && (int) ( $entry['time'] ?? 0 ) > time() - DAY_IN_SECONDS;
		}
	)
);

$settings_tabs = array(
	'overview'   => array( __( 'Overview', 'social-media-auto-post' ), 'layout-dashboard', 0 ),
	'connection' => array( __( 'Connection', 'social-media-auto-post' ), 'plug', 0 ),
	'post-types' => array( __( 'Post types', 'social-media-auto-post' ), 'layers', $enabled_count ),
	'advanced'   => array( __( 'Advanced', 'social-media-auto-post' ), 'sliders-horizontal', 0 ),
	'activity'   => array( __( 'Activity', 'social-media-auto-post' ), 'activity', $error_count ),
	'help'       => array( __( 'Help', 'social-media-auto-post' ), 'circle-help', 0 ),
);
?>
<div class="wrap a4smap-app" data-a4smap-app>
	<header class="a4smap-hero">
		<div class="a4smap-hero__top">
			<div class="a4smap-brand">
				<img class="a4smap-brand__logo" src="<?php echo esc_url( A4SMAP_URL . 'assets/images/advert4.svg' ); ?>" width="46" height="46" alt="" />
				<div>
					<h1 class="a4smap-brand__title"><?php esc_html_e( 'Social Media Auto Post', 'social-media-auto-post' ); ?></h1>
					<p class="a4smap-brand__subtitle"><?php esc_html_e( 'by Advert4 · Facebook, Instagram & TikTok', 'social-media-auto-post' ); ?></p>
				</div>
			</div>
			<div class="a4smap-hero__actions">
				<span class="a4smap-connection <?php echo $is_connected ? 'is-connected' : ''; ?>">
					<span class="a4smap-dot" aria-hidden="true"></span>
					<?php
					if ( $is_connected ) {
						echo esc_html(
							sprintf(
								/* translators: %d: number of Advert4 profiles */
								_n( 'Connected · %d profile', 'Connected · %d profiles', count( $profiles ), 'social-media-auto-post' ),
								count( $profiles )
							)
						);
					} else {
						esc_html_e( 'Not connected', 'social-media-auto-post' );
					}
					?>
				</span>
				<a class="a4smap-btn a4smap-btn--on-dark" href="<?php echo esc_url( $app_url ); ?>" target="_blank" rel="noopener">
					<?php esc_html_e( 'Open Advert4', 'social-media-auto-post' ); ?>
					<?php Icons::render( 'external-link', 14 ); ?>
				</a>
			</div>
		</div>

		<nav class="a4smap-nav" aria-label="<?php esc_attr_e( 'Settings sections', 'social-media-auto-post' ); ?>">
			<?php foreach ( $settings_tabs as $tab_id => $tab_info ) : ?>
				<a href="#<?php echo esc_attr( $tab_id ); ?>" class="a4smap-nav__tab" data-a4smap-tab="<?php echo esc_attr( $tab_id ); ?>">
					<?php Icons::render( $tab_info[1], 16 ); ?>
					<?php echo esc_html( $tab_info[0] ); ?>
					<?php if ( $tab_info[2] > 0 ) : ?>
						<span class="a4smap-nav__count"><?php echo esc_html( (string) $tab_info[2] ); ?></span>
					<?php endif; ?>
				</a>
			<?php endforeach; ?>
		</nav>
	</header>

	<hr class="wp-header-end" />

	<div class="a4smap-body">
		<?php settings_errors(); ?>

		<?php if ( ! Site::is_public() ) : ?>
			<div class="a4smap-callout">
				<?php Icons::render( 'triangle-alert', 20 ); ?>
				<div>
					<strong><?php esc_html_e( 'This site is not reachable from the internet', 'social-media-auto-post' ); ?></strong>
					<?php esc_html_e( 'Advert4 downloads your images and videos from this site, so posts can only be published once the site has a public address.', 'social-media-auto-post' ); ?>
				</div>
			</div>
		<?php endif; ?>

		<section class="a4smap-panel" data-a4smap-panel="overview">
			<?php
			View::render(
				'settings/overview',
				array(
					'stats'     => $stats,
					'recent'    => $recent,
					'checklist' => $checklist,
				)
			);
			?>
		</section>

		<form method="post" action="options.php" class="a4smap-form" data-a4smap-form>
			<?php settings_fields( Settings::GROUP ); ?>

			<section class="a4smap-panel" data-a4smap-panel="connection" hidden>
				<?php
				View::render(
					'settings/connection',
					array(
						'settings'      => $settings,
						'profiles'      => $profiles,
						'profile_error' => $profile_error,
						'app_url'       => $app_url,
					)
				);
				?>
			</section>

			<section class="a4smap-panel" data-a4smap-panel="post-types" hidden>
				<div class="a4smap-panel__head">
					<div>
						<h2 class="a4smap-panel__title"><?php esc_html_e( 'What to share', 'social-media-auto-post' ); ?></h2>
						<p class="a4smap-panel__lead"><?php esc_html_e( 'Turn on the content you want on social media and decide how each type is posted. Every post can still change these choices in the editor.', 'social-media-auto-post' ); ?></p>
					</div>
				</div>

				<?php $first_open = true; ?>
				<?php foreach ( $post_types as $type_slug => $type_object ) : ?>
					<?php
					$type_config = $settings->post_type( (string) $type_slug );

					View::render(
						'settings/post-type',
						array(
							'post_type' => $type_object,
							'config'    => $type_config,
							'profiles'  => $profiles,
							'meta_keys' => $meta_keys[ $type_slug ] ?? array(),
							'open'      => $first_open && $type_config->enabled,
						)
					);

					if ( $type_config->enabled ) {
						$first_open = false;
					}
					?>
				<?php endforeach; ?>
			</section>

			<section class="a4smap-panel" data-a4smap-panel="advanced" hidden>
				<?php View::render( 'settings/advanced', array( 'settings' => $settings ) ); ?>
			</section>

			<div class="a4smap-savebar" data-a4smap-savebar hidden>
				<span class="a4smap-savebar__state">
					<span class="a4smap-dot" aria-hidden="true"></span>
					<span data-a4smap-save-state data-dirty="<?php esc_attr_e( 'You have unsaved changes', 'social-media-auto-post' ); ?>"><?php esc_html_e( 'All changes saved', 'social-media-auto-post' ); ?></span>
				</span>
				<button type="submit" class="a4smap-btn a4smap-btn--accent">
					<?php Icons::render( 'check', 16 ); ?>
					<?php esc_html_e( 'Save changes', 'social-media-auto-post' ); ?>
				</button>
			</div>
		</form>

		<section class="a4smap-panel" data-a4smap-panel="activity" hidden>
			<?php View::render( 'settings/log', array( 'log' => $log ) ); ?>
		</section>

		<section class="a4smap-panel" data-a4smap-panel="help" hidden>
			<?php View::render( 'settings/help' ); ?>
		</section>
	</div>
</div>
