<?php
/**
 * A BuddyPress group that can be provisioned to the search service.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Search\Provisioning;

use MeshResearch\CCClient\Search\SearchDocument;
use MeshResearch\CCClient\Search\SearchPerson;

require_once __DIR__ . '/functions.php';

class ProvisionalGroup implements ProvisionableInterface {
	public function __construct(
		public \BP_Groups_Group $group
	) {}

	public function toDocument(): SearchDocument {
		$group_admin = get_users( [ 'group_id' => $this->group->id, 'role' => 'admin' ] )[0];
		$admin = new SearchPerson(
			name: $group_admin->display_name,
			username: $group_admin->user_login,
			url: get_profile_url( $group_admin ),
			role: 'admin',
			network_node: get_current_network_node()
		);

		$group_users = get_users( [ 
			'group_id' => $this->group->id,
			'role_in' => [ 'admin', 'mod', 'member' ]
		] );
		$members = array_map( function( $user ) {
			return new SearchPerson(
				name: $user->display_name,
				username: $user->user_login,
				url: get_profile_url( $user ),
				role: $user->roles[0],
				network_node: get_current_network_node()
			);
		}, $group_users );

		return new SearchDocument(
			title: $this->group->name,
			description: $this->group->description,
			owner: $admin,
			contributors: $members,
			primary_url: bp_get_group_permalink( $this->group ),
			thumbnail_url: '',
			content: '',
			publication_date: '',
			modified_date: '',
			content_type: 'group',
			network_node: get_current_network_node()
		);
	}

	public function getSearchID(): string {
		$search_id = groups_get_groupmeta( $this->group->id, 'cc_search_id', true );
		if ( $search_id === false ) {
			throw new \Exception( 'Group does not exist.' );
		}
		return $search_id;
	}

	public function setSearchID(string $search_id): void {
		groups_update_groupmeta( $this->group->id, 'cc_search_id', $search_id );
	}

	public static function getAll(): array {
		$groups = \BP_Groups_Group::get( [
			'per_page' => 0,
			'page' => 1,
			'populate_extras' => false
		] );

		$provisionable_groups = [];
		foreach ( $groups['groups'] as $group ) {
			$provisionable_groups[] = new ProvisionalGroup( $group );
		}

		return $provisionable_groups;
	}

	public static function getAllAsDocuments(): array {
		$provisionable_groups = self::getAll();
		$documents = [];
		foreach ( $provisionable_groups as $provisionable_group ) {
			$documents[] = $provisionable_group->toDocument();
		}
		return $documents;
	}
}