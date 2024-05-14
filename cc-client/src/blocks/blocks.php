<?php
/**
 * Main blocks file.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient;

if ( CCC_FEATURE_PROFILE_BLOCK ) {
	require_once( plugin_dir_path( __FILE__ ) . 'profile/server.php' );
}

