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

if (!defined("CC_CLIENT_DOING_TESTING")) {
    define("CC_CLIENT_DOING_TESTING", false);
}

define("CC_CLIENT_BASE_DIR", plugin_dir_path(__FILE__));
define("CC_CLIENT_BASE_URL", plugin_dir_url(__FILE__));

define("CC_CLIENT_REST_NAMESPACE", "cc-client/v1");

/**
 * Enqueue block editor frontend assets.
 */
function enqueue_client_block_assets()
{
    $asset_file = include CC_CLIENT_BASE_DIR . "build/search/view.asset.php";
    wp_enqueue_style(
        "cc-client-search",
        CC_CLIENT_BASE_URL . "build/search/style-view.css",
        [],
        $asset_file["version"]
    );

    if (is_admin()) {
        // Enqueue editor only assets here
    }
}
add_action(
    "enqueue_block_assets",
    __NAMESPACE__ . '\enqueue_client_block_assets'
);

/**
 * Composer autoload.
 *
 * When cc-client is installed as a conmposer dependency, the vendor directory
 * will be in the root of the project and the autoload.php will already be
 * included at the project level, so the vendor directory will not exist.
 */
if (file_exists(CC_CLIENT_BASE_DIR . "vendor/autoload.php")) {
    require_once CC_CLIENT_BASE_DIR . "vendor/autoload.php";
}

require_once CC_CLIENT_BASE_DIR . "src/admin/admin-settings.php";
require_once CC_CLIENT_BASE_DIR . "src/Rest/rest.php";
require_once CC_CLIENT_BASE_DIR . "src/blocks/blocks.php";
require_once CC_CLIENT_BASE_DIR . "src/functions.php";

require_once CC_CLIENT_BASE_DIR .
    "src/Search/Provisioning/bulk_provisioning.php";
require_once CC_CLIENT_BASE_DIR .
    "src/Search/Provisioning/incremental_provisioner_functions.php";
require_once CC_CLIENT_BASE_DIR .
    "src/Search/Provisioning/provisionable_functions.php";
require_once CC_CLIENT_BASE_DIR .
    "src/Search/Provisioning/provisioning_helper_functions.php";
require_once CC_CLIENT_BASE_DIR . "src/Search/search_functions.php";
