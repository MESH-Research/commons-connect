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

		if ( $post->post_status !== 'publish' && ! empty( $provisionable_post->search_id ) ) {
			$success = $this->search_api->delete( $provisionable_post->search_id );
			if ( ! $success ) {
				return;
			}
			$provisionable_post->setSearchID( '' );
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

	    // Switch to the site context to query its posts
	    switch_to_blog($site->blog_id);

	    $posts = get_posts([
	        'post_type' => $this->post_types,
	        'post_status' => 'any',
	        'posts_per_page' => -1,
	        'suppress_filters' => true,
	    ]);

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
	        return;
		}

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
	        return;
		}

		// Switch to the site context to query its posts
		switch_to_blog($site->blog_id);

		$posts = get_posts([
	        'post_type' => $this->post_types,
	        'post_status' => 'any',
	        'posts_per_page' => -1,
	        'suppress_filters' => true,
	    ]);

	    foreach ($posts as $post) {
			$this->provisionNewOrUpdatedPost($post->ID, $post, true);
	    }

	    // Restore the original blog context
	    restore_current_blog();
	}
}
