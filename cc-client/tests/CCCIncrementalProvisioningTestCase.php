<?php
/**
 * Tests for the incremental provisioning system.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Tests;

use MeshResearch\CCClient\Search\SearchAPI;

class CCCIncrementalProvisioningTestCase extends CCCTestCase {
	public function setUp() : void {
		parent::setUp();
		$this->options->incremental_provisioning_enabled = true;
		$this->search_api = new SearchAPI($this->options);
	}
}