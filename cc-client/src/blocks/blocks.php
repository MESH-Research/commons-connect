<?php
/**
 * Main blocks file.
 *
 * @package MESHResearch\CCClient
 */

namespace MESHResearch\CCClient;

if ( CCC_FEATURE_PROFILE_BLOCK ) {
	require_once( plugin_dir_path( __FILE__ ) . 'profile/server.php' );
}

function register_search_page_block() {
	register_block_type(
		CC_CLIENT_BASE_DIR . '/build/search-page',
		[]
	);
}
add_action( 'init', __NAMESPACE__ . '\register_search_page_block' );
