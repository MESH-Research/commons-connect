<?php
/**
 * Provisioning tests
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Tests;

use MeshResearch\CCClient\Search;

use function MeshResearch\CCClient\Search\Provisioning\bulk_provision;

class ProvisioningTest extends \WP_UnitTestCase {
	private string $api_key;
	private string $api_url;

	/**
	 * Set up the test case
	 */
	protected function setUp(): void {
		$this->api_key = '12345';
		$this->api_url = 'http://commonsconnect-search.lndo.site/v1';
		parent::setUp();
	}
	
	public function testBulkPostProvisioning(): void {
		$author = $this->factory->user->create_and_get();
		$post_ids = $this->factory->post->create_many( 10, [ 'post_author' => $author->ID ] );
		$search_api = new Search\SearchAPI( $this->api_key, $this->api_url );
		bulk_provision( ['post'], $search_api );
		foreach ( $post_ids as $post_id ) {
			$cc_search_id = get_post_meta( $post_id, 'cc_search_id', true );
			$this->assertNotEmpty( $cc_search_id );
		}
	}
}
 
 