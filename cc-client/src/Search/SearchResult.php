<?php
/**
 * A search result from the search service.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Search;

class SearchResult {
	public function __construct(
		public int $total = 0,
		public int $page = 0,
		public int $per_page = 0,
		public array $hits = []
	) {}

	public function equals( SearchResult $result ): bool {
		return $this->toJSON() === $result->toJSON();
	}

	public function toJSON(): string {
		return json_encode( $this );
	}

	public static function fromJSON( string $json ): SearchResult {
		$data = json_decode( $json, true );
		$hits = [];
		if ( isset( $data['hits'] ) && is_array( $data['hits'] ) ) {
			foreach ( $data['hits'] as $hit ) {
				$hits[] = SearchDocument::fromJSON( json_encode( $hit ) );
			}
		}
		return new SearchResult(
			$data['total'] ?? 0,
			$data['page'] ?? 0,
			$data['per_page'] ?? 0,
			$hits
		);
	}
}