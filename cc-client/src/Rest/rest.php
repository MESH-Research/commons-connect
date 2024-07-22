<?php
/**
 * Register REST routes.
 *
 * @package MeshResearch\CCClient
 */

 namespace MeshResearch\CCClient\Rest;

require_once( plugin_dir_path( __FILE__ ) . 'OptionsController.php' );
require_once( plugin_dir_path( __FILE__ ) . 'SearchController.php' );

function register_rest_routes() {
    $controllers = [
        new OptionsController(),
        new SearchController(),
    ];

    foreach ( $controllers as $controller ) {
        $controller->register_routes();
    }
}
add_action( 'rest_api_init', __NAMESPACE__ . '\register_rest_routes' );
