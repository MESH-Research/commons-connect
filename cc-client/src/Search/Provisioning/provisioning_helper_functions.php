<?php
/**
 * Helper functions for search provisioning.
 * 
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Search\Provisioning;

function get_profile_url( \WP_User $user ): string {
	if ( 
		! function_exists( 'bp_core_enable_root_profiles' ) || 
		! function_exists( 'bp_get_members_root_slug' ) || 
		! function_exists( 'bp_core_get_username' )
	) {
		return get_author_posts_url( $user->ID );
	}
	
	//See: bp-members-functions.php::bp_core_get_user_domain()
	$username = bp_core_get_username( $user->ID );
	$base_url = get_home_url( get_current_blog_id() );
	$after_domain = bp_core_enable_root_profiles() ? $username : bp_get_members_root_slug() . '/' . $username;
	return trailingslashit( $base_url ) . $after_domain;
}

function get_current_network_node(): string {
	return get_network_option( null, 'society_id', '' );
}

function get_other_profile_urls( \WP_User $user ): array {
	$primary_url = get_profile_url( $user );
	$other_urls = [];
	$networks = get_networks();
	foreach ( $networks as $network ) {
		switch_to_blog( $network->blog_id );
		$profile_url = get_profile_url( $user );
		if ( $profile_url != $primary_url ) {
			$other_urls[] = $profile_url;
		}
		restore_current_blog();
	}
	return $other_urls;
}

function get_network_nodes(): array {
	$nodes = [];
	$networks = get_networks();
	foreach ( $networks as $network ) {
		$society_id = get_network_option( $network->id, 'society_id' );
		if ( $society_id ) {
			$nodes[] = $society_id;
		}
	}
	return $nodes;
}