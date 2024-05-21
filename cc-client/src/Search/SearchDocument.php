<?php
/**
 * A document for provisioning to or receiving from the search service.
 * 
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Search;

class SearchDocument implements \JsonSerializable {
	public function __construct(
		public string              $_internal_id     = '',
		public string              $_id              = '',
		public string              $title            = '',
		public string              $description      = '',
		public SearchPerson | null $owner            = null,
		public array               $contributors     = [],
		public string              $primary_url      = '',
		public array               $other_urls       = [],
		public string              $thumbnail_url    = '',
		public string              $content          = '',
		public \DateTime | null    $publication_date = null,
		public \DateTime | null    $modified_date    = null,
		public string              $language         = '',
		public string              $content_type     = '',
		public string              $network_node     = ''
	) {
	}

	public function jsonSerialize(): mixed {
		$to_serialize = new \stdClass();
		foreach ( $this as $key => $value ) {
			if ( empty( $value ) ) {
				continue;
			}
			if ( $value instanceof \DateTime ) {
				$to_serialize->$key = $value->format( 'Y-m-d' );
				continue;
			}
			$to_serialize->$key = $value;
		}
		return $to_serialize;
	}

	public function equals( SearchDocument $contributor ): bool {
		return $this->toJSON() === $contributor->toJSON();
	}

	public function toJSON(): string {
		return json_encode( $this, JSON_PRETTY_PRINT );
	}

	protected static function singleFromJSON( mixed $data ) {
		if ( isset( $data['owner'] ) ) {
			$owner = SearchPerson::fromJSON( json_encode( $data['owner'] ) );
		} else {
			$owner = new SearchPerson();
		}

		$contributors = [];
		if ( isset( $data['contributors'] ) && is_array( $data['contributors'] ) ) {
			foreach ( $data['contributors'] as $contributor ) {
				$contributors[] = SearchPerson::fromJSON( json_encode( $contributor ) );
			}
		}

		$publication_date = ! empty( $data['publication_date'] ) ? new \DateTime( $data['publication_date'] ) : null;
		$modified_date = ! empty( $data['modified_date'] ) ? new \DateTime( $data['modified_date'] ) : null;
			
		return new SearchDocument(
			_internal_id:     $data['_internal_id'] ?? 0,
			_id:              $data['_id'] ?? '',
			title:            $data['title'] ?? '',
			description:      $data['description'] ?? '',
			owner:            $owner,
			contributors:     $contributors,
			primary_url:      $data['primary_url'] ?? '',
			other_urls:       $data['other_urls'] ?? [],
			thumbnail_url:    $data['thumbnail_url'] ?? '',
			content:          $data['content'] ?? '',
			publication_date: $publication_date,
			modified_date:    $modified_date,
			language:         $data['language'] ?? '',
			content_type:     $data['content_type'] ?? '',
			network_node:     $data['network_node'] ?? ''
		);
	}

	/**
	 * Create a SearchDocument or array of SearchDocuments from a JSON string
	 * 
	 * @param string $json The JSON string to parse
	 * @return SearchDocument|array The parsed SearchDocument or array of SearchDocuments
	 */
	public static function fromJSON( string $json ): SearchDocument|array {
		$data = json_decode( $json, true );

		if ( ! is_array( $data ) ) {
			throw new \Exception( 'Invalid JSON' );
		}

		if ( array_is_list( $data ) ) {
			$documents = [];
			foreach ( $data as $document ) {
				$documents[] = self::singleFromJSON( $document );
			}
			return $documents;
		}

		return self::singleFromJSON( $data );
	}
}