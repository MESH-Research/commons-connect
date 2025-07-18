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
		// Use wp_uninitialize_site instead of wp_delete_site because the latter fires
		// after the site's database tables have already been deleted, making it impossible
		// to query the posts that need to be removed from the search index.
		add_action('wp_validate_site_deletion', [ $this, 'provisionPostsFromDeletedSite' ], 10, 2 );
		add_action('make_spam_blog', [ $this, 'provisionPostsFromSpammedSite' ], 10, 1);
		add_action('make_ham_blog', [ $this, 'provisionPostsFromUnspammedSite' ], 10, 1);
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

		$action = $update ? 'UPDATE' : 'ADD';
		error_log( sprintf( '[CC-Client] Post provisioning %s - Post ID: %d, Type: %s, Title: %s', $action, $post_id, $post->post_type, $post->post_title ) );

		if ( $post->post_status !== 'publish' && ! empty( $provisionable_post->search_id ) ) {
			error_log( sprintf( '[CC-Client] Post provisioning DELETE (unpublished) - Post ID: %d, Search ID: %s', $post_id, $provisionable_post->search_id ) );
			$success = $this->search_api->delete( $provisionable_post->search_id );
			if ( ! $success ) {
				error_log( sprintf( '[CC-Client] Post provisioning DELETE FAILED - Post ID: %d, Search ID: %s', $post_id, $provisionable_post->search_id ) );
				return;
			}
			$provisionable_post->setSearchID( '' );
			error_log( sprintf( '[CC-Client] Post provisioning DELETE SUCCESS - Post ID: %d, Search ID: %s', $post_id, $provisionable_post->search_id ) );
			return;
		}

		$document = $this->search_api->index_or_update( $provisionable_post->toDocument() );
		if ( $document ) {
			$provisionable_post->setSearchID( $document->_id );
			error_log( sprintf( '[CC-Client] Post provisioning %s SUCCESS - Post ID: %d, Search ID: %s', $action, $post_id, $document->_id ) );
		} else {
			error_log( sprintf( '[CC-Client] Post provisioning %s FAILED - Post ID: %d', $action, $post_id ) );
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
			error_log( sprintf( '[CC-Client] Post provisioning DELETE skipped (no search ID) - Post ID: %d, Type: %s, Title: %s', $post_id, $post->post_type, $post->post_title ) );
			return;
		}

		error_log( sprintf( '[CC-Client] Post provisioning DELETE - Post ID: %d, Search ID: %s, Type: %s, Title: %s', $post_id, $search_id, $post->post_type, $post->post_title ) );

		$success = $this->search_api->delete( $search_id );
		if ( $success ) {
			$provisionable_post->setSearchID( '' );
			error_log( sprintf( '[CC-Client] Post provisioning DELETE SUCCESS - Post ID: %d, Search ID: %s', $post_id, $search_id ) );
		} else {
			error_log( sprintf( '[CC-Client] Post provisioning DELETE FAILED - Post ID: %d, Search ID: %s', $post_id, $search_id ) );
		}
	}

	/**
	 * Provisions posts from a site that is being deleted.
	 *
	 * This method is called during the wp_validate_site_deletion hook, which fires
	 * before any deletion process begins. This timing ensures that the database
	 * tables still exist and can be queried to remove posts from the search index.
	 *
	 * @param \WP_Error $errors Error object (unused)
	 * @param \WP_Site $site The site object being deleted
	 */
	public function provisionPostsFromDeletedSite(\WP_Error $errors, \WP_Site $site) {
		if ( ! $this->isEnabled() ) {
			return;
		}

		error_log( sprintf( '[CC-Client] Post provisioning DELETE ALL from site - Site ID: %d, Domain: %s', $site->blog_id, $site->domain ) );

	    // Switch to the site context to query its posts
	    switch_to_blog($site->blog_id);

	    $posts = get_posts([
	        'post_type' => $this->post_types,
	        'post_status' => 'any',
	        'posts_per_page' => -1,
	        'suppress_filters' => true,
	    ]);

	    $post_count = count($posts);
	    error_log( sprintf( '[CC-Client] Post provisioning DELETE ALL from site - Found %d posts to delete from Site ID: %d', $post_count, $site->blog_id ) );

	    foreach ($posts as $post) {
	        $this->provisionDeletedPost($post->ID, $post);
	    }

	    // Restore the original blog context
	    restore_current_blog();
	}

	public function provisionPostsFromSpammedSite(int $site_id) {
		if ( ! $this->isEnabled() ) {
			return;
		}

	    $site = get_site($site_id);
	    if (!$site) {
	        error_log( sprintf( '[CC-Client] Post provisioning DELETE ALL from spammed site FAILED - Site not found, Site ID: %d', $site_id ) );
	        return;
		}

		error_log( sprintf( '[CC-Client] Post provisioning DELETE ALL from spammed site - Site ID: %d, Domain: %s', $site_id, $site->domain ) );

		// Create a dummy WP_Error object for compatibility with provisionPostsFromDeletedSite
		$errors = new \WP_Error();
		$this->provisionPostsFromDeletedSite($errors, $site);
	}

	public function provisionPostsFromUnspammedSite(int $site_id) {
		if ( ! $this->isEnabled() ) {
			return;
		}

	    $site = get_site($site_id);
	    if (!$site) {
	        error_log( sprintf( '[CC-Client] Post provisioning RE-ADD from unspammed site FAILED - Site not found, Site ID: %d', $site_id ) );
	        return;
		}

		error_log( sprintf( '[CC-Client] Post provisioning RE-ADD from unspammed site - Site ID: %d, Domain: %s', $site_id, $site->domain ) );

		// Switch to the site context to query its posts
		switch_to_blog($site->blog_id);

		$posts = get_posts([
	        'post_type' => $this->post_types,
	        'post_status' => 'any',
	        'posts_per_page' => -1,
	        'suppress_filters' => true,
	    ]);

	    $post_count = count($posts);
	    error_log( sprintf( '[CC-Client] Post provisioning RE-ADD from unspammed site - Found %d posts to re-add from Site ID: %d', $post_count, $site_id ) );

	    foreach ($posts as $post) {
			$this->provisionNewOrUpdatedPost($post->ID, $post, true);
	    }

	    // Restore the original blog context
	    restore_current_blog();
	}
}
