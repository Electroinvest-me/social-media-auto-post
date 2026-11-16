<?php
/**
 * Runs when the plugin is deleted from the Plugins screen.
 *
 * Background jobs are always removed. Settings, the activity log and the
 * sharing history on posts are removed only when "Delete the settings and
 * sharing history when the plugin is deleted" is ticked.
 *
 * @package Advert4\SocialMediaAutoPost
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Clean up one site.
 */
function a4smap_uninstall_site(): void {
	wp_unschedule_hook( 'a4smap_publish' );
	wp_unschedule_hook( 'a4smap_refresh' );

	$settings = get_option( 'a4smap_settings' );

	if ( ! is_array( $settings ) || empty( $settings['delete_on_uninstall'] ) ) {
		return;
	}

	delete_option( 'a4smap_settings' );
	delete_option( 'a4smap_log' );
	delete_option( 'a4smap_secret' );
	delete_transient( 'a4smap_profiles' );

	foreach ( array( '_a4smap_overrides', '_a4smap_state', '_a4smap_shares', '_a4smap_lock' ) as $meta_key ) {
		delete_post_meta_by_key( $meta_key );
	}
}

if ( is_multisite() ) {
	foreach ( get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	) as $a4smap_site_id ) {
		switch_to_blog( (int) $a4smap_site_id );
		a4smap_uninstall_site();
		restore_current_blog();
	}
} else {
	a4smap_uninstall_site();
}
