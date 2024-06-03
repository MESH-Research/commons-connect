<?php
/**
 * Tests for incremental provisioning.
 * 
 * These test cases do not explicitly call provisioning functions, but instead
 * expect that provisioning will occur as a side effect of creating, updating,
 * or deleting items, through WordPress hooks.
 */

namespace MeshResearch\CCClient\Tests;

use MeshResearch\CCClient\Search\SearchParams;
use MeshResearch\CCClient\Search\Provisioning\ProvisionablePost;
use MeshResearch\CCClient\Search\Provisioning\ProvisionableSite;
use MeshResearch\CCClient\Search\Provisioning\ProvisionableUser;
use MeshResearch\CCClient\Search\Provisioning\IncrementalPostsProvisioner;
use MeshResearch\CCClient\Search\Provisioning\IncrementalSitesProvisioner;
use MeshResearch\CCClient\Search\Provisioning\IncrementalUsersProvisioner;

class IncrementalProvisioningTest extends CCCIncrementalProvisioningTestCase {	
	
	/**
	 * POSTS
	 */

	public function test_provision_new_post() {
		$post_provisioner = new IncrementalPostsProvisioner($this->search_api);
		$post_author = $this->factory->user->create_and_get();
		$post = $this->factory->post->create_and_get( [
			'post_author' => $post_author->ID,
		] );
		sleep(1);

		$search_params = new SearchParams(query: $post->post_title );
		$search_results = $this->search_api->search($search_params);
		$found = false;
		foreach ( $search_results->hits as $hit ) {
			if ( intval($hit->_internal_id) === $post->ID ) {
				$found = true;
				break;
			}	
		}
		$this->assertTrue( $found, 'Post not found in search results' );
	}

	public function test_provision_updated_post() {
		$post_provisioner = new IncrementalPostsProvisioner($this->search_api);
		$post_author = $this->factory->user->create_and_get();
		$post = $this->factory->post->create_and_get( [
			'post_author' => $post_author->ID,
		] );
		sleep(1);
		
		$search_params = new SearchParams(query: $post->post_title );
		$search_results = $this->search_api->search($search_params);
		$found = false;
		foreach ( $search_results->hits as $hit ) {
			if ( intval($hit->_internal_id) === $post->ID ) {
				$found = true;
				break;
			}	
		}
		$this->assertTrue( $found, 'Initial post not found in search results' );

		$post->post_title = 'Updated title';
		\wp_update_post( [
			'ID' => $post->ID,
			'post_title' => $post->post_title,
		] );
		sleep(1);
		$search_params = new SearchParams(query: $post->post_title );
		$search_results = $this->search_api->search($search_params);
		$found = false;
		$hit_title = '';
		$internal_id_count = 0;
		foreach ( $search_results->hits as $hit ) {
			if ( intval($hit->_internal_id) === $post->ID ) {
				$found = true;
				$hit_title = $hit->title;
				$internal_id_count++;
			}	
		}
		$this->assertTrue( $found, 'Updated post not found in search results' );
		$this->assertEquals( $post->post_title, $hit_title );
		$this->assertEquals( 1, $internal_id_count );
	}

	public function test_provision_deleted_post() {
		$post_provisioner = new IncrementalPostsProvisioner($this->search_api);
		$post_author = $this->factory->user->create_and_get();
		$post = $this->factory->post->create_and_get( [
			'post_author' => $post_author->ID,
		] );
		sleep(1);
		$provisionable_post = new ProvisionablePost( $post );
		$search_id = $provisionable_post->getSearchID();
		$this->assertNotEmpty( $provisionable_post->getSearchID(), 'Search ID not set' );

		\wp_delete_post( $post->ID, true );
		sleep(1);
		try {
			$deleted_document = $this->search_api->get_document( $search_id );
		} catch ( \Exception $e ) {
			$deleted_document = null;
		}
		$this->assertNull( $deleted_document, 'Deleted post found in search results' );
	}

	/**
	 * SITES
	 */

	public function test_provision_new_site() {
		$site_provisioner = new IncrementalSitesProvisioner($this->search_api);
		$site = $this->factory->blog->create_and_get();
		sleep(1);

		$provisionable_site = new ProvisionableSite( $site );
		$this->assertNotEmpty( $provisionable_site->search_id, 'Search ID not set' );

		$indexed_document = $this->search_api->get_document( $provisionable_site->search_id );
		$this->assertNotEmpty( $indexed_document, 'Site not indexed' );
	}

