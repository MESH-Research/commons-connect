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

		$action = $update ? 'UPDATE' : 'ADD';
		error_log( sprintf( '[CC-Client] Discussion provisioning %s - Post ID: %d, Type: %s, Title: %s', $action, $post_id, $post->post_type, $post->post_title ) );

		// Check if discussion is public before delegating to posts provisioner
		$provisionable_discussion = new ProvisionableDiscussion( $post );
		if ( ! $provisionable_discussion->is_public() && ! empty( $provisionable_discussion->getSearchID() ) ) {
			error_log( sprintf( '[CC-Client] Discussion provisioning DELETE (non-public) - Post ID: %d, Search ID: %s', $post_id, $provisionable_discussion->search_id ) );
			$success = $this->search_api->delete( $provisionable_discussion->search_id );
			if ( $success ) {
				$provisionable_discussion->setSearchID( '' );
				error_log( sprintf( '[CC-Client] Discussion provisioning DELETE SUCCESS (non-public) - Post ID: %d', $post_id ) );
			} else {
				error_log( sprintf( '[CC-Client] Discussion provisioning DELETE FAILED (non-public) - Post ID: %d', $post_id ) );
			}
			return;
		}

		// If discussion is public or doesn't have a search ID, delegate to posts provisioner
		if ( $provisionable_discussion->is_public() ) {
			error_log( sprintf( '[CC-Client] Discussion provisioning %s (public) - Delegating to posts provisioner for Post ID: %d', $action, $post_id ) );
			$this->incremental_posts_provisioner->provisionNewOrUpdatedPost( $post_id, $post, $update );
		} else {
			error_log( sprintf( '[CC-Client] Discussion provisioning %s skipped (non-public, no search ID) - Post ID: %d', $action, $post_id ) );
		}
	}

	public function provisionDeletedPost( int $post_id, \WP_Post $post ) {
		if ( in_array( $post->post_type, $this->incremental_posts_provisioner->post_types ) ) {
			error_log( sprintf( '[CC-Client] Discussion provisioning DELETE - Post ID: %d, Type: %s, Title: %s', $post_id, $post->post_type, $post->post_title ) );
		}
		$this->incremental_posts_provisioner->provisionDeletedPost( $post_id, $post );
	}

	public function provisionPostsFromDeletedSite(\WP_Error $errors, \WP_Site $deletedSite) {
		error_log( sprintf( '[CC-Client] Discussion provisioning DELETE ALL from site - Site ID: %d, Domain: %s', $deletedSite->blog_id, $deletedSite->domain ) );
		$this->incremental_posts_provisioner->provisionPostsFromDeletedSite( $errors, $deletedSite );
	}

	public function provisionPostsFromSpammedSite(int $site_id) {
		error_log( sprintf( '[CC-Client] Discussion provisioning DELETE ALL from spammed site - Site ID: %d', $site_id ) );
		$this->incremental_posts_provisioner->provisionPostsFromSpammedSite( $site_id );
	}

	public function provisionPostsFromUnspammedSite(int $site_id) {
		error_log( sprintf( '[CC-Client] Discussion provisioning RE-ADD from unspammed site - Site ID: %d', $site_id ) );
		$this->incremental_posts_provisioner->provisionPostsFromUnspammedSite( $site_id );
	}
}
