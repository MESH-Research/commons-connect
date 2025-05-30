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
		add_action('wp_delete_site', [ $this, 'provisionPostsFromDeletedSite' ], 10, 2 );
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

	public function provisionPostsFromDeletedSite(\WP_Site $deletedSite) {
	    $posts = get_posts([
	        'post_type' => $this->post_types,
	        'post_status' => 'any',
	        'posts_per_page' => -1,
	        'suppress_filters' => true,
	        'site_id' => $deletedSite->blog_id,
	    ]);

	    foreach ($posts as $post) {
	        $this->provisionDeletedPost($post->ID, $post, true);
	    }
	}

	public function provisionPostsFromSpammedSite(int $site_id) {
	    $site = get_site($site_id);
	    if (!$site) {
	        return;
		}
		$this->provisionPostsFromDeletedSite($site);
	}

	public function provisionPostsFromUnspammedSite(int $site_id) {
	    $site = get_site($site_id);
	    if (!$site) {
	        return;
		}
		$posts = get_posts([
	        'post_type' => $this->post_types,
	        'post_status' => 'any',
	        'posts_per_page' => -1,
	        'suppress_filters' => true,
	        'site_id' => $site->blog_id,
	    ]);

	    foreach ($posts as $post) {
			$this->provisionNewOrUpdatedPost($post->ID, $post, true);
	    }
	}
}
