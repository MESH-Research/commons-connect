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
	$base_url = get_home_url( get_central_blog_id() );
	$after_domain = bp_core_enable_root_profiles() ? $username : bp_get_members_root_slug() . '/' . $username;
	return trailingslashit( $base_url ) . $after_domain;
}

function get_current_network_node(): string {
	$current_network_node = wp_cache_get( __NAMESPACE__ . '_current_network_node' );
	if ( $current_network_node ) {
		return $current_network_node;
	}
	$current_network_node = get_network_option( null, 'society_id', '' );
	wp_cache_set( __NAMESPACE__ . '_current_network_node', $current_network_node, '', 60 );
	return $current_network_node;
}

function get_other_profile_urls( \WP_User $user ): array {
	$primary_url = get_profile_url( $user );
	$site_url = get_site_url();
	$profile_url_path = str_replace($site_url, '', $primary_url);
	$networks = get_networks();
	$other_urls = [];
	foreach ( $networks as $network ) {
		$base_url = get_network_base_url( $network->id );
		$profile_url = $base_url . $profile_url_path;
		if ( $profile_url != $primary_url ) {
			$other_urls[] = $profile_url;
		}
	}
	return $other_urls;
}

function get_network_base_url( int $network_id ): string {
	$base_url = wp_cache_get( __NAMESPACE__ . '_network_base_url_' . $network_id );
	if ( $base_url ) {
		return $base_url;
	}
	$network = get_network( $network_id );
	$base_url = 'https://' . $network->domain;
	wp_cache_set( __NAMESPACE__ . '_network_base_url_' . $network_id, $base_url, '', 60 * 60 );
	return $base_url;
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

/**
 * Get the blog ID of the central blog.
 * 
 * The central blog is the root blog of the main network.
 * (On *.hcommons.org, this is hcommons.org itself.)
 */
function get_central_blog_id(): int {
	$networks = get_networks();
	
	if ( defined( 'CENTRAL_BLOG_ID' ) ) {
		return CENTRAL_BLOG_ID;
	}

	if ( defined( 'DOMAIN_NAME') ) {
		$central_blog_domain = DOMAIN_NAME;
	} elseif ( defined( 'WP_DOMAIN' ) ) {
		$central_blog_domain = WP_DOMAIN;
	} else {
		$current_blog = get_blog_details();
		$current_domain = $current_blog->domain;
		foreach ( $networks as $network ) {
			if ( 
					strlen( $current_domain ) > strlen( $network->domain) && 
					strpos( $current_domain, $network->domain ) !== false 
				) {
				$central_blog_domain = $network->domain;
			}
		}
		$central_blog_domain = $current_domain;
	}

	foreach ( $networks as $network ) {
		if ( $network->domain === $central_blog_domain ) {
			return $network->blog_id;
		}
	}

	return 1;
}