<?php
/**
 * Incremental provisioner for users.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Search\Provisioning;

use MeshResearch\CCClient\Search\SearchAPI;

class IncrementalProfilesProvisioner implements IncrementalProvisionerInterface {
	public function __construct(
		private SearchAPI $search_api,
		private bool $enabled = true
	) {
		$this->registerHooks();
	}
	
	public function registerHooks(): void {
		add_action( 'profile_update', [$this, 'provisionNewOrUpdatedUser'] );
		add_action( 'xprofile_updated_profile', [$this, 'provisionNewOrUpdatedUser'] );
		add_action( 'wpmu_new_user', [$this, 'provisionNewOrUpdatedUser'] );
		add_action( 'user_register', [$this, 'provisionNewOrUpdatedUser'] );
		add_action( 'wp_update_user', [$this, 'provisionNewOrUpdatedUser'] );
		add_action( 'delete_user', [ $this, 'provisionDeletedUser' ] );
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
	
	public function provisionNewOrUpdatedUser( $user_id ) {
		if ( ! $this->enabled ) {
			return;
		}
		$user = get_userdata( $user_id );
		error_log( sprintf( '[CC-Client] Profile provisioning ADD/UPDATE - User ID: %d, Username: %s, Email: %s', $user_id, $user->user_login, $user->user_email ) );
		$provisionable_user = new ProvisionableProfile( $user );
		$provisionable_user->getSearchID();

		if ( 
			isset( $user->spam ) && 
			intval( $user->spam ) === 1 && 
			! empty( $provisionable_user->search_id ) 
		) {
			error_log( sprintf( '[CC-Client] Profile provisioning DELETE (spam user) - User ID: %d, Search ID: %s', $user_id, $provisionable_user->search_id ) );
			$success = $this->search_api->delete( $provisionable_user->search_id );
			if ( $success ) {
				$provisionable_user->setSearchID( '' );
				error_log( sprintf( '[CC-Client] Profile provisioning DELETE SUCCESS (spam user) - User ID: %d', $user_id ) );
			} else {
				error_log( sprintf( '[CC-Client] Profile provisioning DELETE FAILED (spam user) - User ID: %d', $user_id ) );
			}
			return;
		}

		$indexed_document = $this->search_api->index_or_update( $provisionable_user->toDocument() );
		if ( $indexed_document ) {
			$provisionable_user->setSearchID( $indexed_document->_id );
			error_log( sprintf( '[CC-Client] Profile provisioning ADD/UPDATE SUCCESS - User ID: %d, Search ID: %s', $user_id, $indexed_document->_id ) );
		} else {
			error_log( sprintf( '[CC-Client] Profile provisioning ADD/UPDATE FAILED - User ID: %d', $user_id ) );
		}
	}

	public function provisionDeletedUser( $user_id ) {
		if ( ! $this->enabled ) {
			return;
		}
		$user = get_userdata( $user_id );
		error_log( sprintf( '[CC-Client] Profile provisioning DELETE - User ID: %d, Username: %s', $user_id, $user->user_login ) );
		$provisionable_user = new ProvisionableProfile( $user );
		$search_id = $provisionable_user->getSearchID();
		if ( empty( $search_id ) ) {
			error_log( sprintf( '[CC-Client] Profile provisioning DELETE skipped (no search ID) - User ID: %d', $user_id ) );
			return;
		}
		error_log( sprintf( '[CC-Client] Profile provisioning DELETE - User ID: %d, Search ID: %s', $user_id, $search_id ) );
		$success = $this->search_api->delete( $search_id );
		if ( $success ) {
			$provisionable_user->setSearchID( '' );
			error_log( sprintf( '[CC-Client] Profile provisioning DELETE SUCCESS - User ID: %d, Search ID: %s', $user_id, $search_id ) );
		} else {
			error_log( sprintf( '[CC-Client] Profile provisioning DELETE FAILED - User ID: %d, Search ID: %s', $user_id, $search_id ) );
		}
	}
}