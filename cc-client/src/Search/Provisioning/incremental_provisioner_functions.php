<?php
/**
 * Functions for the incremental provisioning system.
 */

namespace MeshResearch\CCClient\Search\Provisioning;

function register_incremental_provisioners(): void {
	// When testing, load incremental provisioners manually as needed to
	// avoid test pollution.
	if ( CC_CLIENT_DOING_TESTING ) {
		return;
	}

	$config = new \MeshResearch\CCClient\CCClientOptions();

	if ( ! $config->incremental_provisioning_enabled ) {
		return;
	}
	
	try {
		$search_api = new \MeshResearch\CCClient\Search\SearchAPI( $config );
	} catch ( \Exception $e ) {
		trigger_error( 'Failed to initialize search API: ' . $e->getMessage(), E_USER_WARNING );
		return;
	}

	$provisioners = [
		'\MeshResearch\CCClient\Search\Provisioning\IncrementalDiscussionsProvisioner',
		'\MeshResearch\CCClient\Search\Provisioning\IncrementalPostsProvisioner',
		'\MeshResearch\CCClient\Search\Provisioning\IncrementalUsersProvisioner',
		'\MeshResearch\CCClient\Search\Provisioning\IncrementalGroupsProvisioner',
		'\MeshResearch\CCClient\Search\Provisioning\IncrementalSitesProvisioner',
	];

	foreach ( $provisioners as $provisioner ) {
		try {
			$provisioner = new $provisioner( $search_api );
		} catch ( \Exception $e ) {
			trigger_error( 'Failed to initialize incremental provisioner: ' . $e->getMessage(), E_USER_WARNING );
		}
	}
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\register_incremental_provisioners' );