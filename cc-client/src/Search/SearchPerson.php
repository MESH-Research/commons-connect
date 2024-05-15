<?php
/**
 * A user in a search document.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Search;

class SearchPerson implements \JsonSerializable {
	public function __construct(
		public string $name = '',
		public string $username = '',
		public string $url = '',
		public string $role = '',
		public string $network_node = ''
	) {}

	public function jsonSerialize(): mixed {
		$to_serialize = new \stdClass();
		foreach ( $this as $key => $value ) {
			if ( ! empty( $value ) ) {
				$to_serialize->$key = $value;
			}
		}
		return $to_serialize;
	}

	public function equals( SearchPerson $contributor ): bool {
		return $this->toJSON() === $contributor->toJSON();
	}

	public function toJSON(): string {
		return json_encode( $this );
	}

	public static function fromJSON( string $json ): SearchPerson {
		$data = json_decode( $json, true );
		return new SearchPerson(
			$data['name'] ?? '',
			$data['username'] ?? '',
			$data['url'] ?? '',
			$data['role'] ?? '',
			$data['network_node'] ?? ''
		);
	}
}
