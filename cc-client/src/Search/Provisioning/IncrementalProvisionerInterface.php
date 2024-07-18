<?php
/**
 * Interface for incremental provisioners.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Search\Provisioning;

interface IncrementalProvisionerInterface {
	public function registerHooks(): void;
	public function isEnabled(): bool;
	public function enable(): void;
	public function disable(): void;
}