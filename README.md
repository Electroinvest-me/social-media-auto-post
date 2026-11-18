# Social Media Auto Post

WordPress plugin that auto posts posts, images, galleries and videos to **Facebook, Instagram and TikTok** through the [Advert4](https://advert4.me) REST API — automatically on publish or only when the editor ticks "Share".

- WordPress 6.4+, **PHP 7.4+**
- Slug / text domain: `social-media-auto-post`
- Prefix: `a4smap` (options, meta, hooks, REST namespace), namespace `Advert4\SocialMediaAutoPost`

## Features

- Per post type settings: on/off, automatic or manual trigger, Advert4 profile, networks, format, media source, caption template, publish or draft in Advert4.
- Formats: automatic, single image, gallery (carousel), video (Reel), Story.
- Media sources: featured image, custom field (attachment IDs, `12,34` lists, URLs, JSON/serialized arrays, ACF image/gallery/file), galleries in the content, all images in the content, attached media, or files picked per post. Every reference is turned into an absolute file URL.
- Editor sidebar box: share toggle, per-post overrides, live preview, "Share now / Share again", result per network with links and errors.
- Background sending on WP-Cron with retries (1, 5, 15 min) and an idempotent `external_id`, signed webhook (`/wp-json/a4smap/v1/webhook`) plus status polling.
- "Social" column and "Share to social media" bulk action on the posts list, activity log on the settings screen.

## Structure

```
social-media-auto-post.php   Bootstrap: constants, PSR-4 autoloader, activation hooks
uninstall.php                Cleanup (data only when "delete on uninstall" is ticked)
src/
  Plugin.php                 Composition root: builds the services and registers hooks
  Installer.php              Activation / deactivation
  Contracts/Hookable.php     Interface of services that attach to WordPress hooks
  Enums/                     Option lists (Format, ImageSource, VideoSource, Trigger, Mode, Network)
  Settings/                  Settings option + PostTypeConfig value object
  Api/                       Advert4 HTTP client, ApiException, cached ProfileRepository
  Media/                     MediaResolver, MetaValueParser, ContentScanner, MediaItem
  Content/CaptionBuilder.php Caption templates and placeholders
  Sharing/                   PayloadBuilder, Publisher, Queue, Worker, AutoShare, StatusSync,
                             ShareDecision, ShareState, PostOverrides, SharePlan
  Rest/WebhookController.php Advert4 result webhook
  Admin/                     SettingsPage, Overview, MetaBox, PostsList, Ajax, Notices, Assets, Icons
views/                       PHP templates: settings tabs, meta box, social post preview
assets/                      admin.css (Advert4 design tokens), admin.js, Advert4 logo
languages/                   Translation template (.pot)
```

## How publishing works

1. `transition_post_status` → `AutoShare` queues the post (`ShareState` = `queued`) when `ShareDecision` says it should be shared.
2. The block editor saves meta boxes in a second request, so REST publishes are queued with a 30 s delay; the meta box save then sends at once or cancels when "Share" was unticked. Classic editor, Quick Edit, scheduled posts and WP-CLI send immediately.
3. `Worker` (cron hook `a4smap_publish`) → `Publisher` → `PayloadBuilder` (settings + `PostOverrides` + `MediaResolver` + `CaptionBuilder`) → `POST /api/v1/posts`.
4. The Advert4 status per network is stored on the post and refreshed by webhook or by `a4smap_refresh` cron checks.

## Configuration constants

```php
define( 'A4SMAP_API_KEY', '…' );                       // Advert4 workspace API key
define( 'A4SMAP_API_URL', 'https://advert4.me/api/v1' ); // Self-hosted / staging Advert4
```

## Hooks

| Hook | Type | |
| --- | --- | --- |
| `a4smap_should_share` | filter | `(bool $wants, WP_Post $post)` share on publish? |
| `a4smap_media` | filter | `(MediaSelection $selection, WP_Post $post, PostTypeConfig $config)` |
| `a4smap_caption` | filter | `(string $caption, WP_Post $post, string $template)` |
| `a4smap_payload` | filter | `(array $payload, WP_Post $post)` full API request |
| `a4smap_http_args` | filter | `(array $args, string $method, string $path)` |
| `a4smap_post_types` | filter | post types listed on the settings screen |
| `a4smap_shared` | action | `(int $post_id, array $remote, array $payload)` |
| `a4smap_share_failed` | action | `(int $post_id, string $message, SharePlan $plan)` |
| `a4smap_status_changed` | action | `(int $post_id, string $status, string $previous, ShareState $state)` |

Example — TikTok options:

```php
add_filter( 'a4smap_payload', function ( array $payload ) {
	$payload['options']['tiktok']['privacy_level'] = 'PUBLIC_TO_EVERYONE';
	return $payload;
} );
```

## Development

```sh
composer install
composer check        # php -l, PHPCS (WordPress + PHPCompatibility 7.4+), PHPStan level 6
composer zip          # dist/social-media-auto-post.zip from the last commit (dev files excluded)
```

## Credits

- Interface icons: [Lucide](https://lucide.dev) (ISC license)
- Network logos: [Simple Icons](https://simpleicons.org) (CC0)

## License

GPL-2.0-or-later. © Electroinvest.
