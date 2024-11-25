<?php
/**
 * A WordPress post that can be provisioned to the search service.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Search\Provisioning;

use MeshResearch\CCClient\Search\SearchDocument;
use MeshResearch\CCClient\Search\SearchPerson;

class ProvisionablePost implements ProvisionableInterface {
	public function __construct(
		public \WP_Post $post,
		public string $search_id = ''
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

		// Try to get the excerpt, but don't fail if something goes wrong.
		// There is currently a bug in wp-inclides/formatting.php::convert_smilies that
		// can cause a TypeError to be thrown when calling get_the_excerpt.
		// @see: https://core.trac.wordpress.org/ticket/59927
		try {
			$excerpt = \get_the_excerpt( $this->post );
		} catch ( \Throwable $e ) {
			$excerpt = '';
		}

		$document = new SearchDocument(
			_internal_id: strval( $this->post->ID ),
			title: \get_the_title( $this->post ),
			description: $excerpt,
			owner: $author,
			contributors: [ $author ],
			primary_url: \get_permalink( $this->post ),
			thumbnail_url: \get_the_post_thumbnail_url( $this->post ),
			content: \wp_strip_all_tags( \get_the_content( '', false, $this->post ) ),
			publication_date: new \DateTime( get_the_date( 'Y-m-d', $this->post ) ),
			modified_date: new \DateTime( get_the_modified_date( 'Y-m-d', $this->post ) ),
			content_type: 'post',
			network_node: get_current_network_node()
		);

		if ( $this->search_id ) {
			$document->_id = $this->search_id;
		}
		
		return $document;
	}

	public function getSearchID(): string {
		$search_id = get_post_meta( $this->post->ID, 'cc_search_id', true );
		if ( ! $search_id ) {
			$search_id = '';
		}
		$this->search_id = $search_id;
		return $search_id;
	}

	public function setSearchID( ? string $search_id ): void {
		if ( ! $search_id ) {
			delete_post_meta( $this->post->ID, 'cc_search_id' );
		} else {
			update_post_meta( $this->post->ID, 'cc_search_id', $search_id );
		}
		$this->search_id = $search_id ?? '';
	}

	public static function getAll( bool $reset = false, bool $show_progress = false, array $post_types = [ 'post', 'page' ] ): array {
		$posts = \get_posts( [
			'post_type' => $post_types,
			'numberposts' => -1,
			'post_status' => 'publish'
		] );
		if ( $show_progress && class_exists( 'WP_CLI' ) ) {
			\WP_CLI::line( 'Provisioning ' . count( $posts ) . ' posts...' );
		}
		$provisionable_posts = [];
		foreach ( $posts as $post ) {
			$provisionable_post = new ProvisionablePost( $post );
			if ( $reset ) {
				$provisionable_post->setSearchID( '' );
			}
			$provisionable_posts[] = $provisionable_post;
		}

		return $provisionable_posts;
	}

	public static function getAllAsDocuments( bool $reset = false, bool $show_progress = false, array $post_types = [ 'post', 'page' ] ): array {
		$provisionable_posts = self::getAll( $reset, $show_progress, $post_types );
		if ( $show_progress && class_exists( 'WP_CLI' ) ) {
			\WP_CLI::line( 'Converting ' . count( $provisionable_posts ) . ' posts to documents...' );
		}
		$documents = [];
		foreach ( $provisionable_posts as $provisionable_post ) {
			$documents[] = $provisionable_post->toDocument();
		}
		return $documents;
	}

	public static function isAvailable(): bool {
		return true;
	}
}