=== Social Media Auto Post ===
Contributors: electroinvest
Tags: auto post, social media, facebook, instagram, tiktok
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Auto post WordPress posts, images, galleries and videos to Facebook, Instagram and TikTok – automatically on publish or only when you choose.

== Description ==

**Social Media Auto Post** shares your WordPress content to your Facebook Page, Instagram and TikTok accounts the moment you publish. It works with posts, pages, WooCommerce products and any custom post type, and sends a single image, a gallery (carousel), a video (Reel) or a Story – whatever fits the post.

Publishing goes through [Advert4](https://advert4.me), a content calendar that connects to the official Meta and TikTok APIs. Your WordPress site never stores social network passwords or tokens.

= What it does =

* **Auto post on publish** – or share only the posts where you tick "Share" in the editor.
* **Per post type settings** – choose which post types are shared, to which Advert4 profile and to which networks.
* **Images, galleries, videos and Stories** – the format can be chosen per post type and per post, or picked automatically (video → Reel, several images → carousel, one image → image post).
* **Media from anywhere** – the featured image, a custom field (attachment IDs, URLs, ACF image/gallery/file fields, WooCommerce product gallery), galleries in the content, every image in the content, attached media, or files you pick for one post. The full URL of every image and video is sent.
* **Override in the editor** – a sidebar box on every post lets you change the format, images, networks, caption and publish mode for that post only, preview what will be sent, and share now or share again.
* **Caption templates** – `{title}`, `{excerpt}`, `{content}`, `{url}`, `{hashtags}`, `{categories}`, `{meta:field}`, `{terms:taxonomy}` and more. Captions are shortened to the limit of the networks you post to.
* **Results per network** – status, link to the published post and error per network in the editor and in the posts list. Updates arrive by signed webhook, or are checked in the background.
* **Reliable** – sending runs in the background so saving a post never waits; temporary errors are retried; every post is sent with an idempotency key so it is never published twice.
* **Review first** – optionally create the post as a draft in Advert4 instead of publishing it right away.
* **Bulk sharing** – "Share to social media" bulk action on the posts list.
* **Developer friendly** – filters for the payload, media, caption and the share decision, plus actions after sending.

= Requirements =

* An [Advert4](https://advert4.me) account with your Facebook Page, Instagram account or TikTok account connected, and an API key.
* The site must be reachable from the internet, because Advert4 downloads the images and videos from it.

= External service =

This plugin sends data to the Advert4 API (`https://advert4.me/api/v1`) operated by Electroinvest, Montenegro:

* when a post is shared: the caption built from the post, the URLs of its images or video, the chosen profile and networks, and a signed callback URL of your site;
* when you test the connection or open the settings: a request for the profiles of your workspace.

Nothing is sent before you add an API key. See the Advert4 [Terms of Service](https://advert4.me/terms) and [Privacy Policy](https://advert4.me/privacy).

== Installation ==

1. Install and activate the plugin.
2. In Advert4, connect your social accounts to a profile and create an API key (menu "API").
3. In WordPress, open **Social Auto Post → Connection**, paste the key and save.
4. On the **Post types** tab, turn on the post types you want to share and choose the profile, networks, format, media source and caption.
5. Publish a post – the "Social Media Auto Post" box in the editor shows the result per network.

The API key can also be set in `wp-config.php`:

`define( 'A4SMAP_API_KEY', 'your-key' );`

== Frequently Asked Questions ==

= Does it post automatically? =

Yes, by default every new post of an enabled post type is shared when it is published. Choose "Only when I tick Share on the post" to decide post by post. Posts published before the plugin was installed are never shared by surprise when you edit them.

= Can I share a gallery? =

Yes. Choose "Gallery (carousel)" or "Automatic" and take the images from a custom field, from the galleries in the content, from every image in the content or from the attached images. Facebook and Instagram accept up to 10 images per carousel.

= Which custom field formats are supported? =

An attachment ID, a comma separated list of IDs (as WooCommerce stores the product gallery), a URL or path, a list of URLs, JSON or serialized arrays, and ACF image, gallery and file fields.

= Can I post videos and Reels? =

Yes. Choose where the video comes from (a custom field, the first video in the content, or the attached video). MP4 and MOV files are supported.

= What happens if Advert4 is temporarily unavailable? =

The post is retried after 1, 5 and 15 minutes. Validation errors (for example a video that is too long for a network) are shown in the editor instead.

= Does it work with the block editor and the classic editor? =

Yes, with both, and with Quick Edit, scheduled posts and WP-CLI.

== Screenshots ==

1. Overview: what was shared, what is in progress and what needs attention.
2. Settings per post type with a live preview of your latest post.
3. The editor box: share switch, preview, per-post options and the result per network.
4. Connection to your Advert4 workspace and its profiles.

== Credits ==

* Interface icons from [Lucide](https://lucide.dev), ISC license.
* Network logos from [Simple Icons](https://simpleicons.org), CC0.

== Changelog ==

= 1.0.0 =
* First release.
