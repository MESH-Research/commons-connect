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
		error_log( sprintf( '[CC-Client] Group provisioning ADD/UPDATE - Group ID: %d, Name: %s, Status: %s', $group->id, $group->name, $group->status ) );
		$provisionable_group = new ProvisionableGroup( $group );
		$provisionable_group->getSearchID();

		if ( 'public' === $group->status ) {
			$indexed_document = $this->search_api->index_or_update( $provisionable_group->toDocument() );
			if ( $indexed_document ) {
				$provisionable_group->setSearchID( $indexed_document->_id );
				error_log( sprintf( '[CC-Client] Group provisioning ADD/UPDATE SUCCESS - Group ID: %d, Search ID: %s', $group->id, $indexed_document->_id ) );
			} else {
				error_log( sprintf( '[CC-Client] Group provisioning ADD/UPDATE FAILED - Group ID: %d', $group->id ) );
			}
			return;
		}

		// If the group isn't public, delete the document if it exists.
		if ( ! empty( $provisionable_group->getSearchID() ) ) {
			error_log( sprintf( '[CC-Client] Group provisioning DELETE (non-public) - Group ID: %d, Search ID: %s', $group->id, $provisionable_group->getSearchID() ) );
			$success = $this->search_api->delete( $provisionable_group->getSearchID() );
			if ( $success ) {
				$provisionable_group->setSearchID( '' );
				error_log( sprintf( '[CC-Client] Group provisioning DELETE SUCCESS (non-public) - Group ID: %d', $group->id ) );
			} else {
				error_log( sprintf( '[CC-Client] Group provisioning DELETE FAILED (non-public) - Group ID: %d', $group->id ) );
			}
		} else {
			error_log( sprintf( '[CC-Client] Group provisioning DELETE skipped (non-public, no search ID) - Group ID: %d', $group->id ) );
		}
	}

	public function provisionDeletedGroup( int $group_id ) {
		$group = new \BP_Groups_Group( $group_id );
		error_log( sprintf( '[CC-Client] Group provisioning DELETE - Group ID: %d, Name: %s', $group_id, $group->name ) );
		$provisionable_group = new ProvisionableGroup( $group );
		$search_id = $provisionable_group->getSearchID();
		if ( empty( $search_id ) ) {
			error_log( sprintf( '[CC-Client] Group provisioning DELETE skipped (no search ID) - Group ID: %d', $group_id ) );
			return;
		}

		error_log( sprintf( '[CC-Client] Group provisioning DELETE - Group ID: %d, Search ID: %s', $group_id, $search_id ) );
		$success = $this->search_api->delete( $search_id );
		if ( $success ) {
			$provisionable_group->setSearchID( '' );
			error_log( sprintf( '[CC-Client] Group provisioning DELETE SUCCESS - Group ID: %d, Search ID: %s', $group_id, $search_id ) );
		} else {
			error_log( sprintf( '[CC-Client] Group provisioning DELETE FAILED - Group ID: %d, Search ID: %s', $group_id, $search_id ) );
		}
	}
}