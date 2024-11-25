<?php
/**
 * A BuddyPress group that can be provisioned to the search service.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Search\Provisioning;

use MeshResearch\CCClient\Search\SearchDocument;
use MeshResearch\CCClient\Search\SearchPerson;

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
		if ( ! $search_id ) {
			$search_id = '';
		}
		$this->search_id = $search_id;
		return $search_id;
	}

	public function setSearchID(?string $search_id): void {
		if ( ! $search_id ) {
			groups_delete_groupmeta( $this->group->id, 'cc_search_id' );
		} else {
			groups_update_groupmeta( $this->group->id, 'cc_search_id', $search_id );
		}
		$this->search_id = $search_id ?? '';
	}

	public static function getAll( bool $reset = false, bool $show_progress = false ): array {
		$groups = \BP_Groups_Group::get( [
			'per_page' => 0,
			'page' => 1,
			'populate_extras' => false
		] );

		if ( $show_progress && class_exists( 'WP_CLI' ) ) {
			\WP_CLI::line( 'Provisioning ' . count( $groups['groups'] ) . ' groups...' );
		}

		$provisionable_groups = [];
		foreach ( $groups['groups'] as $group ) {
			$provisionable_group = new ProvisionableGroup( $group );
			if ( $reset ) {
				$provisionable_group->setSearchID( '' );
			}
			$provisionable_groups[] = $provisionable_group;
		}

		return $provisionable_groups;
	}

	public static function getAllAsDocuments( bool $reset = false, bool $show_progress = false ): array {
		$provisionable_groups = self::getAll( $reset, $show_progress );
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