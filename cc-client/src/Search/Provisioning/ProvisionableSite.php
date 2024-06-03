<?php
/**
 * A WordPress site that can be provisioned to the search service.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Search\Provisioning;

use MeshResearch\CCClient\Search\SearchDocument;
use MeshResearch\CCClient\Search\SearchPerson;

class ProvisionableSite implements ProvisionableInterface {
	public function __construct(
		public \WP_Site $site,
		public string $search_id = ''
	) {
		$this->getSearchID();
	}

	public function toDocument(): SearchDocument {
		switch_to_blog( $this->site->id );
		$site_description = get_bloginfo( 'description' );
		$modified_date = new \DateTime( get_lastpostmodified() );
		$network_node = get_current_network_node();
		restore_current_blog();
		
		$admin = null;
		$site_admins = get_users( [ 'blog_id' => $this->site->id, 'role' => 'administrator' ] );
		$site_admin = $site_admins[0] ?? null;
		if ( $site_admin ) {
			$admin = new SearchPerson(
				name: $site_admin->display_name,
				username: $site_admin->user_login,
				url: get_profile_url( $site_admin ),
				role: 'admin',
				network_node: $network_node
			);
		}

		$doc = new SearchDocument(
			_internal_id: strval( $this->site->id ),
			title: $this->site->blogname,
			description: $site_description,
			owner: $admin,
			contributors: [],
			primary_url: $this->site->siteurl,
			thumbnail_url: '',
			content: '',
			publication_date: null,
			modified_date: $modified_date,
			content_type: 'site',
			network_node: $network_node
		);

		if ( $this->search_id ) {
			$doc->_id = $this->search_id;
		}

		return $doc;
	}

	public function getSearchID(): string {
		$search_id = get_blog_option( $this->site->blog_id, 'cc_search_id' );
		if ( $search_id === false ) {
			$search_id = '';
		}
		$this->search_id = $search_id;
		return $search_id;
	}

	public function setSearchID( string $search_id ): void {
		$success = update_blog_option( $this->site->blog_id, 'cc_search_id', $search_id );
	}

	public static function getAll(): array {
		$sites = get_sites( [ 'number' => 50000 ] );
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

	public static function isAvailable(): bool {
		return function_exists( 'get_sites' );
	}
}
