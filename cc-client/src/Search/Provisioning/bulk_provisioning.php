<?php
/**
 * Bulk provisioning.
 * 
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Search\Provisioning;

use MeshResearch\CCClient\Search\SearchAPI;

function bulk_provision( array $document_types, SearchAPI $search_api, bool $show_progress = false ): void {
	$documents = [];
	if ( in_array( 'post', $document_types ) ) {
		$additional_documents = ProvisionablePost::getAllAsDocuments( reset: true, show_progress: $show_progress );
		if ( $show_progress && class_exists( 'WP_CLI' ) ) {
			\WP_CLI::line( 'Provisioning ' . count( $additional_documents ) . ' posts...' );
		}
		$documents = array_merge($documents, $additional_documents);
	}
	if ( in_array( 'profile', $document_types ) ) {
		$additional_documents = ProvisionableProfile::getAllAsDocuments( reset: true, show_progress: $show_progress );
		if ( $show_progress && class_exists( 'WP_CLI' ) ) {
			\WP_CLI::line( 'Provisioning ' . count( $additional_documents ) . ' users...' );
		}
		$documents = array_merge($documents, $additional_documents);
	}
	if ( in_array( 'group', $document_types ) ) {
		$additional_documents = ProvisionableGroup::getAllAsDocuments( reset: true, show_progress: $show_progress );
		if ( $show_progress && class_exists( 'WP_CLI' ) ) {
			\WP_CLI::line( 'Provisioning ' . count( $additional_documents ) . ' groups...' );
		}
		$documents = array_merge($documents, $additional_documents );
	}
	if ( in_array( 'site', $document_types ) ) {
		$additional_documents = ProvisionableSite::getAllAsDocuments( reset: true, show_progress: $show_progress );
		if ( $show_progress && class_exists( 'WP_CLI' ) ) {
			\WP_CLI::line( 'Provisioning ' . count( $additional_documents ) . ' sites...' );
		}
		$documents = array_merge($documents, $additional_documents );
	}
	if ( in_array( 'discussion', $document_types ) ) {
		$additional_documents = ProvisionableDiscussion::getAllAsDocuments(
			reset: true,
			show_progress: $show_progress,
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
			if ( $show_progress && class_exists( 'WP_CLI' ) ) {
				\WP_CLI::warning( 'Failed to index document: ' . $document->title );
			}
			continue;
		}
		if ( empty( $document->_internal_id ) ) {
			error_log( 'Failed to update internal ID for document: ' . print_r( $document, true ) );
			if ( $show_progress && class_exists( 'WP_CLI' ) ) {
				\WP_CLI::warning( 'Failed to update internal ID for document: ' . $document->title . 'id: ' . $document->_id );
			}
			continue;
		}
		try {
			$provisioner = get_provisionable(
				type: $document->content_type,
				wpid: $document->_internal_id
			);
			$provisioner->setSearchID( $document->_id );
		} catch ( \Exception $e ) {
			error_log( 'Failed to update internal ID for document: ' . print_r( $document, true ) );
			if ( $show_progress && class_exists( 'WP_CLI' ) ) {
				\WP_CLI::warning( 'Failed to update internal ID for document: ' . $document->title . 'id: ' . $document->_id );
			}
		}
	}
	if ( $show_progress && class_exists( 'WP_CLI' ) ) {
		\WP_CLI::success( 'Provisioning complete' );
	}
}