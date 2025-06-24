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
		add_action( 'before_delete_post', [ $this, 'provisionDeletedPost' ], 10, 2 );
		add_action( 'wp_validate_site_deletion', [ $this, 'provisionPostsFromDeletedSite' ], 10, 2 );
		add_action('make_spam_blog', [ $this, 'provisionPostsFromSpammedSite' ], 10, 1);
		add_action('make_ham_blog', [ $this, 'provisionPostsFromUnspammedSite' ], 10, 1);
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

	public function provisionNewOrUpdatedPost( int $post_id, \WP_Post $post, bool $update ) {
		if ( ! $this->isEnabled() ) {
			return;
		}
		if ( ! in_array( $post->post_type, $this->incremental_posts_provisioner->post_types ) ) {
			return;
		}

		// Check if discussion is public before delegating to posts provisioner
		$provisionable_discussion = new ProvisionableDiscussion( $post );
		if ( ! $provisionable_discussion->is_public() && ! empty( $provisionable_discussion->getSearchID() ) ) {
			$success = $this->search_api->delete( $provisionable_discussion->search_id );
			if ( $success ) {
				$provisionable_discussion->setSearchID( '' );
			}
			return;
		}

		// If discussion is public or doesn't have a search ID, delegate to posts provisioner
		if ( $provisionable_discussion->is_public() ) {
			$this->incremental_posts_provisioner->provisionNewOrUpdatedPost( $post_id, $post, $update );
		}
	}

	public function provisionDeletedPost( int $post_id, \WP_Post $post ) {
		$this->incremental_posts_provisioner->provisionDeletedPost( $post_id, $post );
	}

	public function provisionPostsFromDeletedSite(\WP_Error $errors, \WP_Site $deletedSite) {
		$this->incremental_posts_provisioner->provisionPostsFromDeletedSite( $errors, $deletedSite );
	}

	public function provisionPostsFromSpammedSite(int $site_id) {
		$this->incremental_posts_provisioner->provisionPostsFromSpammedSite( $site_id );
	}

	public function provisionPostsFromUnspammedSite(int $site_id) {
		$this->incremental_posts_provisioner->provisionPostsFromUnspammedSite( $site_id );
	}
}
