<?php
/**
 * Class SearchAPITest
 * 
 * Tests of the SearchAPI class.
 * 
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Tests;

use MeshResearch\CCClient\Search\SearchAPI;
use MeshResearch\CCClient\Search\SearchDocument;
use MeshResearch\CCClient\Search\SearchParams;

class SearchAPITest extends \WP_UnitTestCase
{
	private string $api_key;
	private string $api_url;

	/**
	 * Set up the test case
	 */
	protected function setUp(): void {
		$this->api_key = '12345';
		$this->api_url = 'http://commonsconnect-search.lndo.site/v1';
	}
		
	/**
	 * Test the ping method
	 */
	public function test_ping(): void {
		$api = new SearchAPI( $this->api_key, $this->api_url );
		$this->assertTrue($api->ping());
	}

	public function test_index_document(): void {
		$document_json = file_get_contents(__DIR__ . '/test-data/single_test_doc.json');
		$document = SearchDocument::fromJSON( $document_json );
		$api = new SearchAPI( $this->api_key, $this->api_url );
		$indexed_document = $api->index($document);
		$this->assertInstanceOf(SearchDocument::class, $indexed_document);
		$this->assertNotEmpty($indexed_document->_id);
	}

	public function test_bulk_index_documents(): void {
		$documents_json = file_get_contents(__DIR__ . '/test-data/small_test_doc_collection.json');
		$documents = SearchDocument::fromJSON($documents_json);
		$api = new SearchAPI( $this->api_key, $this->api_url );
		$indexed_documents = $api->bulk_index($documents);
		$this->assertIsArray($indexed_documents);
		$this->assertCount(count($documents), $indexed_documents);
		foreach ($indexed_documents as $indexed_document) {
			$this->assertNotEmpty($indexed_document->_id);
		}
	}

	public function test_update_document(): void {
		$document_json = file_get_contents(__DIR__ . '/test-data/single_test_doc.json');
		$document = SearchDocument::fromJSON( $document_json );
		$api = new SearchAPI( $this->api_key, $this->api_url );
		$indexed_document = $api->index($document);
		$indexed_document->title = 'On Open Scholarship, Revised';
		$this->assertTrue($api->update($indexed_document));
		$revised_document = $api->get_document($indexed_document->_id);
		$this->assertEquals('On Open Scholarship, Revised', $revised_document->title);
	}

	public function test_delete_document(): void {
		$document_json = file_get_contents(__DIR__ . '/test-data/single_test_doc.json');
		$document = SearchDocument::fromJSON( $document_json );
		$api = new SearchAPI( $this->api_key, $this->api_url );
		$indexed_document = $api->index($document);
		$this->assertTrue($api->delete($indexed_document->_id));
	}

	public function test_get_document(): void {
		$document_json = file_get_contents(__DIR__ . '/test-data/single_test_doc.json');
		$document = SearchDocument::fromJSON( $document_json );
		$api = new SearchAPI( $this->api_key, $this->api_url );
		$indexed_document = $api->index($document);
		$document->_id = $indexed_document->_id;
		$retrieved_document = $api->get_document($indexed_document->_id);
		$this->assertObjectEquals($document, $retrieved_document);
	}

	public function test_search_documents(): void {
		$documents_json = file_get_contents(__DIR__ . '/test-data/small_test_doc_collection.json');
		$documents = SearchDocument::fromJSON($documents_json);
		$api = new SearchAPI( $this->api_key, $this->api_url );
		$api->bulk_index($documents);
		$search_params = new SearchParams(query:'open scholarship');
		$search_results = $api->search($search_params);
		$this->assertIsArray($search_results->hits);
		$this->assertGreaterThan(0, $search_results->total);
	}
}