<?php
/**
 * Interface for classes that can be provisioned to the search service.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Search\Provisioning;

use MeshResearch\CCClient\Search\SearchDocument;

interface ProvisionableInterface {
	public function toDocument(): SearchDocument;
	public function getSearchID(): string;
	public function setSearchID( string $search_id ): void;
	public static function getAll(): array;
	public static function getAllAsDocuments(): array;
}