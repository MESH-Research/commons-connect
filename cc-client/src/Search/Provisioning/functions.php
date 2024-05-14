<?php
/**
 * Helper functions for search provisioning.
 * 
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Search\Provisioning;

use MeshResearch\CCClient\Search\SearchAPI;

function bulk_provision( array $document_types, SearchAPI $search_api ): void {
	$documents = [];
	if ( in_array( 'post', $document_types ) ) {
		$documents = array_merge($documents, ProvisionablePost::getAllAsDocuments());
	}
	if ( in_array( 'user', $document_types ) ) {
		$documents = array_merge($documents, ProvisionableUser::getAllAsDocuments());
	}
	if ( in_array( 'group', $document_types ) ) {
		$documents = array_merge($documents, ProvisionableGroup::getAllAsDocuments());
	}
	if ( in_array( 'site', $document_types ) ) {
		$documents = array_merge($documents, ProvisionableSite::getAllAsDocuments());
	}
		
	// Send documents to the search service.
	$indexed_documents = $search_api->bulk_index( $documents );
	foreach ( $indexed_documents as $document ) {
		if ( empty( $document->_id ) ) {
			error_log( 'Failed to index document: ' . print_r( $document, true ) );
			continue;
		}
		if ( empty( $document->_internal_id ) ) {
			error_log( 'Failed to update internal ID for document: ' . print_r( $document, true ) );
			continue;
		}
		update_post_meta( $document->_internal_id, 'cc_search_id', $document->_id );
	}
}

function get_profile_url( \WP_User $user ): string {
	if ( function_exists( 'bp_members_get_user_url' ) ) {
		return bp_members_get_user_url( $user->ID );
	}

	if ( function_exists( 'bp_core_get_user_domain' ) ) {
		return bp_core_get_user_domain( $user->ID );
	}

	return get_author_posts_url( $user->ID );
}

function get_current_network_node(): string {
	if ( class_exists( '\Humanities_Commons') ) {
		return \Humanities_Commons::$society_id;
	}

	else return '';
}