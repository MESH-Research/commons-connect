<?php
/**
 * Parameters for a search query.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Search;

class SearchParams {
	public function __construct(
		public string $query          = '',
		public array  $exact_match    = [],
		public array  $return_fields  = [],
		public string $start_date     = '',
		public string $end_date       = '',
		public string $sort_direction = '',
		public string $sort_field     = '',
		public int    $page           = -1,
		public int    $per_page       = -1,
	) {}

	public function toJSON(): string {
		return json_encode( $this );
	}

	public static function fromJSON( string $json ): SearchParams {
		$data = json_decode( $json, true );
		return new SearchParams(
			$data['query'] ?? '',
			$data['exact_match'] ?? [],
			$data['return_fields'] ?? [],
			$data['start_date'] ?? '',
			$data['end_date'] ?? '',
			$data['sort_direction'] ?? '',
			$data['sort_field'] ?? '',
			$data['page'] ?? -1,
			$data['per_page'] ?? -1,
		);
	}

	public static function fromQueryParams( array $query_params ): SearchParams {
		$params = new SearchParams(
			query: $query_params['q'] ?? '',
			return_fields: $query_params['fields'] ?? [],
			start_date: $query_params['start_date'] ?? '',
			end_date: $query_params['end_date'] ?? '',
			sort_direction: $query_params['sort_dir'] ?? '',
			sort_field: $query_params['sort_by'] ?? '',
			page: $query_params['page'] ?? -1,
			per_page: $query_params['per_page'] ?? -1
		);
		foreach ( get_class_vars( SearchDocument::class ) as $field => $default_value ) {
			if ( isset( $query_params[ $field ] ) ) {
				$params->exact_match[ $field ] = $query_params[ $field ];
			}
		}
		return $params;
	}

	public function toQueryString(): string {
		$query_terms = [];
		if ( ! empty( $this->query ) ) {
			$query_terms[] = 'q=' . urlencode( $this->query );
		}
		foreach ( $this->exact_match as $field => $value ) {
			$query_terms[] = urlencode( $field ) . '=' . urlencode( $value );
		}
		if ( ! empty( $this->return_fields ) ) {
			$query_terms[] = 'fields=' . implode( ',', $this->return_fields );
		}
		if ( ! empty( $this->start_date ) ) {
			$query_terms[] = 'start_date=' . urlencode( $this->start_date );
		}
		if ( ! empty( $this->end_date ) ) {
			$query_terms[] = 'end_date=' . urlencode( $this->end_date );
		}
		if ( ! empty( $this->sort_direction ) ) {
			$query_terms[] = 'sort_dir=' . urlencode( $this->sort_direction );
		}
		if ( ! empty( $this->sort_field ) ) {
			$query_terms[] = 'sort_by=' . urlencode( $this->sort_field );
		}
		if ( $this->page >= 0 ) {
			$query_terms[] = 'page=' . $this->page;
		}
		if ( $this->per_page >= 0 ) {
			$query_terms[] = 'per_page=' . $this->per_page;
		}
		return implode( '&', $query_terms );
	}
}
