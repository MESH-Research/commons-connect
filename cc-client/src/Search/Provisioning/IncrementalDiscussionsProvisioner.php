<?php
/**
 * Incremental provisioner for bbPress topics and replies.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Search\Provisioning;

use MeshResearch\CCClient\Search\SearchAPI;

class IncrementalDiscussionsProvisioner extends IncrementalPostsProvisioner {
	public function __construct(
		private SearchAPI $search_api,
		private bool $enabled = true,
		public array $post_types = [ 'topic', 'reply' ]
	) {
		$this->registerHooks();
	}

	public function ProvisionNewOrUpdatedPost( int $post_id, \WP_Post $post, bool $update ) {
		if ( ! $this->enabled ) {
			return;
		}
		if ( ! in_array( $post->post_type, $this->post_types ) ) {
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