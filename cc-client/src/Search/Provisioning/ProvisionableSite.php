<?php
/**
 * A WordPress site that can be provisioned to the search service.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Search\Provisioning;

use MeshResearch\CCClient\Search\SearchDocument;
use MeshResearch\CCClient\Search\SearchPerson;

require_once __DIR__ . '/functions.php';

class ProvisionableSite implements ProvisionableInterface {
	public function __construct(
		public \WP_Site $site
	) {}

	public function toDocument(): SearchDocument {
		switch_to_blog( $this->site->id );
		$site_description = get_bloginfo( 'description' );
		$modified_date = date('Y-m-d', get_lastpostmodified());
		$network_node = get_current_network_node();
		restore_current_blog();
		
		$site_admin = get_users( [ 'blog_id' => $this->site->id, 'role' => 'administrator' ] )[0];
		$admin = new SearchPerson(
			name: $site_admin->display_name,
			username: $site_admin->user_login,
			url: get_profile_url( $site_admin ),
			role: 'admin',
			network_node: $network_node
		);

		$site_users = get_users( [ 
			'blog_id' => $this->site->id,
			'role_in' => [ 'administrator', 'editor', 'author', 'contributor' ]
		] );
		$contributors = array_map( function( $user ) {
			return new SearchPerson(
				name: $user->display_name,
				username: $user->user_login,
				url: get_profile_url( $user ),
				role: $user->roles[0],
				network_node: $network_node
			);
		}, $site_users );

		return new SearchDocument(
			title: $this->site->blogname,
			description: $site_description,
			owner: $admin,
			contributors: $contributors,
			primary_url: $this->site->siteurl,
			thumbnail_url: '',
			content: '',
			publication_date: '',
			modified_date: $modified_date,
			content_type: 'site',
			network_node: $network_node
		);
	}

	public function getSearchID(): string {
		$search_id = get_blog_option( $this->site->id, 'cc_search_id' );
		if ( $search_id === false ) {
			throw new \Exception( 'Invalid site ID' );
		}
		return $search_id;
	}

	public function setSearchID( string $search_id ): void {
		update_blog_option( $this->site->id, 'cc_search_id', $search_id );
	}

	public static function getAll(): array {
		$sites = get_sites();
		$provisionable_sites = [];
		foreach ( $sites as $site ) {
			$provisionable_sites[] = new ProvisionableSite( $site );
		}

		return $provisionable_sites;
	}

	public static function getAllAsDocuments(): array {
		$provisionable_sites = self::getAll();
		$documents = [];
		foreach ( $provisionable_sites as $provisionable_site ) {
			$documents[] = $provisionable_site->toDocument();
		}
		return $documents;
	}
}
