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
use MeshResearch\CCClient\Search\Provisioning\ProvisionableProfile;
use MeshResearch\CCClient\Search\Provisioning\IncrementalPostsProvisioner;
use MeshResearch\CCClient\Search\Provisioning\IncrementalSitesProvisioner;
use MeshResearch\CCClient\Search\Provisioning\IncrementalProfilesProvisioner;
use MeshResearch\CCClient\Search\Provisioning\IncrementalDiscussionsProvisioner;
use MeshResearch\CCClient\Search\Provisioning\ProvisionableDiscussion;

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
		$deleted_document = $this->search_api->get_document( $search_id );
		$this->assertFalse( $deleted_document, 'Deleted post found in search results' );
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
			$deleted_document = $this->search_api->get_document( $provisionable_site->search_id );
		} catch ( \Exception $e ) {
			$deleted_document = null;
		}
		$this->assertFalse( $deleted_document, 'Deleted site found in search results' );
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
			$deleted_document = $this->search_api->get_document( $provisionable_site->search_id );
		} catch ( \Exception $e ) {
			$deleted_document = null;
		}
		$this->assertFalse( $deleted_document, 'Private site found in search results' );
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
		$user_provisioner = new IncrementalProfilesProvisioner($this->search_api);
		$user = $this->factory->user->create_and_get();
		sleep(1);
		$provisionable_user = new ProvisionableProfile( $user );
		$this->assertNotEmpty( $provisionable_user->search_id, 'Search ID not set' );
		$indexed_document = $this->search_api->get_document( $provisionable_user->search_id );
		$this->assertNotEmpty( $indexed_document, 'User not indexed' );
	}

	public function test_provision_updated_user() {
		$user_provisioner = new IncrementalProfilesProvisioner($this->search_api);
		$user = $this->factory->user->create_and_get();
		sleep(1);
		$provisionable_user = new ProvisionableProfile( $user );
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
		$user_provisioner = new IncrementalProfilesProvisioner($this->search_api);
		$user = $this->factory->user->create_and_get();
		sleep(1);
		$provisionable_user = new ProvisionableProfile( $user );
		$this->assertNotEmpty( $provisionable_user->search_id, 'Search ID not set' );
		$indexed_document = $this->search_api->get_document( $provisionable_user->search_id );
		$this->assertNotEmpty( $indexed_document, 'User not indexed' );

		\wp_delete_user( $user->ID );
		sleep(1);
		try {
			$deleted_document = $this->search_api->get_document( $provisionable_user->search_id );
		} catch ( \Exception $e ) {
			$deleted_document = null;
		}
		$this->assertFalse( $deleted_document, 'Deleted user found in search results' );
	}

	/**
	* SITES
	*/

	public function test_provision_spam_blog() {
		$site_provisioner = new IncrementalSitesProvisioner($this->search_api);
		$post_provisioner = new IncrementalPostsProvisioner($this->search_api);

		// Create a site and a post in that site
		$site = $this->factory->blog->create_and_get();
		sleep(1);

		// Switch to the new site to create a post
		switch_to_blog( $site->blog_id );
		$post_author = $this->factory->user->create_and_get();
		$post = $this->factory->post->create_and_get( [
			'post_author' => $post_author->ID,
		] );
		restore_current_blog();
		sleep(1);

		// Verify both site and post are indexed
		$provisionable_site = new ProvisionableSite( $site );
		$this->assertNotEmpty( $provisionable_site->search_id, 'Site search ID not set' );

		switch_to_blog( $site->blog_id );
		$provisionable_post = new ProvisionablePost( $post );
		$this->assertNotEmpty( $provisionable_post->getSearchID(), 'Post search ID not set' );
		restore_current_blog();

		$indexed_site = $this->search_api->get_document( $provisionable_site->search_id );
		$this->assertNotEmpty( $indexed_site, 'Site not indexed' );

		switch_to_blog( $site->blog_id );
		$indexed_post = $this->search_api->get_document( $provisionable_post->getSearchID() );
		$this->assertNotEmpty( $indexed_post, 'Post not indexed' );
		restore_current_blog();

		// Mark site as spam
		update_blog_status( $site->blog_id, 'spam', 1 );
		sleep(1);

		// Verify both site and post are removed from index
		try {
			$spammed_site = $this->search_api->get_document( $provisionable_site->search_id );
		} catch ( \Exception $e ) {
			$spammed_site = null;
		}
		$this->assertFalse( $spammed_site, 'Spammed site found in search results' );

		try {
			$spammed_post = $this->search_api->get_document( $provisionable_post->getSearchID() );
		} catch ( \Exception $e ) {
			$spammed_post = null;
		}
		$this->assertFalse( $spammed_post, 'Post from spammed site found in search results' );
	}

	public function test_provision_ham_blog() {
		$site_provisioner = new IncrementalSitesProvisioner($this->search_api);
		$post_provisioner = new IncrementalPostsProvisioner($this->search_api);

		// Create a site and mark it as spam initially
		$site = $this->factory->blog->create_and_get();
		sleep(1);

		// Switch to the site to create a post
		switch_to_blog( $site->blog_id );
		$post_author = $this->factory->user->create_and_get();
		$post = $this->factory->post->create_and_get( [
			'post_author' => $post_author->ID,
		] );
		restore_current_blog();
		sleep(1);

		$provisionable_site = new ProvisionableSite( $site );
		update_blog_status( $site->blog_id, 'spam', 1 );
		sleep(2);
		// Verify neither site nor post are indexed while spammed

		try {
			$spammed_site = $this->search_api->get_document( $provisionable_site->search_id );
		} catch ( \Exception $e ) {
			$spammed_site = null;
		}
		$this->assertFalse( $spammed_site, 'Spammed site should not be indexed' );

		switch_to_blog( $site->blog_id );
		$provisionable_post = new ProvisionablePost( $post );

		try {
			$spammed_post = $this->search_api->get_document( $provisionable_post->search_id );
		} catch ( \Exception $e ) {
			$spammed_post = null;
		}
		$this->assertFalse( $spammed_post, 'Spammed post should not be indexed' );
		restore_current_blog();

		// Unspam the site
		update_blog_status( $site->blog_id, 'spam', 0 );
		sleep(1);

		// Verify both site and post are now indexed
		$provisionable_site = new ProvisionableSite( $site );
		$indexed_site = $this->search_api->get_document( $provisionable_site->search_id );
		$this->assertNotEmpty( $indexed_site, 'Unspammed site not indexed' );

		switch_to_blog($site->blog_id);
		$indexed_post = $this->search_api->get_document( $provisionable_post->getSearchID() );
		$this->assertNotEmpty( $indexed_post, 'Post from unspammed site not indexed' );
		restore_current_blog();
	}

	public function test_provision_deleted_site_with_posts() {
		$site_provisioner = new IncrementalSitesProvisioner($this->search_api);
		$post_provisioner = new IncrementalPostsProvisioner($this->search_api);

		// Create a site and a post in that site
		$site = $this->factory->blog->create_and_get();
		sleep(1);

		// Switch to the new site to create a post
		switch_to_blog( $site->blog_id );
		$post_author = $this->factory->user->create_and_get();
		$post = $this->factory->post->create_and_get( [
			'post_author' => $post_author->ID,
		] );
		restore_current_blog();
		sleep(1);

		// Verify both site and post are indexed
		$provisionable_site = new ProvisionableSite( $site );
		$this->assertNotEmpty( $provisionable_site->search_id, 'Site search ID not set' );

		switch_to_blog( $site->blog_id );
		$provisionable_post = new ProvisionablePost( $post );
		$this->assertNotEmpty( $provisionable_post->getSearchID(), 'Post search ID not set' );
		restore_current_blog();

		$indexed_site = $this->search_api->get_document( $provisionable_site->search_id );
		$this->assertNotEmpty( $indexed_site, 'Site not indexed' );

		switch_to_blog( $site->blog_id );
		$indexed_post = $this->search_api->get_document( $provisionable_post->getSearchID() );
		$this->assertNotEmpty( $indexed_post, 'Post not indexed' );
		restore_current_blog();

		// Delete the site
		wp_delete_site( $site->blog_id );
		sleep(1);

		// Verify both site and post are removed from index
		try {
			$deleted_site = $this->search_api->get_document( $provisionable_site->search_id );
		} catch ( \Exception $e ) {
			$deleted_site = null;
		}
		$this->assertFalse( $deleted_site, 'Deleted site found in search results' );

		try {
			$deleted_post = $this->search_api->get_document( $provisionable_post->getSearchID() );
		} catch ( \Exception $e ) {
			$deleted_post = null;
		}
		$this->assertFalse( $deleted_post, 'Post from deleted site found in search results' );
	}

	/**
	 * DISCUSSIONS (bbPress topics and replies)
	 */

	public function test_provision_spam_blog_with_discussions() {
		$site_provisioner = new IncrementalSitesProvisioner($this->search_api);
		$discussions_provisioner = new IncrementalDiscussionsProvisioner($this->search_api);

		// Create a site with forum, topic, and reply
		$site = $this->factory->blog->create_and_get();
		sleep(1);

		// Switch to the new site to create bbPress content
		switch_to_blog( $site->blog_id );

		// Create a forum (parent for topics)
		$forum = $this->factory->post->create_and_get( [
			'post_type' => 'forum',
			'post_status' => 'publish',
		] );

		// Create a topic
		$topic = $this->factory->post->create_and_get( [
			'post_type' => 'topic',
			'post_status' => 'publish',
			'post_parent' => $forum->ID,
		] );

		// Create a reply
		$reply = $this->factory->post->create_and_get( [
			'post_type' => 'reply',
			'post_status' => 'publish',
			'post_parent' => $topic->ID,
		] );

		restore_current_blog();
		sleep(1);

		// Verify site, topic, and reply are indexed
		$provisionable_site = new ProvisionableSite( $site );
		$this->assertNotEmpty( $provisionable_site->search_id, 'Site search ID not set' );

		switch_to_blog( $site->blog_id );
		$provisionable_topic = new ProvisionableDiscussion( $topic );
		$provisionable_reply = new ProvisionableDiscussion( $reply );
		$this->assertNotEmpty( $provisionable_topic->getSearchID(), 'Topic search ID not set' );
		$this->assertNotEmpty( $provisionable_reply->getSearchID(), 'Reply search ID not set' );
		restore_current_blog();

		$indexed_site = $this->search_api->get_document( $provisionable_site->search_id );
		$this->assertNotEmpty( $indexed_site, 'Site not indexed' );

		switch_to_blog( $site->blog_id );
		$indexed_topic = $this->search_api->get_document( $provisionable_topic->getSearchID() );
		$indexed_reply = $this->search_api->get_document( $provisionable_reply->getSearchID() );
		$this->assertNotEmpty( $indexed_topic, 'Topic not indexed' );
		$this->assertNotEmpty( $indexed_reply, 'Reply not indexed' );
		restore_current_blog();

		// Mark site as spam
		update_blog_status( $site->blog_id, 'spam', 1 );
		sleep(1);

		// Verify site, topic, and reply are removed from index
		try {
			$spammed_site = $this->search_api->get_document( $provisionable_site->search_id );
		} catch ( \Exception $e ) {
			$spammed_site = null;
		}
		$this->assertFalse( $spammed_site, 'Spammed site found in search results' );

		try {
			$spammed_topic = $this->search_api->get_document( $provisionable_topic->getSearchID() );
		} catch ( \Exception $e ) {
			$spammed_topic = null;
		}
		$this->assertFalse( $spammed_topic, 'Topic from spammed site found in search results' );

		try {
			$spammed_reply = $this->search_api->get_document( $provisionable_reply->getSearchID() );
		} catch ( \Exception $e ) {
			$spammed_reply = null;
		}
		$this->assertFalse( $spammed_reply, 'Reply from spammed site found in search results' );
	}

	public function test_provision_ham_blog_with_discussions() {
		$site_provisioner = new IncrementalSitesProvisioner($this->search_api);
		$discussions_provisioner = new IncrementalDiscussionsProvisioner($this->search_api);

		// Create a site and mark it as spam initially
		$site = $this->factory->blog->create_and_get();
		sleep(1);

		// Switch to the site to create bbPress content
		switch_to_blog( $site->blog_id );

		// Create a forum (parent for topics)
		$forum = $this->factory->post->create_and_get( [
			'post_type' => 'forum',
			'post_status' => 'publish',
		] );

		// Create a topic
		$topic = $this->factory->post->create_and_get( [
			'post_type' => 'topic',
			'post_status' => 'publish',
			'post_parent' => $forum->ID,
		] );

		// Create a reply
		$reply = $this->factory->post->create_and_get( [
			'post_type' => 'reply',
			'post_status' => 'publish',
			'post_parent' => $topic->ID,
		] );

		restore_current_blog();
		sleep(1);

		$provisionable_site = new ProvisionableSite( $site );
		update_blog_status( $site->blog_id, 'spam', 1 );
		sleep(2);

		// Verify neither site nor discussions are indexed while spammed
		try {
			$spammed_site = $this->search_api->get_document( $provisionable_site->search_id );
		} catch ( \Exception $e ) {
			$spammed_site = null;
		}
		$this->assertFalse( $spammed_site, 'Spammed site should not be indexed' );

		switch_to_blog( $site->blog_id );
		$provisionable_topic = new ProvisionableDiscussion( $topic );
		$provisionable_reply = new ProvisionableDiscussion( $reply );

		try {
			$spammed_topic = $this->search_api->get_document( $provisionable_topic->getSearchID() );
		} catch ( \Exception $e ) {
			$spammed_topic = null;
		}
		$this->assertFalse( $spammed_topic, 'Spammed topic should not be indexed' );

		try {
			$spammed_reply = $this->search_api->get_document( $provisionable_reply->getSearchID() );
		} catch ( \Exception $e ) {
			$spammed_reply = null;
		}
		$this->assertFalse( $spammed_reply, 'Spammed reply should not be indexed' );
		restore_current_blog();

		// Unspam the site
		update_blog_status( $site->blog_id, 'spam', 0 );
		sleep(1);

		// Verify site, topic, and reply are now indexed
		$provisionable_site = new ProvisionableSite( $site );
		$indexed_site = $this->search_api->get_document( $provisionable_site->search_id );
		$this->assertNotEmpty( $indexed_site, 'Unspammed site not indexed' );

		switch_to_blog($site->blog_id);
		$indexed_topic = $this->search_api->get_document( $provisionable_topic->getSearchID() );
		$indexed_reply = $this->search_api->get_document( $provisionable_reply->getSearchID() );
		$this->assertNotEmpty( $indexed_topic, 'Topic from unspammed site not indexed' );
		$this->assertNotEmpty( $indexed_reply, 'Reply from unspammed site not indexed' );
		restore_current_blog();
	}

	public function test_provision_deleted_site_with_discussions() {
		$site_provisioner = new IncrementalSitesProvisioner($this->search_api);
		$discussions_provisioner = new IncrementalDiscussionsProvisioner($this->search_api);

		// Create a site with forum, topic, and reply
		$site = $this->factory->blog->create_and_get();
		sleep(1);

		// Switch to the new site to create bbPress content
		switch_to_blog( $site->blog_id );

		// Create a forum (parent for topics)
		$forum = $this->factory->post->create_and_get( [
			'post_type' => 'forum',
			'post_status' => 'publish',
		] );

		// Create a topic
		$topic = $this->factory->post->create_and_get( [
			'post_type' => 'topic',
			'post_status' => 'publish',
			'post_parent' => $forum->ID,
		] );

		// Create a reply
		$reply = $this->factory->post->create_and_get( [
			'post_type' => 'reply',
			'post_status' => 'publish',
			'post_parent' => $topic->ID,
		] );

		restore_current_blog();
		sleep(1);

		// Verify site, topic, and reply are indexed
		$provisionable_site = new ProvisionableSite( $site );
		$this->assertNotEmpty( $provisionable_site->search_id, 'Site search ID not set' );

		switch_to_blog( $site->blog_id );
		$provisionable_topic = new ProvisionableDiscussion( $topic );
		$provisionable_reply = new ProvisionableDiscussion( $reply );
		$this->assertNotEmpty( $provisionable_topic->getSearchID(), 'Topic search ID not set' );
		$this->assertNotEmpty( $provisionable_reply->getSearchID(), 'Reply search ID not set' );
		restore_current_blog();

		$indexed_site = $this->search_api->get_document( $provisionable_site->search_id );
		$this->assertNotEmpty( $indexed_site, 'Site not indexed' );

		switch_to_blog( $site->blog_id );
		$indexed_topic = $this->search_api->get_document( $provisionable_topic->getSearchID() );
		$indexed_reply = $this->search_api->get_document( $provisionable_reply->getSearchID() );
		$this->assertNotEmpty( $indexed_topic, 'Topic not indexed' );
		$this->assertNotEmpty( $indexed_reply, 'Reply not indexed' );
		restore_current_blog();

		// Delete the site
		wp_delete_site( $site->blog_id );
		sleep(1);

		// Verify site, topic, and reply are removed from index
		try {
			$deleted_site = $this->search_api->get_document( $provisionable_site->search_id );
		} catch ( \Exception $e ) {
			$deleted_site = null;
		}
		$this->assertFalse( $deleted_site, 'Deleted site found in search results' );

		try {
			$deleted_topic = $this->search_api->get_document( $provisionable_topic->getSearchID() );
		} catch ( \Exception $e ) {
			$deleted_topic = null;
		}
		$this->assertFalse( $deleted_topic, 'Topic from deleted site found in search results' );

		try {
			$deleted_reply = $this->search_api->get_document( $provisionable_reply->getSearchID() );
		} catch ( \Exception $e ) {
			$deleted_reply = null;
		}
		$this->assertFalse( $deleted_reply, 'Reply from deleted site found in search results' );
	}
}
