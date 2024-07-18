<?php
/**
 * Incremental provisioner for BuddyPress groups.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Search\Provisioning;

use MeshResearch\CCClient\Search\SearchAPI;

class IncrementalGroupsProvisioner implements IncrementalProvisionerInterface {
	public function __construct(
		private SearchAPI $search_api,
		private bool $enabled = true
	) {
		$this->registerHooks();
	}

	public function registerHooks(): void {
		add_action( 'groups_group_after_save',  [ $this, 'provisionNewOrUpdatedGroup' ] );
		add_action( 'groups_before_delete_group', [ $this, 'provisionDeletedGroup' ] );
	}

	public function isEnabled(): bool {
		return $this->enabled;
	}

	public function enable(): void {
		$this->enabled = true;
	}

	public function disable(): void {
		$this->enabled = false;
	}

	public function provisionNewOrUpdatedGroup( \BP_Groups_Group $group ) {
		if ( ! $this->enabled ) {
			return;
		}
		$provisionable_group = new ProvisionableGroup( $group );
		$provisionable_group->getSearchID();

		if ( 'public' === $group->status ) {
			$indexed_document = $this->search_api->index_or_update( $provisionable_group->toDocument() );
			$provisionable_group->setSearchID( $indexed_document->_id );
			return;
		}

		// If the group isn't public, delete the document if it exists.
		if ( ! empty( $provisionable_group->getSearchID() ) ) {
			$this->search_api->delete( $provisionable_group->getSearchID() );
			$provisionable_group->setSearchID( '' );
			return;
		}
	}

	public function provisionDeletedGroup( int $group_id ) {
		$group = new \BP_Groups_Group( $group_id );
		$provisionable_group = new ProvisionableGroup( $group );
		$search_id = $provisionable_group->getSearchID();
		if ( empty( $search_id ) ) {
			return;
		}

		$this->search_api->delete( $search_id );
		$provisionable_group->setSearchID( '' );
	}
}