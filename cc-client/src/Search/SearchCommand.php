<?php
/**
 * WP CLI command for provisioning and querying the search service.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Search;

use MeshResearch\CCClient\Search\SearchAPI;
use MeshResearch\CCClient\CCClientOptions;

use MeshResearch\CCClient\Search\Provisioning\ProvisionableGroup;
use MeshResearch\CCClient\Search\Provisioning\ProvisionablePost;
use MeshResearch\CCClient\Search\Provisioning\ProvisionableSite;
use MeshResearch\CCClient\Search\Provisioning\ProvisionableUser;

use function MeshResearch\CCClient\Search\Provisioning\get_network_nodes;
use function MeshResearch\CCClient\Search\Provisioning\get_provisionable;
use function MeshResearch\CCClient\Search\Provisioning\bulk_provision;

class SearchCommand {
	private SearchAPI $search_api;
	private CCClientOptions $options;

	public function __construct() {
		$this->options = new CCClientOptions( incremental_provisioning_enabled: false);
		$this->search_api = new SearchAPI( $this->options );
	}
	
	/**
	 * Ping the search service
	 */
	public function ping() {
		\WP_CLI::line( 'Pinging the search service...' );
		try {
			$response = $this->search_api->ping();
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
		if ( empty( $this->options->cc_search_key ) ) {
			\WP_CLI::warning( 'Search service API key is not configured.' );
		} else {
			\WP_CLI::success( 'Search service API key is configured.' );
		}
		if ( empty( $this->options->cc_search_admin_key ) ) {
			\WP_CLI::warning( 'Search service admin API key is not configured.' );
		} else {
			\WP_CLI::success( 'Search service admin API key is configured.' );
		}
		if ( empty( $this->options->cc_search_endpoint ) ) {
			\WP_CLI::warning( 'Search service endpoint is not configured.' );
		} else {
			\WP_CLI::success( 'Search service endpoint: ' . $this->options->cc_search_endpoint );
		}

		$response = $this->search_api->ping();
		if ( $response === true ) {
			\WP_CLI::success( 'Search service is up and running.' );
		} else {
			\WP_CLI::warning( 'Search service is not responding.' );
		}

		$response = $this->search_api->check_api_key();
		if ( $response === true ) {
			\WP_CLI::success( 'Search service API key is valid.' );
		} else {
			\WP_CLI::warning( 'Search service API key is not valid.' );
		}

		$response = $this->search_api->check_admin_api_key();
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
	 * [<id>]
	 * : The ID of the document to retrieve.
	 * 
	 * [--id=<id>]
	 * : The ID of the document to retrieve.
	 * 
	 * [--internal_id=<internal_id>]
	 * : The internal ID of the document to retrieve. (Requires --type option.)
	 * 
	 * [--type=<type>]
	 * : The type of document to retrieve.
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
		} elseif ( ! empty( $assoc_args['internal_id'] ) && ! empty( $assoc_args['type'] ) ) {
			$type = $assoc_args['type'];
			$internal_id = $assoc_args['internal_id'];
			\WP_CLI::line( 'Getting document by internal ID...' );
			switch ( $type ) {
				case 'user':
					$item = get_user_by( 'ID', $internal_id );
					if ( ! $item ) {
						\WP_CLI::error( 'Invalid user ID' );
					}
					$provisioner = new ProvisionableUser( $item );
					break;
				case 'group':
					$item = new \BP_Groups_Group( $internal_id );
					if ( ! $item->id ) {
						\WP_CLI::error( 'Invalid group ID' );
					}
					$provisioner = new ProvisionableGroup( $item );
					break;
				case 'site':
					$item = get_site( $internal_id );
					if ( ! $item ) {
						\WP_CLI::error( 'Invalid site ID' );
					}
					$provisioner = new ProvisionableSite( $item );
					break;
				case 'post':
					$item = get_post( $internal_id );
					if ( ! $item ) {
						\WP_CLI::error( 'Invalid post ID' );
					}
					$provisioner = new ProvisionablePost( $item );
					break;
				default:
					\WP_CLI::error( 'Invalid document type' );
			}
			$doc_id = $provisioner->getSearchID();
			if ( empty( $doc_id ) ) {
				\WP_CLI::error( 'Document not found.' );
			}
		}
		else {
			\WP_CLI::error( 'Document ID is required.' );
		}
		\WP_CLI::line( 'Getting document...' );
		try {
			$response = $this->search_api->get_document( $doc_id );
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
			$provisionable_item = get_provisionable( $type, $wpid );
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
			$provisionable_item = get_provisionable( $type, $wpid );
		} catch ( \Exception $e ) {
			\WP_CLI::error( $e->getMessage() );
		}
		$document = $provisionable_item->toDocument();
		try {
			$indexed_document = $this->search_api->index_or_update( $document );
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

		if ( ! $this->search_api->check_api_key() ) {
			\WP_CLI::error( 'Search service API key is not valid.' );
		}
		if ( ! $this->search_api->check_admin_api_key() ) {
			\WP_CLI::error( 'Search service admin API key is not valid.' );
		}
		
		\WP_CLI::line( 'Resetting documents from all nodes...' );
		$network_nodes = get_network_nodes();
		foreach ( $network_nodes as $node ) {
			try {
				$deleted = $this->search_api->delete_node( $node );
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