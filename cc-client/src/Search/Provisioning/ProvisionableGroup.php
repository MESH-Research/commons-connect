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

class ProvisionableGroup implements ProvisionableInterface {
	public function __construct(
		public \BP_Groups_Group $group,
		public string $search_id = ''
	) {}

	public function toDocument(): SearchDocument {
		if ( ! function_exists( 'groups_get_group_members') ) {
			throw new \Exception( 'BuddyPress Groups plugin is not active.' );
		}
		$group_admins = groups_get_group_members( [
			'group_id' => $this->group->id,
			'per_page' => 0,
			'exclude_admins_mods' => false,
			'group_role' => 'admin'
		] );
		$group_admin_id = $group_admins['members'][0] ?? null;
		$group_admin = get_user_by( 'ID', $group_admin_id );
		
		if ( function_exists( 'bp_groups_get_group_type') ) {
			$network_node = bp_groups_get_group_type( $this->group->id );
		} else {
			$network_node = get_current_network_node();
		}

		$admin = null;
		if ( $group_admin ) {
			$admin = new SearchPerson(
				name: $group_admin->display_name,
				username: $group_admin->user_login,
				url: get_profile_url( $group_admin ),
				role: 'admin',
				network_node: $network_node
			);
		}

		$doc = new SearchDocument(
			_internal_id: strval($this->group->id),
			title: $this->group->name,
			description: $this->group->description,
			owner: $admin,
			contributors: [],
			primary_url: bp_get_group_permalink( $this->group ),
			thumbnail_url: '',
			content: '',
			publication_date: null,
			modified_date: null,
			content_type: 'group',
			network_node: $network_node
		);

		if ( $this->search_id ) {
			$doc->_id = $this->search_id;
		}

		return $doc;
	}

	public function getSearchID(): string {
		$search_id = groups_get_groupmeta( $this->group->id, 'cc_search_id', true );
		if ( $search_id === false ) {
			$search_id = '';
		}
		return $search_id;
	}

	public function setSearchID(string $search_id): void {
		groups_update_groupmeta( $this->group->id, 'cc_search_id', $search_id );
	}

	public function updateSearchID() : void {
		$search_id = $this->getSearchID();
		$this->search_id = $search_id;
	}

	public static function getAll(): array {
		$groups = \BP_Groups_Group::get( [
			'per_page' => 0,
			'page' => 1,
			'populate_extras' => false
		] );

		$provisionable_groups = [];
		foreach ( $groups['groups'] as $group ) {
			$provisionable_groups[] = new ProvisionableGroup( $group );
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

	public static function isAvailable(): bool {
		return class_exists( '\BP_Groups_Group' );
	}
}