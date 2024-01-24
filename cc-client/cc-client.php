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
 * @package           MESHResearch\CCClient
 */

namespace MESHResearch\CCClient;

define( 'CC_CLIENT_BASE_DIR', plugin_dir_path( __FILE__ ) );
define( 'CC_CLIENT_BASE_URL', plugin_dir_url( __FILE__ ) );

const CC_CLIENT_REST_NAMESPACE = 'cc-client/v1';

/**
 * Enqueue block editor frontend assets.
 */
function frontend_enqueue() {
	$asset_file = include plugin_dir_path( __FILE__ ) . 'build/profile/front.asset.php';
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\frontend_enqueue' );

require_once( plugin_dir_path( __FILE__ ) . 'src/admin/admin-settings.php' );
require_once( plugin_dir_path( __FILE__ ) . 'src/rest/rest.php' );
require_once( plugin_dir_path( __FILE__ ) . 'src/blocks/blocks.php' );