<?php
/**
 * Admin CSS and JavaScript.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues the shared admin assets with their runtime data.
 */
final class Assets {

	public const HANDLE = 'a4smap-admin';

	/**
	 * Enqueue the admin CSS and JavaScript.
	 *
	 * @param array<string, mixed> $data Data for the script (window.a4smap).
	 */
	public static function enqueue( array $data = array() ): void {
		wp_enqueue_style( self::HANDLE, A4SMAP_URL . 'assets/css/admin.css', array(), A4SMAP_VERSION );
		wp_enqueue_script( self::HANDLE, A4SMAP_URL . 'assets/js/admin.js', array( 'jquery' ), A4SMAP_VERSION, true );

		wp_localize_script(
			self::HANDLE,
			'a4smap',
			array_merge(
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'i18n'    => array(
						'working'      => __( 'Working…', 'social-media-auto-post' ),
						'failed'       => __( 'Something went wrong. Please try again.', 'social-media-auto-post' ),
						'chooseMedia'  => __( 'Choose images or a video', 'social-media-auto-post' ),
						'useMedia'     => __( 'Use these files', 'social-media-auto-post' ),
						'notInProfile' => __( 'Not connected in this profile', 'social-media-auto-post' ),
						'confirmLog'   => __( 'Clear the activity log?', 'social-media-auto-post' ),
					),
				),
				$data
			)
		);
	}
}
