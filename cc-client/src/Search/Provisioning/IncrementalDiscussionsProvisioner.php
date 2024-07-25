<?php
/**
 * Incremental provisioner for bbPress topics and replies.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Search\Provisioning;

use MeshResearch\CCClient\Search\SearchAPI;

class IncrementalDiscussionsProvisioner implements IncrementalProvisionerInterface {
	private IncrementalPostsProvisioner $incremental_posts_provisioner;
	
	public function __construct(
		private SearchAPI $search_api,
	) {
		$this->incremental_posts_provisioner = new IncrementalPostsProvisioner( $search_api );
		$this->registerHooks();
		$this->incremental_posts_provisioner->post_types = [ 'topic', 'reply' ];
	}

	public function registerHooks() : void {
		add_action( 'save_post', [ $this, 'provisionNewOrUpdatedPost' ], 10, 3 );
		add_action( 'before_delete_post', [ $this->incremental_posts_provisioner, 'provisionDeletedPost' ], 10, 2 );
	}

	public function isEnabled() : bool {
		return $this->incremental_posts_provisioner->isEnabled();
	}

	public function enable() : void {
		$this->incremental_posts_provisioner->enable();
	}

	public function disable() : void {
		$this->incremental_posts_provisioner->disable();
	}

	public function ProvisionNewOrUpdatedPost( int $post_id, \WP_Post $post, bool $update ) {
		if ( ! $this->isEnabled() ) {
			return;
		}
		if ( ! in_array( $post->post_type, $this->incremental_posts_provisioner->post_types ) ) {
			return;
		}
		$provisionable_discussion = new ProvisionableDiscussion( $post );
		$provisionable_discussion->getSearchID();

		if ( $post->post_status !== 'publish' && ! empty( $provisionable_discussion->search_id ) ) {
			$this->search_api->delete( $provisionable_discussion->search_id );
			$provisionable_discussion->setSearchID( '' );
			return;
		}

		if ( ! $provisionable_discussion->is_public() && ! empty( $provisionable_discussion->search_id ) ) {
			$this->search_api->delete( $provisionable_discussion->search_id );
			$provisionable_discussion->setSearchID( '' );
			return;
		}

		$document = $this->search_api->index_or_update( $provisionable_discussion->toDocument() );
		$provisionable_discussion->setSearchID( $document->_id );
	}
}