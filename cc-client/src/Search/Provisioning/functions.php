<?php
/**
 * Helper functions for search provisioning.
 * 
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Search\Provisioning;

use MeshResearch\CCClient\Search\SearchAPI;

function bulk_provision( array $document_types, SearchAPI $search_api, bool $show_progress = false ): void {
	$documents = [];
	if ( in_array( 'post', $document_types ) ) {
		$additional_documents = ProvisionablePost::getAllAsDocuments();
		if ( $show_progress && class_exists( 'WP_CLI' ) ) {
			\WP_CLI::line( 'Provisioning ' . count( $additional_documents ) . ' posts...' );
		}
		$documents = array_merge($documents, $additional_documents);
	}
	if ( in_array( 'user', $document_types ) ) {
		$additional_documents = ProvisionableUser::getAllAsDocuments();
		if ( $show_progress && class_exists( 'WP_CLI' ) ) {
			\WP_CLI::line( 'Provisioning ' . count( $additional_documents ) . ' users...' );
		}
		$documents = array_merge($documents, $additional_documents);
	}
	if ( in_array( 'group', $document_types ) ) {
		$additional_documents = ProvisionableGroup::getAllAsDocuments();
		if ( $show_progress && class_exists( 'WP_CLI' ) ) {
			\WP_CLI::line( 'Provisioning ' . count( $additional_documents ) . ' groups...' );
		}
		$documents = array_merge($documents, $additional_documents );
	}
	if ( in_array( 'site', $document_types ) ) {
		$additional_documents = ProvisionableSite::getAllAsDocuments();
		if ( $show_progress && class_exists( 'WP_CLI' ) ) {
			\WP_CLI::line( 'Provisioning ' . count( $additional_documents ) . ' sites...' );
		}
		$documents = array_merge($documents, $additional_documents );
	}
	if ( in_array( 'discussion', $document_types ) ) {
		$additional_documents = ProvisionableDiscussion::getAllAsDocuments(
			post_types: [ 'reply', 'topic' ]
		);
		if ( $show_progress && class_exists( 'WP_CLI' ) ) {
			\WP_CLI::line( 'Provisioning ' . count( $additional_documents ) . ' discussion posts...' );
		}
		$documents = array_merge($documents, $additional_documents );
	}
		
	// Send documents to the search service.
	$indexed_documents = $search_api->bulk_index( $documents, $show_progress );
	if ( $show_progress && class_exists( 'WP_CLI' ) ) {
		\WP_CLI::line( 'Updating WordPress metadata...' );
	}
	foreach ( $indexed_documents as $document ) {
		if ( empty( $document->_id ) ) {
			error_log( 'Failed to index document: ' . print_r( $document, true ) );
			continue;
		}
		if ( empty( $document->_internal_id ) ) {
			error_log( 'Failed to update internal ID for document: ' . print_r( $document, true ) );
			continue;
		}
		$provisioner = get_provisioner_by_type(
			type: $document->content_type,
			wpid: $document->_internal_id
		);
		$provisioner->setSearchID( $document->_id );
	}
	if ( $show_progress && class_exists( 'WP_CLI' ) ) {
		\WP_CLI::success( 'Provisioning complete' );
	}
}

function get_profile_url( \WP_User $user ): string {
	if ( 
		! function_exists( 'bp_core_enable_root_profiles' ) || 
		! function_exists( 'bp_get_members_root_slug' ) || 
		! function_exists( 'bp_core_get_username' )
	) {
		return get_author_posts_url( $user->ID );
	}
	
	//See: bp-members-functions.php::bp_core_get_user_domain()
	$username = bp_core_get_username( $user->ID );
	$base_url = get_home_url( get_current_blog_id() );
	$after_domain = bp_core_enable_root_profiles() ? $username : bp_get_members_root_slug() . '/' . $username;
	return trailingslashit( $base_url ) . $after_domain;
}

function get_current_network_node(): string {
	return get_network_option( null, 'society_id', '' );
}

function get_other_profile_urls( \WP_User $user ): array {
	$primary_url = get_profile_url( $user );
	$other_urls = [];
	$networks = get_networks();
	foreach ( $networks as $network ) {
		switch_to_network( $network->id );
		switch_to_blog( $network->blog_id );
		$profile_url = get_profile_url( $user );
		if ( $profile_url != $primary_url ) {
			$other_urls[] = $profile_url;
		}
		restore_current_blog();
		restore_current_network();
	}
	return $other_urls;
}

function get_provisioner_by_type( string $type, string $wpid ): ProvisionableUser | ProvisionableGroup | ProvisionableSite | ProvisionablePost {
	switch ( $type ) {
		case 'user':
			$item = get_user_by( 'ID', $wpid );
			if ( ! $item ) {
				throw new \Exception( 'Invalid user ID' );
			}
			return new ProvisionableUser( $item );
		case 'group':
			$item = new \BP_Groups_Group( $wpid );
			if ( ! $item->id ) {
				throw new \Exception( 'Invalid group ID' );
			}
			return new ProvisionableGroup( $item );
		case 'site':
			$item = get_blog_details( $wpid );
			if ( ! $item ) {
				throw new \Exception( 'Invalid site ID' );
			}
			return new ProvisionableSite( $item );
		case 'post':
			$item = get_post( $wpid );
			if ( ! $item ) {
				throw new \Exception( 'Invalid post ID' );
			}
			return new ProvisionablePost( $item );
		case 'discussion':
			$item = get_post( $wpid );
			if ( ! $item ) {
				throw new \Exception( 'Invalid post ID' );
			}
			return new ProvisionablePost( $item );
		default:
			throw new \Exception( 'Invalid provisionable type: ' . $type );
	}
}

function get_available_provisioners(): array {
	$provisioners = [];
	if ( ProvisionablePost::isAvailable() ) {
		$provisioners[] = 'post';
		$provisioners[] = 'discussion';
	}
	if ( ProvisionableUser::isAvailable() ) {
		$provisioners[] = 'user';
	}
	if ( ProvisionableGroup::isAvailable() ) {
		$provisioners[] = 'group';
	}
	if ( ProvisionableSite::isAvailable() ) {
		$provisioners[] = 'site';
	}
	return $provisioners;
}

/**
 * Get all network nodes.
 */
function get_network_nodes(): array {
	$nodes = [];
	$networks = get_networks();
	foreach ( $networks as $network ) {
		$society_id = get_network_option( $network->id, 'society_id' );
		if ( $society_id ) {
			$nodes[] = $society_id;
		}
	}
	return $nodes;
}