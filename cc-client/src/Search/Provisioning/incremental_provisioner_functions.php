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
	
	$provisioners = [
		'CCClient\Search\Provisioning\IncrementalDiscussionsProvisioner',
		'CCClient\Search\Provisioning\IncrementalPostsProvisioner',
		'CCClient\Search\Provisioning\IncrementalUsersProvisioner',
		'CCClient\Search\Provisioning\IncrementalGroupsProvisioner',
		'CCClient\Search\Provisioning\IncrementalSitesProvisioner',
	];

	foreach ( $provisioners as $provisioner ) {
		$provisioner = new $provisioner();
	}
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\register_incremental_provisioners' );