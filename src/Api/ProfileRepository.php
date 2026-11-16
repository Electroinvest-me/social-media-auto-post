<?php
/**
 * Cached list of Advert4 profiles.
 *
 * @package Advert4\SocialMediaAutoPost
 */

namespace Advert4\SocialMediaAutoPost\Api;

defined( 'ABSPATH' ) || exit;

/**
 * Profiles of the workspace and their connected networks, cached for a few hours.
 *
 * @phpstan-type Account array{id: int, provider: string, provider_label: string, name: string, username: string, status: string}
 * @phpstan-type Profile array{id: int, name: string, slug: string, accounts: list<Account>}
 */
final class ProfileRepository {

	public const TRANSIENT = 'a4smap_profiles';

	/**
	 * API client.
	 *
	 * @var Client
	 */
	private Client $client;

	/**
	 * Create the repository.
	 *
	 * @param Client $client API client.
	 */
	public function __construct( Client $client ) {
		$this->client = $client;
	}

	/**
	 * Every profile.
	 *
	 * @param bool $refresh Skip the cache.
	 * @return list<Profile>
	 * @throws ApiException When Advert4 cannot be reached.
	 */
	public function all( bool $refresh = false ): array {
		if ( ! $this->client->is_configured() ) {
			return array();
		}

		if ( ! $refresh ) {
			$cached = get_transient( self::TRANSIENT );

			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$profiles = array_map( array( $this, 'normalize' ), $this->client->profiles() );

		set_transient( self::TRANSIENT, $profiles, 6 * HOUR_IN_SECONDS );

		return $profiles;
	}

	/**
	 * Every profile, or an empty list when Advert4 cannot be reached.
	 *
	 * @return list<Profile>
	 */
	public function cached(): array {
		try {
			return $this->all();
		} catch ( ApiException $exception ) {
			return array();
		}
	}

	/**
	 * A profile by slug or id.
	 *
	 * @param string $reference Slug or id.
	 * @return Profile|null
	 */
	public function find( string $reference ): ?array {
		foreach ( $this->cached() as $profile ) {
			if ( $profile['slug'] === $reference || (string) $profile['id'] === $reference ) {
				return $profile;
			}
		}

		return null;
	}

	/**
	 * Networks with an active account in a profile.
	 *
	 * @param string $reference Profile slug or id.
	 * @return list<string>
	 */
	public function networks( string $reference ): array {
		$profile = $this->find( $reference );

		if ( null === $profile ) {
			return array();
		}

		$networks = array();

		foreach ( $profile['accounts'] as $account ) {
			if ( 'active' === $account['status'] ) {
				$networks[] = $account['provider'];
			}
		}

		return array_values( array_unique( $networks ) );
	}

	/**
	 * The profile to use when none is configured: the only one in the workspace.
	 */
	public function default_reference(): string {
		$profiles = $this->cached();

		return 1 === count( $profiles ) ? $profiles[0]['slug'] : '';
	}

	/**
	 * Drop the cache.
	 */
	public function forget(): void {
		delete_transient( self::TRANSIENT );
	}

	/**
	 * Keep only the fields the plugin uses.
	 *
	 * @param array<string, mixed> $profile Profile from the API.
	 * @return Profile
	 */
	private function normalize( array $profile ): array {
		$accounts = array();

		foreach ( (array) ( $profile['accounts'] ?? array() ) as $account ) {
			if ( ! is_array( $account ) ) {
				continue;
			}

			$accounts[] = array(
				'id'             => (int) ( $account['id'] ?? 0 ),
				'provider'       => (string) ( $account['provider'] ?? '' ),
				'provider_label' => (string) ( $account['provider_label'] ?? '' ),
				'name'           => (string) ( $account['name'] ?? '' ),
				'username'       => (string) ( $account['username'] ?? '' ),
				'status'         => (string) ( $account['status'] ?? '' ),
			);
		}

		return array(
			'id'       => (int) ( $profile['id'] ?? 0 ),
			'name'     => (string) ( $profile['name'] ?? '' ),
			'slug'     => (string) ( $profile['slug'] ?? '' ),
			'accounts' => $accounts,
		);
	}
}
