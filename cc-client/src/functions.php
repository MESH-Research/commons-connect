<?php
/**
 * Miscellaneous functios for the plugin.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient;

const DEFAULT_OPTIONS = [
	'cc_search_key' => '',
	'cc_search_endpoint' => '',
	'cc_search_admin_key' => '',
];

/**
 * Get plugin options.
 */
function get_ccc_options(): array {
	$default_options = DEFAULT_OPTIONS;
	$options = get_option( 'cc_client_options' );
	if ( ! is_array( $options ) ) {
		$options = [];
	}
	$options = array_merge( $default_options, $options );

	foreach ( $options as $key => &$value ) {
		$option_const_name = strtoupper( $key );
		$value = getenv( $option_const_name ) ?: $value;
	}
	return $options;
}

/**
 * Register WP CLI commands.
 */
if ( class_exists( 'WP_CLI' ) ) {
	\WP_CLI::add_command( 'cc search', 'MeshResearch\CCClient\Search\SearchCommand' );
}