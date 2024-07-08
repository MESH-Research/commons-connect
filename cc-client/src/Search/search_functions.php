<?php
/**
 * Functions supporting CC Search.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Search;

use MeshResearch\CCClient\CCClientOptions;

/**
 * Filter the searchform query var.
 * 
 * The default WordPress searchform action is 's'. This function changes it to 'q'.
 */
function filter_searchform_query_var( string $query_var ): string {
	$options = new CCClientOptions();
	if ( ! $options->search_page_id ) {
		return $query_var;
	}
	return 'q';
}
add_filter( 'kc_searchform_query_var', __NAMESPACE__ . '\filter_searchform_query_var' );

/**
 * Filter the searchform action URL.
 */
function filter_searchform_action( string $action ): string {
	$options = new CCClientOptions();
	if ( ! $options->search_page_id ) {
		return $action;
	}
	return get_permalink( $options->search_page_id );
}
add_filter( 'kc_searchform_action', __NAMESPACE__ . '\filter_searchform_action' );
