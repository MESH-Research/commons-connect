<?php
/**
 * Functions for updating search index when items are created, updated, deleted,
 * or have their visibility change.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Search\Provisioning;

use MeshResearch\CCClient\Search\SearchAPI;

/**
 * Sites
 */

function provision_new_site( \WP_Site $site ) {
	$provisionable_site = new ProvisionableSite( $site );
	$search_api = new SearchAPI();
	$search_api->index( $provisionable_site->toDocument() );
}
add_action( 'wp_insert_site', __NAMESPACE__ . '\provision_new_site' );

function provision_updated_site( \WP_Site $site ) {
	$provisionable_site = new ProvisionableSite( $site );
	$provisionable_site->updateSearchID();
	$search_api = new SearchAPI();
	$search_api->index_or_update( $provisionable_site->toDocument() );
}
add_action( 'wp_update_site', __NAMESPACE__ . '\provision_updated_site' );

function provision_deleted_site( \WP_Site $site ) {
	$provisionable_site = new ProvisionableSite( $site );
	$search_id = $provisionable_site->getSearchID();
	if ( empty( $search_id ) ) {
		return;
	}
	$search_api = new SearchAPI();
	$search_api->delete( $search_id );
}
add_action( 'wp_delete_site', __NAMESPACE__ . '\provision_deleted_site' );

/**
 * Update search index when site visibility changes.
 * 
 * Visibility is stored as a string in the 'blog_public' option, encoded on KC as:
 *   - '1' public and friendly to search engines
 *   - '0' not public and not friendly to search engines
 *   - '-1' visibile only to registered users of that network
 *   - '-2' visible only to registered users of that site
 *   - '-3' visible only to administrators of that site
 */
function provision_site_visibility_change( int $blog_id, string $old_value, string $new_value ) {
	$new_visibility = intval( $new_value );

	$site = get_blog_details( $blog_id );
	$provisionable_site = new ProvisionableSite( $site );
	$provisionable_site->updateSearchID();
	$search_api = new SearchAPI();

	if ( $new_visibility > 0 ) {
		$search_api->index_or_update( $provisionable_site->toDocument() );
	} elseif ( ! empty( $provisionable_site->search_id ) ) {
		$search_api->delete( $provisionable_site->search_id );
		$provisionable_site->setSearchID( '' );
	}
}
add_action( 'update_option_blog_public', __NAMESPACE__ . '\provision_site_visibility_change', 10, 3 );

/**
 * Users
 */

function provision_new_or_updated_user( int $user_id ) {
	$user = get_userdata( $user_id );
	$provisionable_user = new ProvisionableUser( $user );
	$provisionable_user->updateSearchID();
	$search_api = new SearchAPI();

	if ( 
		isset( $user->spam ) && 
		intval( $user->spam ) === 1 && 
		! empty( $provisionable_user->search_id ) 
	) {
		$search_api->delete( $provisionable_user->search_id );
		$provisionable_user->setSearchID( '' );
		return;
	}

	$search_api->index_or_update( $provisionable_user->toDocument() );
}
add_action( 'profile_update', __NAMESPACE__ . '\provision_new_or_updated_user' );
add_action( 'xprofile_updated_profile', __NAMESPACE__ . '\provision_new_or_updated_user' );
add_action( 'wpmu_new_user', __NAMESPACE__ . '\provision_new_or_updated_user' );
add_action( 'user_register', __NAMESPACE__ . '\provision_new_or_updated_user' );
add_action( 'wp_update_user', __NAMESPACE__ . '\provision_new_or_updated_user' );

function provision_deleted_user( int $user_id ) {
	$user = get_userdata( $user_id );
	$provisionable_user = new ProvisionableUser( $user );
	$search_id = $provisionable_user->getSearchID();
	if ( empty( $search_id ) ) {
		return;
	}
	$search_api = new SearchAPI();
	$search_api->delete( $search_id );
	$provisionable_user->setSearchID( '' );
}
add_action( 'delete_user', __NAMESPACE__ . '\provision_deleted_user' );

/**
 * Groups
 */

function provision_new_or_updated_group( \BP_Groups_Group $group ) {
	$provisionable_group = new ProvisionableGroup( $group );
	$provisionable_group->updateSearchID();
	$search_api = new SearchAPI();
	$search_api->index_or_update( $provisionable_group->toDocument() );
}
add_action( 'groups_group_create_complete', __NAMESPACE__ . '\provision_new_group' );
add_action( 'groups_group_after_save', __NAMESPACE__ . '\provision_updated_group' );

function provision_deleted_group( int $group_id ) {
	$group = new \BP_Groups_Group( $group_id );
	$provisionable_group = new ProvisionableGroup( $group );
	$search_id = $provisionable_group->getSearchID();
	if ( empty( $search_id ) ) {
		return;
	}
	$search_api = new SearchAPI();
	$search_api->delete( $search_id );
	$provisionable_group->setSearchID( '' );
}
add_action( 'groups_before_delete_group', __NAMESPACE__ . '\provision_deleted_group' );

/**
 * Posts & Discussions
 */

function provision_new_or_updated_post( int $post_id ) {
	$post = get_post( $post_id );
	if ( $post->post_type == 'topic' || $post->post_type == 'reply' ) {
		$provisionable_item = new ProvisionableDiscussion( $post );
	} else {
		$provisionable_item = new ProvisionablePost( $post );
	}
	$provisionable_item->updateSearchID();
	$search_api = new SearchAPI();
	
	if ( $post->status !== 'publish' && ! empty( $provisionable_item->search_id ) ) {
		$search_api->delete( $provisionable_item->search_id );
		$provisionable_item->setSearchID( '' );
		return;
	}

	if ( is_a( $provisionable_item, ProvisionableDiscussion::class ) ) {
		if ( ! $provisionable_item->is_public() && ! empty( $provisionable_item->search_id ) ) {
			$search_api->delete( $provisionable_item->search_id );
			$provisionable_item->setSearchID( '' );
			return;
		}
	}
	
	$search_api->index_or_update( $provisionable_post->toDocument() );
}
add_action( 'save_post', __NAMESPACE__ . '\provision_new_or_updated_post' );
