<?php
/**
 * Incremental provisioner for posts.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Search\Provisioning;

use MeshResearch\CCClient\Search\SearchAPI;

class IncrementalPostsProvisioner implements IncrementalProvisionerInterface {
	public function __construct(
		private SearchAPI $search_api,
		private bool $enabled = true,
		public array $post_types = [ 'post', 'page' ]
	) {
		$this->registerHooks();
	}

	public function registerHooks() : void {
		add_action( 'save_post', [ $this, 'provisionNewOrUpdatedPost' ], 10, 3 );
		add_action( 'before_delete_post', [ $this, 'provisionDeletedPost' ], 10, 2 );
	}

	public function isEnabled() : bool {
		return $this->enabled;
	}
	
	public function enable() : void {
		$this->enabled = true;
	}

	public function disable() : void {
		$this->enabled = false;
	}

	public function provisionNewOrUpdatedPost( int $post_id, \WP_Post $post, bool $update ) {
		if ( ! $this->isEnabled() ) {
			return;
		}
		if ( ! in_array( $post->post_type, $this->post_types ) ) {
			return;
		}
		$provisionable_post = new ProvisionablePost( $post );
		$provisionable_post->getSearchID();
		
		if ( $post->post_status !== 'publish' && ! empty( $provisionable_item->search_id ) ) {
			$success = $this->search_api->delete( $provisionable_item->search_id );
			if ( ! $success ) {
				return;
			}
			$provisionable_item->setSearchID( '' );
			return;
		}

		$document = $this->search_api->index_or_update( $provisionable_post->toDocument() );
		if ( $document ) {
			$provisionable_post->setSearchID( $document->_id );
		}
	}

	public function provisionDeletedPost( int $post_id, \WP_Post $post ) {
		if ( ! $this->isEnabled() ) {
			return;
		}
		if ( ! in_array( $post->post_type, $this->post_types ) ) {
			return;
		}
		$provisionable_post = new ProvisionablePost( $post );
		$search_id = $provisionable_post->getSearchID();
		if ( empty( $search_id ) ) {
			return;
		}

		$success = $this->search_api->delete( $search_id );
		if ( $success ) {
			$provisionable_post->setSearchID( '' );
		}
	}
}