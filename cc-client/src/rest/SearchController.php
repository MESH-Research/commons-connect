<?php
/**
 * REST controller for plugin search.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient;

use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Request;

use MeshResearch\CCClient\Search\SearchAPI;
use MeshResearch\CCClient\CCClientOptions;
use MeshResearch\CCClient\Search\SearchParams;

/**
 * REST controller for search
 */
class SearchController extends WP_REST_Controller {
    protected $namespace;
    protected $resource_name;
    protected $schema;

    public function __construct() {
        $this->namespace     = CC_CLIENT_REST_NAMESPACE;
        $this->resource_name = 'search';
        $this->options = new CCClientOptions();
        $this->search_api = new SearchAPI( $this->options );
    }

    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->resource_name,
            [
                [
                    'methods'             => 'GET',
                    'callback'            => [ $this, 'get_results' ],
                    'permission_callback' => '__return_true',
                ],
                'schema' => [ $this, 'get_item_schema' ],
            ]
        );
    }

	public function get_results( WP_REST_Request $request ) : WP_REST_Response {
		$parameters = $request->get_query_params();
        $search_params = SearchParams::fromQueryParams( $parameters );
        $results = $this->search_api->search( $search_params );
		return new WP_REST_Response( $results );
	}

    public function get_items_permission_check( WP_REST_Request $request ) : bool {
        return true;
    }


    public function get_item_schema() : array {
        if ( $this->schema ) {
            return $this->schema;
        }

        $schema = [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title'   => 'cc_client_options',
            'type'    => 'object',
            'properties' => [
                'cc_server_url' => [
                    'description' => esc_html__( 'URL of the Commons Connect server.', 'cc-client' ),
                    'type'        => 'string',
                    'format'      => 'uri',
                ],
                'cc_search_endpoint' => [
                    'description' => esc_html__( 'Search endpoint on the Commons Connect server.', 'cc-client' ),
                    'type'        => 'string',
                    'format'      => 'uri',
                ],
                'cc_search_key' => [
                    'description' => esc_html__( 'API key for the Commons Connect search endpoint.', 'cc-client' ),
                    'type'        => 'string',
                ],
            ]
        ];

        return $schema;
    }
}
