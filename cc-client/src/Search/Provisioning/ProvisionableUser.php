<?php
/**
 * A WordPress profile that can be provisioned to the search service.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Search\Provisioning;

use MeshResearch\CCClient\Search\SearchDocument;
use MeshResearch\CCClient\Search\SearchPerson;

require_once __DIR__ . '/functions.php';

class ProvisionableUser implements ProvisionableInterface {
	public function __construct(
		public \WP_User $user
	) {}

	public function toDocument(): SearchDocument {
		$network_node = get_current_network_node();

		if ( class_exists( '\BP_XProfile_ProfileData' ) ) {
			$profile_data = \BP_XProfile_ProfileData::get_all_for_user( $this->user->ID );
		}

		$document = new SearchDocument(
			title: $this->user->display_name,
			description: '',
			owner: new SearchPerson(
				name: $this->user->display_name,
				username: $this->user->user_login,
				url: get_profile_url( $this->user ),
				role: 'user',
				network_node: $network_node
			),
			contributors: [],
			primary_url: get_profile_url( $this->user ),
			thumbnail_url: '',
			content: '',
			publication_date: '',
			modified_date: '',
			content_type: 'user',
			network_node: $network_node
		);
		return $document;
	}

	public function getSearchID(): string {
		$search_id = get_user_meta( $this->user->ID, 'cc_search_id', true );
		if ( $search_id === false ) {
			$search_id = '';
		}
		return $search_id;
	}

	public function setSearchID( string $search_id ): void {
		update_user_meta( $this->user->ID, 'cc_search_id', $search_id );
	}

	public static function getAll(): array {
		$users = get_users( [ 'blog_id' => get_current_blog_id() ] );

		$provisionable_users = [];
		foreach ( $users as $user ) {
			$provisionable_users[] = new ProvisionableUser( $user );
		}

		return $provisionable_users;
	}

	public static function getAllAsDocuments(): array {
		$provisionable_users = self::getAll();
		$documents = [];
		foreach ( $provisionable_users as $provisionable_user ) {
			$documents[] = $provisionable_user->toDocument();
		}
		return $documents;
	}
}