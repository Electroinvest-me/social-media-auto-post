<?php
/**
 * Preview placeholder when the post type has no posts yet.
 *
 * @package Advert4\SocialMediaAutoPost
 */

use Advert4\SocialMediaAutoPost\Admin\Icons;

defined( 'ABSPATH' ) || exit;
?>
<div class="a4smap-empty">
	<?php Icons::render( 'image-plus', 26 ); ?>
	<?php esc_html_e( 'Write your first post of this type to see a live preview here.', 'social-media-auto-post' ); ?>
</div>
