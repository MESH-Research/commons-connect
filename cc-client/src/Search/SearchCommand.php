<?php
/**
 * WP CLI command for provisioning and querying the search service.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Search;

use MeshResearch\CCClient\Search\SearchAPI;

use function MeshResearch\CCClient\get_ccc_options;
use function MeshResearch\CCClient\Search\Provisioning\get_network_nodes;
use function MeshResearch\CCClient\Search\Provisioning\get_provisioner_by_type;
use function MeshResearch\CCClient\Search\Provisioning\bulk_provision;

class SearchCommand {
	/**
	 * Ping the search service
	 */
	public function ping() {
		\WP_CLI::line( 'Pinging the search service...' );
		try {
			$search_api = new SearchAPI();
		} catch ( \Exception $e ) {
			\WP_CLI::error( $e->getMessage() );
		}
		try {
			$response = $search_api->ping();
		} catch ( \Exception $e ) {
			$response = false;
		}
		if ( $response === true ) {
			\WP_CLI::success( 'Search service is up and running.' );
		} else {
			\WP_CLI::error( 'Search service is not responding.' );
		}
	}

	/**
	 * Output some status information about the search service
	 */
	public function status() {
		$options = get_ccc_options();
		if ( empty( $options['cc_search_key'] ) ) {
			\WP_CLI::warning( 'Search service API key is not configured.' );
		} else {
			\WP_CLI::success( 'Search service API key is configured.' );
		}
		if ( empty( $options['cc_search_admin_key'] ) ) {
			\WP_CLI::warning( 'Search service admin API key is not configured.' );
		} else {
			\WP_CLI::success( 'Search service admin API key is configured.' );
		}
		if ( empty( $options['cc_search_endpoint'] ) ) {
			\WP_CLI::warning( 'Search service endpoint is not configured.' );
		} else {
			\WP_CLI::success( 'Search service endpoint: ' . $options['cc_search_endpoint'] );
		}
		try {
			$search_api = new SearchAPI();
			\WP_CLI::success( 'Search service is configured.' );
		} catch ( \Exception $e ) {
			\WP_CLI::error( 'Search service is not configured: ' . $e->getMessage() );
		}

		$response = $search_api->ping();
		if ( $response === true ) {
			\WP_CLI::success( 'Search service is up and running.' );
		} else {
			\WP_CLI::warning( 'Search service is not responding.' );
		}

		$response = $search_api->check_api_key();
		if ( $response === true ) {
			\WP_CLI::success( 'Search service API key is valid.' );
		} else {
			\WP_CLI::warning( 'Search service API key is not valid.' );
		}

		$response = $search_api->check_admin_api_key();
		if ( $response === true ) {
			\WP_CLI::success( 'Search service admin API key is valid.' );
		} else {
			\WP_CLI::warning( 'Search service admin API key is not valid.' );
		}

		if ( class_exists( 'BP_Groups_Group' ) ) {
			\WP_CLI::line( 'BuddyPress Groups is active.' );
		} else {
			\WP_CLI::line( 'BuddyPress Groups is not active.' );
		}
		if ( class_exists( 'BP_XProfile_ProfileData' ) ) {
			\WP_CLI::line( 'BuddyPress XProfile is active.' );
		} else {
			\WP_CLI::line( 'BuddyPress XProfile is not active.' );
		}
		if ( class_exists( 'Humanities_Commons' ) ) {
			\WP_CLI::line( 'Humanities Commons is active.' );
			\WP_CLI::line( 'Society ID: ' . \Humanities_Commons::$society_id );
		} else {
			\WP_CLI::line( 'Humanities Commons is not active.' );
		}
		\WP_CLI::line( 'SiteURL: ' . get_site_url() );
		\WP_CLI::line( 'SiteID: ' . get_current_blog_id() );
		if ( is_multisite() ) {
			$network_id = get_current_network_id();
			$network = get_network( $network_id );
			\WP_CLI::line( 'Network ID: ' . $network_id );
			\WP_CLI::line( 'Network domain: ' . $network->domain );
		} else {
			\WP_CLI::line( 'Multisite is not enabled.' );
		}
	}

	/**
	 * Get a document by ID
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the document to retrieve.
	 * 
	 * [--id=<id>]
	 * : The ID of the document to retrieve.
	 *
	 * ## EXAMPLES
	 *
	 *   wp cc search get_document 123
	 *   wp cc search get_document --id=123
	 */
	public function get_document( $args, $assoc_args ) {
		if ( ! empty( $args) ) {
			$doc_id = $args[0];
		} elseif ( ! empty( $assoc_args['id'] ) ) {
			$doc_id = $assoc_args['id'];
		} else {
			\WP_CLI::error( 'Document ID is required.' );
		}
		\WP_CLI::line( 'Getting document...' );
		$search_api = new SearchAPI();
		try {
			$response = $search_api->get_document( $doc_id );
		} catch ( \Exception $e ) {
			\WP_CLI::error( $e->getMessage() );
		}
		\WP_CLI::line( $response->toJSON() );
	}

	/**
	 * Get indexing status of an item.
	 *
	 * ## OPTIONS
	 * 
	 * --type=<type>
	 * : The type of item to check the status of: user, group, site, or post.
	 *
	 * --wpid=<wpid>
	 * : The WordPress ID of the item to check the status of.
	 */
	public function item_status( $args, $assoc_args ) {
		if ( empty( $assoc_args['type'] ) ) {
			\WP_CLI::error( 'Item type is required.' );
		}
		if ( empty( $assoc_args['wpid'] ) ) {
			\WP_CLI::error( 'WordPress ID is required.' );
		}
		$type = $assoc_args['type'];
		$wpid = $assoc_args['wpid'];
		\WP_CLI::line( 'Getting status of ' . $type . ' ' . $wpid . '...' );
		try {
			$provisionable_item = get_provisioner_by_type( $type, $wpid );
		} catch ( \Exception $e ) {
			\WP_CLI::error( $e->getMessage() );
		}
		try {
			$search_id = $provisionable_item->getSearchID();
		} catch ( \Exception $e ) {
			\WP_CLI::error( $e->getMessage() );
		}
		if ( empty( $search_id ) ) {
			\WP_CLI::line( 'Item is not indexed.' );
		} else {
			\WP_CLI::line( 'Item is indexed with ID: ' . $search_id );
		}
	}

	/**
	 * Provision an item to the search service.
	 */
	public function provision( $args, $assoc_args ) {
		if ( empty( $assoc_args['type'] ) ) {
			\WP_CLI::error( 'Item type is required.' );
		}
		if ( empty( $assoc_args['wpid'] ) ) {
			\WP_CLI::error( 'WordPress ID is required.' );
		}
		$type = $assoc_args['type'];
		$wpid = $assoc_args['wpid'];
		\WP_CLI::line( 'Provisioning ' . $type . ' ' . $wpid . '...' );
		try {
			$provisionable_item = get_provisioner_by_type( $type, $wpid );
		} catch ( \Exception $e ) {
			\WP_CLI::error( $e->getMessage() );
		}
		$search_api = new SearchAPI();
		$document = $provisionable_item->toDocument();
		try {
			$indexed_document = $search_api->index_or_update( $document );
		} catch ( \Exception $e ) {
			\WP_CLI::error( $e->getMessage() );
		}
		if ( empty( $indexed_document->_id ) ) {
			\WP_CLI::error( 'Failed to index document.' );
		}
		$provisionable_item->setSearchID( $indexed_document->_id );
		\WP_CLI::success( 'Item provisioned with ID: ' . $indexed_document->_id );
	}

	/**
	 * Bulk provision items to the search service.
	 */
	public function provision_all( $args, $assoc_args ) {
		$on_base_site = false;
		$networks = get_networks();
		foreach ( $networks as $network ) {
			if ( intval($network->blog_id) === get_current_blog_id() ) {
				$on_base_site = true;
				break;
			}
		}
		if ( ! $on_base_site ) {
			\WP_CLI::error( 'This command must be run on a base site.' );
		}
		$search_api = new SearchAPI();

		if ( ! $search_api->check_api_key() ) {
			\WP_CLI::error( 'Search service API key is not valid.' );
		}
		if ( ! $search_api->check_admin_api_key() ) {
			\WP_CLI::error( 'Search service admin API key is not valid.' );
		}
		
		\WP_CLI::line( 'Resetting documents from all nodes...' );
		$network_nodes = get_network_nodes();
		foreach ( $network_nodes as $node ) {
			try {
				$deleted = $search_api->delete_node( $node );
				if ( ! $deleted ) {
					\WP_CLI::warning( 'Failed to delete documents from node: ' . $node );
				}
			} catch ( \Exception $e ) {
				\WP_CLI::error( $e->getMessage() );
			}
		}

		\WP_CLI::line( 'Provisioning users...' );
		try {
			bulk_provision(
				document_types: [ 'user' ],
				search_api: $search_api,
				show_progress: true
			);
		} catch ( \Exception $e ) {
			\WP_CLI::error( $e->getMessage() );
		}

		\WP_CLI::line( 'Provisioning sites...' );
		try {
			bulk_provision(
				document_types: [ 'site' ],
				search_api: $search_api,
				show_progress: true
			);
		} catch ( \Exception $e ) {
			\WP_CLI::error( $e->getMessage() );
		}

		\WP_CLI::line( 'Provisioning groups...' );
		try {
			bulk_provision(
				document_types: [ 'group' ],
				search_api: $search_api,
				show_progress: true
			);
		} catch ( \Exception $e ) {
			\WP_CLI::error( $e->getMessage() );
		}

		\WP_CLI::line( 'Provisioning discussion posts...' );
		foreach ( $networks as $network ) {
			switch_to_blog( $network->blog_id );
			\WP_CLI::line( 'Provisioning discussion posts for ' . $network->domain . '...' );
			try {
				bulk_provision(
					document_types: [ 'discussion' ],
					search_api: $search_api,
					show_progress: true
				);
			} catch ( \Exception $e ) {
				\WP_CLI::error( $e->getMessage() );
			}
			restore_current_blog();
		}

		\WP_CLI::line( 'Provisioning posts...' );
		$blogs = get_sites([ 'number' => 50000 ]);
		\WP_CLI::line( 'Provisioning posts for ' . count( $blogs ) . ' blogs...' );
		foreach ( $blogs as $blog ) {
			\WP_CLI::line( 'Provisioning posts for ' . $blog->domain . '...' );
			switch_to_blog( $blog->blog_id );
			try {
				bulk_provision(
					document_types: [ 'post' ],
					search_api: $search_api,
					show_progress: false
				);
			} catch ( \Exception $e ) {
				\WP_CLI::error( $e->getMessage() );
			}
			restore_current_blog();
		}

		\WP_CLI::success( 'Bulk provisioning complete.' );
	}
}