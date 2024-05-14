<?php
/**
 * Tests for the SearchPerson class.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Tests;

use MeshResearch\CCClient\Search\SearchPerson;

class SearchPersonTest extends \WP_UnitTestCase
{
	/**
	 * Test the toJSON method
	 */
	public function test_toJSON(): void {
		$person = new SearchPerson('John Doe', 'johndoe', 'http://example.com');
		$this->assertEquals(
			'{"name":"John Doe","username":"johndoe","url":"http:\/\/example.com"}',
			$person->toJSON()
		);
	}

	/**
	 * Test the fromJSON method
	 */
	public function test_fromJSON(): void {
		$person = SearchPerson::fromJSON('{"name":"John Doe","username":"johndoe","url":"http://example.com"}');
		$this->assertEquals(new SearchPerson('John Doe', 'johndoe', 'http://example.com'), $person);
	}
}