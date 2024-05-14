<?php
/**
 * A WordPress post that can be provisioned to the search service.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Search\Provisioning;

use MeshResearch\CCClient\Search\SearchDocument;
use MeshResearch\CCClient\Search\SearchPerson;

require_once __DIR__ . '/functions.php';

class ProvisionablePost implements ProvisionableInterface {
	public function __construct(
		public \WP_Post $post
	) {}

	public function toDocument(): SearchDocument {
		$post_author = \get_user_by( 'ID', $this->post->post_author );
		if ( $post_author ) {
			$author = new SearchPerson(
				name: $post_author->display_name,
				username: $post_author->user_login,
				url: get_profile_url( $post_author ),
				role: 'author',
				network_node: get_current_network_node()
			);
		} else {
			$author = new SearchPerson(
				name: 'Unknown',
				username: 'unknown',
				url: '',
				role: 'author',
				network_node: get_current_network_node()
			);
		}

		$document = new SearchDocument(
			_internal_id: strval( $this->post->ID ),
			title: \get_the_title( $this->post ),
			description: \get_the_excerpt( $this->post ),
			owner: $author,
			contributors: [ $author ],
			primary_url: \get_permalink( $this->post ),
			thumbnail_url: \get_the_post_thumbnail_url( $this->post ),
			content: \wp_strip_all_tags( \get_the_content( '', false, $this->post ) ),
			publication_date: \get_the_date( 'Y-m-d', $this->post ),
			modified_date: \get_the_modified_date( 'Y-m-d', $this->post ),
			content_type: 'post',
			network_node: get_current_network_node()
		);
		
		return $document;
	}

	public function getSearchID(): string {
		$search_id = get_post_meta( $this->post->ID, 'cc_search_id', true );
		if ( $search_id === false ) {
			throw new \Exception( 'Invalid post ID' );
		}
		return $search_id;
	}

	public function setSearchID( string $search_id ): void {
		update_post_meta( $this->post->ID, 'cc_search_id', $search_id );
	}

	public static function getAll( $post_types = [ 'post', 'page' ] ): array {
		$posts = \get_posts( [
			'post_type' => $post_types,
			'numberposts' => -1,
			'post_status' => 'publish'
		] );

		$provisionable_posts = [];
		foreach ( $posts as $post ) {
			$provisionable_posts[] = new ProvisionablePost( $post );
		}

		return $provisionable_posts;
	}

	public static function getAllAsDocuments(): array {
		$provisionable_posts = self::getAll();
		$documents = [];
		foreach ( $provisionable_posts as $provisionable_post ) {
			$documents[] = $provisionable_post->toDocument();
		}
		return $documents;
	}
}