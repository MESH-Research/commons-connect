<?php
/**
 * Tests for the SearchDocument class.
 * 
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Tests;

use MeshResearch\CCClient\Search\SearchDocument;
use MeshResearch\CCClient\Search\SearchPerson;

class SearchDocumentTest extends \WP_UnitTestCase
{
	/**
	 * Test the toJSON method
	 */
	public function test_fromJSON(): void {
		$document_json = file_get_contents(__DIR__ . '/test-data/single_test_doc.json');

		$document = SearchDocument::fromJSON( $document_json );
		$json_object = json_decode( $document_json );
		foreach( get_object_vars($json_object) as $key => $value ) {
			if ( $key === 'owner' ) {
				$this->assertInstanceOf( SearchPerson::class, $document->owner );
				$this->assertEquals( $value->name, $document->owner->name );
				$this->assertEquals( $value->username, $document->owner->username );
			} elseif ( $key === 'contributors' ) {
				$this->assertIsArray( $document->contributors );
				$this->assertCount( count($value), $document->contributors );
				foreach( $value as $index => $other ) {
					$this->assertInstanceOf( SearchPerson::class, $document->contributors[$index] );
					$this->assertEquals( $other->name, $document->contributors[$index]->name );
				}
			} else {
				$this->assertEquals( $value, $document->$key );
			}
		}
	}
}