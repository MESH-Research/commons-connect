<?php
/**
 * Base class for CC Client tests.
 * 
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Tests;

use MeshResearch\CCClient\Search\SearchAPI;
use MeshResearch\CCClient\CCClientOptions;

class CCCTestCase extends \WP_UnitTestCase
{
	public CCClientOptions $options;
	public SearchAPI $search_api;
	
	/**
	 * Set up the test case.
	 */
	protected function setUp(): void {
		$this->options = new CCClientOptions(
			cc_search_key: '12345',
			cc_search_endpoint: 'http://commonsconnect-search.lndo.site/v1',
			cc_search_admin_key: '12345',
			incremental_provisioning_enabled: false
		);
		$this->search_api = new SearchAPI($this->options);
		self::_delete_all_data();
		parent::setUp();
	}
		
	/**
	 * Tear down the test case.
	 */
	protected function tearDown(): void {
		parent::tearDown();
	}
 
	/**
	 * @see https://github.com/WordPress/wordpress-develop/blob/9e31509293e93383a97ed170039dafa5042ea286/tests/phpunit/includes/functions.php#L55
	 */
	public static function _delete_all_data() {
		global $wpdb;
	
		foreach ( array(
			$wpdb->posts,
			$wpdb->postmeta,
			$wpdb->comments,
			$wpdb->commentmeta,
			$wpdb->term_relationships,
			$wpdb->termmeta,
		) as $table ) {
			$wpdb->query( "DELETE FROM {$table}" );
		}
	
		foreach ( array(
			$wpdb->terms,
			$wpdb->term_taxonomy,
		) as $table ) {
			$wpdb->query( "DELETE FROM {$table} WHERE term_id != 1" );
		}
	
		$wpdb->query( "UPDATE {$wpdb->term_taxonomy} SET count = 0" );
	
		$wpdb->query( "DELETE FROM {$wpdb->users} WHERE ID != 1" );
		$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE user_id != 1" );
	}
}