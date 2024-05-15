<?php
/**
 * Miscellaneous functios for the plugin.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient;

use MeshResearch\CCClient\Search\Provisioning\ProvisionableUser;
use MeshResearch\CCClient\Search\Provisioning\ProvisionableGroup;
use MeshResearch\CCClient\Search\Provisioning\ProvisionableSite;
use MeshResearch\CCClient\Search\Provisioning\ProvisionablePost;

const DEFAULT_OPTIONS = [
	'cc_search_key' => '',
	'cc_search_endpoint' => '',
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

function get_provisioner_by_type( string $type, string $wpid ): ProvisionableUser | ProvisionableGroup | ProvisionableSite | ProvisionablePost {
	switch ( $type ) {
		case 'user':
			$item = get_user_by( 'ID', $wpid );
			if ( ! $item ) {
				throw new \Exception( 'Invalid user ID' );
			}
			return new ProvisionableUser( $item );
		case 'group':
			$item = new \BP_Groups_Group( $wpid );
			if ( ! $item->id ) {
				throw new \Exception( 'Invalid group ID' );
			}
			return new ProvisionableGroup( $item );
		case 'site':
			$item = get_blog_details( $wpid );
			if ( ! $item ) {
				throw new \Exception( 'Invalid site ID' );
			}
			return new ProvisionableSite( $item );
		case 'post':
			$item = get_post( $wpid );
			if ( ! $item ) {
				throw new \Exception( 'Invalid post ID' );
			}
			return new ProvisionablePost( $item );
		default:
			throw new \Exception( 'Invalid provisionable type: ' . $type );
	}
}
