<?php
/**
 * Plugin composition root.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost;

use Advert4\SocialMediaAutoPost\Admin\Ajax;
use Advert4\SocialMediaAutoPost\Admin\MetaBox;
use Advert4\SocialMediaAutoPost\Admin\Notices;
use Advert4\SocialMediaAutoPost\Admin\Overview;
use Advert4\SocialMediaAutoPost\Admin\PostsList;
use Advert4\SocialMediaAutoPost\Admin\SettingsPage;
use Advert4\SocialMediaAutoPost\Api\Client;
use Advert4\SocialMediaAutoPost\Api\ProfileRepository;
use Advert4\SocialMediaAutoPost\Content\CaptionBuilder;
use Advert4\SocialMediaAutoPost\Contracts\Hookable;
use Advert4\SocialMediaAutoPost\Media\ContentScanner;
use Advert4\SocialMediaAutoPost\Media\MediaResolver;
use Advert4\SocialMediaAutoPost\Media\MetaValueParser;
use Advert4\SocialMediaAutoPost\Rest\WebhookController;
use Advert4\SocialMediaAutoPost\Settings\Settings;
use Advert4\SocialMediaAutoPost\Sharing\AutoShare;
use Advert4\SocialMediaAutoPost\Sharing\PayloadBuilder;
use Advert4\SocialMediaAutoPost\Sharing\Publisher;
use Advert4\SocialMediaAutoPost\Sharing\Queue;
use Advert4\SocialMediaAutoPost\Sharing\ShareDecision;
use Advert4\SocialMediaAutoPost\Sharing\StatusSync;
use Advert4\SocialMediaAutoPost\Sharing\Worker;
use Advert4\SocialMediaAutoPost\Support\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the services once and attaches them to WordPress.
 */
final class Plugin {

	/**
	 * The running instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Services keyed by class name.
	 *
	 * @var array<class-string, object>
	 */
	private array $services = array();

	/**
	 * Boot the plugin (plugins_loaded).
	 */
	public static function boot(): void {
		if ( null !== self::$instance ) {
			return;
		}

		self::$instance = new self();
		self::$instance->build();
		self::$instance->register();
	}

	/**
	 * The running instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::boot();
		}

		return self::$instance;
	}

	/**
	 * A service, for themes and other plugins: Plugin::instance()->get( Publisher::class ).
	 *
	 * @template T of object
	 * @param class-string<T> $id Class name.
	 * @return T|null
	 */
	public function get( string $id ): ?object {
		$service = $this->services[ $id ] ?? null;

		return $service instanceof $id ? $service : null;
	}

	/**
	 * Create every service.
	 */
	private function build(): void {
		$settings  = new Settings();
		$logger    = new Logger();
		$client    = new Client( $settings );
		$profiles  = new ProfileRepository( $client );
		$queue     = new Queue();
		$decision  = new ShareDecision( $settings );
		$resolver  = new MediaResolver( $settings, new MetaValueParser(), new ContentScanner() );
		$builder   = new PayloadBuilder( $settings, $resolver, new CaptionBuilder(), $profiles );
		$publisher = new Publisher( $client, $builder, $decision, $queue, $logger );
		$sync      = new StatusSync( $client, $queue, $logger );
		$auto      = new AutoShare( $decision, $queue );

		$this->add( $settings, $logger, $client, $profiles, $queue, $decision, $resolver, $builder, $publisher, $sync, $auto );
		$this->add( new Worker( $publisher, $sync, $queue ), new WebhookController( $sync ) );

		if ( is_admin() ) {
			$this->add(
				new SettingsPage( $settings, $profiles, $logger, new Overview( $settings ) ),
				new MetaBox( $settings, $decision, $builder, $auto ),
				new PostsList( $settings, $decision, $queue ),
				new Ajax( $settings, $profiles, $publisher, $sync, $queue, $decision, $builder, $logger ),
				new Notices( $settings )
			);
		}
	}

	/**
	 * Store services.
	 *
	 * @param object ...$services Services.
	 */
	private function add( object ...$services ): void {
		foreach ( $services as $service ) {
			$this->services[ get_class( $service ) ] = $service;
		}
	}

	/**
	 * Attach the hookable services and load translations.
	 */
	private function register(): void {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		foreach ( $this->services as $service ) {
			if ( $service instanceof Hookable ) {
				$service->register();
			}
		}
	}

	/**
	 * Load translations from /languages (WordPress.org translations load automatically).
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain( 'social-media-auto-post', false, dirname( plugin_basename( A4SMAP_FILE ) ) . '/languages' );
	}
}
