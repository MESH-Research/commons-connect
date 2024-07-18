<?php
/**
 * Server-side control and rendering of profile block.
 * 
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient;

function register_profile_block() {
	register_block_type(
		CC_CLIENT_BASE_DIR . '/build/profile',
		[
			'api_version'      => 3,
			'render_callback' => __NAMESPACE__ . '\render_profile_block',
		]
	);
}
add_action( 'init', __NAMESPACE__ . '\register_profile_block' );

function render_profile_block( $block_attributes, $content ) {
	$options = get_option( 'cc_client_options' );
		
	$username = isset($_GET['username']) ? sanitize_text_field($_GET['username']) : '';

	$attributes = array_merge ( 
		$block_attributes,
		[
			'username'       => $username,
			'cc_server_url'  => $options['cc_server_url'],
		]
	);

	$encoded_attributes = json_encode( $attributes );

	return ( 
		"<div 
				class='wp-block-cc-client-profile'
				data-attributes='$encoded_attributes'
			>
				
		</div>"
	);
}

function update_rewrites_on_save( int $post_id, \WP_Post $post, bool $update ) {
	$options = get_option( 'cc_client_options' );
	$page_url = get_permalink( $post_id );
	$has_profile_block = has_block( 'cc-client/profile', $post );
}
add_action( 'save_post', __NAMESPACE__ . '\update_rewrites_on_save', 10, 3 );