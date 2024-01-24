<?php
/**
 * Register REST routes.
 * 
 * @package MESHResearch\CCClient
 */

namespace MESHResearch\CCClient;

require_once( plugin_dir_path( __FILE__ ) . 'OptionsController.php' );

function register_rest_routes() {
	$controllers = [
		new OptionsController(),
	];

	foreach ( $controllers as $controller ) {
		$controller->register_routes();
	}
}
add_action( 'rest_api_init', __NAMESPACE__ . '\register_rest_routes' );