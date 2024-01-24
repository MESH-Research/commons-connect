<?php
/**
 * Settings pages for the Commons Connect Client plugin.
 * 
 * @package MESHResearch\CCClient
 */

namespace MESHResearch\CCClient;

/**
 * Base 64 encoded Commons Connect icon.
 */
function cc_icon() : string {
	$svg_src = file_get_contents( CC_CLIENT_BASE_DIR . 'assets/commons-connect.svg' );
	return 'data:image/svg+xml;base64,' . base64_encode( $svg_src );
}

/**
 * Register the settings pages.
 */
function register_settings_pages() {
	add_menu_page(
		page_title: 'Commons Connect',
		menu_title: 'Commons Connect',
		capability: 'manage_options',
		menu_slug : 'cc-client',
		callback  : __NAMESPACE__ . '\render_settings_page',
		icon_url  : cc_icon(),
		position  : null
	);
}
add_action( 'admin_menu', __NAMESPACE__ . '\register_settings_pages' );

/**
 * Render the settings page.
 */
function render_settings_page() {
	print( '<div id="cc-client-admin"></div>' );
}

/**
 * Enqueue admin settings page assets.
 */
function admin_assets_enqueue( string $hook_suffix ) {
	if ( 'toplevel_page_cc-client' !== $hook_suffix ) {
		return;
	}
	
	$asset_file = include CC_CLIENT_BASE_DIR . 'build/admin/admin.asset.php';
	wp_enqueue_script(
		'cc-client-admin',
		CC_CLIENT_BASE_URL . 'build/admin/admin.js',
		$asset_file['dependencies'],
		$asset_file['version']
	);
/*
	wp_enqueue_style(
		'wordpress-components-styles',
		includes_url( '/css/dist/components/style.min.css' )
	);
*/
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\admin_assets_enqueue', 10, 1 );