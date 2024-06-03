<?php
/**
 * Provisioning tests
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Tests;

use MeshResearch\CCClient\Search;

use function MeshResearch\CCClient\Search\Provisioning\bulk_provision;
use MeshResearch\CCClient\Tests\CCCTestCase;

class ProvisioningTest extends CCCTestCase {
	public function testBulkPostProvisioning(): void {
		$author = $this->factory->user->create_and_get();
		$post_ids = $this->factory->post->create_many( 10, [ 'post_author' => $author->ID ] );
		bulk_provision( ['post'], $this->search_api );
		foreach ( $post_ids as $post_id ) {
			$cc_search_id = get_post_meta( $post_id, 'cc_search_id', true );
			$this->assertNotEmpty( $cc_search_id );
		}
	}
}
 
 