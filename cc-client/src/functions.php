<?php
/**
 * Miscellaneous functios for the plugin.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient;

/**
 * Register WP CLI commands.
 */
if ( class_exists( 'WP_CLI' ) ) {
	\WP_CLI::add_command( 'cc search', 'MeshResearch\CCClient\Search\SearchCommand' );
}