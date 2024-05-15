<?php
/**
 * Plugin Name:       CC Client
 * Description:       Commons Connect Client Plugin.
 * Requires at least: 6.1
 * Requires PHP:      7.0
 * Version:           0.1.0
 * Author:            The WordPress Contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cc-client
 *
 * @package           MeshResearch\CCClient
 */

namespace MeshResearch\CCClient;

define( 'CC_CLIENT_BASE_DIR', plugin_dir_path( __FILE__ ) );
define( 'CC_CLIENT_BASE_URL', plugin_dir_url( __FILE__ ) );

const CC_CLIENT_REST_NAMESPACE = 'cc-client/v1';

/**
 * Feature Flags
 */

define( 'CCC_FEATURE_PROFILE_BLOCK', false );

/**
 * Enqueue block editor frontend assets.
 */
function enqueue_client_block_assets() {
	$asset_file = include CC_CLIENT_BASE_DIR . 'build/profile/front.asset.php';
	wp_enqueue_style(
		'cc-client-profile',
		CC_CLIENT_BASE_URL . 'build/profile/style-index.css',
		[],
		$asset_file['version']
	);

	$asset_file = include CC_CLIENT_BASE_DIR . 'build/search/view.asset.php';
	wp_enqueue_style(
		'cc-client-search',
		CC_CLIENT_BASE_URL . 'build/search/style-view.css',
		[],
		$asset_file['version']
	);

	if ( is_admin() ) {

		// Enqueue editor only assets here
	}
}
add_action( 'enqueue_block_assets', __NAMESPACE__ . '\enqueue_client_block_assets' );

/**
 * Composer autoload.
 */
require_once( CC_CLIENT_BASE_DIR . 'vendor/autoload.php' );

require_once( CC_CLIENT_BASE_DIR . 'src/admin/admin-settings.php' );
require_once( CC_CLIENT_BASE_DIR . 'src/rest/rest.php' );
require_once( CC_CLIENT_BASE_DIR . 'src/blocks/blocks.php' );
require_once( CC_CLIENT_BASE_DIR . 'src/functions.php' );
