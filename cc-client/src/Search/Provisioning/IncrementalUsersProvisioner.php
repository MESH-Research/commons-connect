<?php
/**
 * Incremental provisioner for users.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Search\Provisioning;

use MeshResearch\CCClient\Search\SearchAPI;

class IncrementalUsersProvisioner implements IncrementalProvisionerInterface {
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
		$provisionable_user = new ProvisionableUser( $user );
		$provisionable_user->getSearchID();

		if ( 
			isset( $user->spam ) && 
			intval( $user->spam ) === 1 && 
			! empty( $provisionable_user->search_id ) 
		) {
			$this->search_api->delete( $provisionable_user->search_id );
			$provisionable_user->setSearchID( '' );
			return;
		}

		$indexed_document = $this->search_api->index_or_update( $provisionable_user->toDocument() );
		$provisionable_user->setSearchID( $indexed_document->_id );
	}

	public function provisionDeletedUser( $user_id ) {
		if ( ! $this->enabled ) {
			return;
		}
		$user = get_userdata( $user_id );
		$provisionable_user = new ProvisionableUser( $user );
		$search_id = $provisionable_user->getSearchID();
		if ( empty( $search_id ) ) {
			return;
		}
		$this->search_api->delete( $search_id );
		$provisionable_user->setSearchID( '' );
	}
}