	public function test_provision_updated_site() {
		$site_provisioner = new IncrementalSitesProvisioner($this->search_api);
		$site = $this->factory->blog->create_and_get();
		sleep(1);

		$provisionable_site = new ProvisionableSite( $site );
		$this->assertNotEmpty( $provisionable_site->search_id, 'Search ID not set' );

		$indexed_document = $this->search_api->get_document( $provisionable_site->search_id );
		$this->assertNotEmpty( $indexed_document, 'Site not indexed' );

		update_blog_option( $site->blog_id, 'blogname', 'Updated Title' );
		sleep(1);

		$updated_document = $this->search_api->get_document( $provisionable_site->search_id );
		$this->assertEquals( 'Updated Title', $updated_document->title );
	}

	public function test_provision_deleted_site() {
		$site_provisioner = new IncrementalSitesProvisioner($this->search_api);
		$site = $this->factory->blog->create_and_get();
		sleep(1);

		$provisionable_site = new ProvisionableSite( $site );
		$this->assertNotEmpty( $provisionable_site->search_id, 'Search ID not set' );

		$indexed_document = $this->search_api->get_document( $provisionable_site->search_id );
		$this->assertNotEmpty( $indexed_document, 'Site not indexed' );

		wp_delete_site( $site->blog_id );
		sleep(1);

		try {
			$deleted_document = $search_api->get_document( $provisionable_site->search_id );
		} catch ( \Exception $e ) {
			$deleted_document = null;
		}
		$this->assertNull( $deleted_document, 'Deleted site found in search results' );
	}

	public function test_provision_private_site() {
		$site_provisioner = new IncrementalSitesProvisioner($this->search_api);
		$site = $this->factory->blog->create_and_get();
		sleep(1);

		$provisionable_site = new ProvisionableSite( $site );
		$this->assertNotEmpty( $provisionable_site->search_id, 'Search ID not set' );

		$indexed_document = $this->search_api->get_document( $provisionable_site->search_id );
		$this->assertNotEmpty( $indexed_document, 'Site not indexed' );

		update_blog_option( $site->blog_id, 'blog_public', 0 );
		sleep(1);

		try {
			$deleted_document = $search_api->get_document( $provisionable_site->search_id );
		} catch ( \Exception $e ) {
			$deleted_document = null;
		}
		$this->assertNull( $deleted_document, 'Private site found in search results' );
	}

	public function test_provision_public_site() {
		$site_provisioner = new IncrementalSitesProvisioner($this->search_api);
		$site = $this->factory->blog->create_and_get();
		sleep(1);
		update_blog_option( $site->blog_id, 'blog_public', 0 );
		sleep(1);

		$provisionable_site = new ProvisionableSite( $site );
		$this->assertEmpty( $provisionable_site->search_id, 'Search ID set for private site' );

		update_blog_option( $site->blog_id, 'blog_public', 1 );
		sleep(1);

		$provisionable_site = new ProvisionableSite( $site );
		$this->assertNotEmpty( $provisionable_site->search_id, 'Search ID not set for public site' );
	}

	/**
	 * USERS
	 */

	public function test_provision_new_user() {
		$user_provisioner = new IncrementalUsersProvisioner($this->search_api);
		$user = $this->factory->user->create_and_get();
		sleep(1);
		$provisionable_user = new ProvisionableUser( $user );
		$this->assertNotEmpty( $provisionable_user->search_id, 'Search ID not set' );
		$indexed_document = $this->search_api->get_document( $provisionable_user->search_id );
		$this->assertNotEmpty( $indexed_document, 'User not indexed' );
	}

	public function test_provision_updated_user() {
		$user_provisioner = new IncrementalUsersProvisioner($this->search_api);
		$user = $this->factory->user->create_and_get();
		sleep(1);
		$provisionable_user = new ProvisionableUser( $user );
		$this->assertNotEmpty( $provisionable_user->search_id, 'Search ID not set' );
		$indexed_document = $this->search_api->get_document( $provisionable_user->search_id );
		$this->assertNotEmpty( $indexed_document, 'User not indexed' );

		$user->display_name = 'updated';
		\wp_update_user( $user );
		sleep(1);
		$updated_document = $this->search_api->get_document( $provisionable_user->search_id );
		$this->assertEquals( 'updated', $updated_document->title );
	}

	public function test_provision_deleted_user() {
		$user_provisioner = new IncrementalUsersProvisioner($this->search_api);
		$user = $this->factory->user->create_and_get();
		sleep(1);
		$provisionable_user = new ProvisionableUser( $user );
		$this->assertNotEmpty( $provisionable_user->search_id, 'Search ID not set' );
		$indexed_document = $this->search_api->get_document( $provisionable_user->search_id );
		$this->assertNotEmpty( $indexed_document, 'User not indexed' );

		\wp_delete_user( $user->ID );
		sleep(1);
		try {
			$deleted_document = $search_api->get_document( $provisionable_user->search_id );
		} catch ( \Exception $e ) {
			$deleted_document = null;
		}
		$this->assertNull( $deleted_document, 'Deleted user found in search results' );
	}
}