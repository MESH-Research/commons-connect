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
    }

    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->resource_name,
            [
                [
                    'methods'             => 'GET',
                    'callback'            => [ $this, 'get_options' ],
                    'permission_callback' => '__return_true',
                ],
                [
                    'methods'             => 'POST',
                    'callback'            => [ $this, 'update_options' ],
                    'permission_callback' => '__return_true',
                ],
                'schema' => [ $this, 'get_item_schema' ],
            ]
        );
    }

    public function get_items_permission_check( WP_REST_Request $request ) : bool {
        return current_user_can( 'manage_options' );
    }

    public function update_options_permission_check( WP_REST_Request $request ) : bool {
        return current_user_can( 'manage_options' );
    }

    public function get_options( WP_REST_Request $request ) : string {
        return "Hello World";
    }

    public function update_options( WP_REST_Request $request ) : WP_REST_Response {
        $options = $request->get_params();
        $sanitized_options = $this->validate_and_sanitize_options( $options );
        if ( $sanitized_options instanceof WP_REST_Response ) {
            return $sanitized_options;
        }
        update_option( 'cc_client_options', $sanitized_options );
        return new WP_REST_Response( $sanitized_options, 200 );
    }

    protected function validate_and_sanitize_options( array $options ) : array | WP_REST_Response {
        $is_valid_data = rest_validate_value_from_schema( $options, $this->get_item_schema(), 'options' );
        if ( ! $is_valid_data ) {
            return new WP_REST_Response( 'Returned options failed to validate.', 400 );
        }

        $sanitized_options = rest_sanitize_value_from_schema( $options, $this->get_item_schema(), 'options' );
        if ( is_wp_error( $sanitized_options ) ) {
            return new WP_REST_Response( $sanitized_options->get_error_message(), 400 );
        }

        return $sanitized_options;
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
