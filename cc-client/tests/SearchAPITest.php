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

use MeshResearch\CCClient\Tests\CCCTestCase;

class SearchAPITest extends CCCTestCase
{
	/**
	 * Test the ping method
	 */
	public function test_ping(): void {
		$this->assertTrue($this->search_api->ping());
	}

	public function test_index_document(): void {
		$document_json = file_get_contents(__DIR__ . '/test-data/single_test_doc.json');
		$document = SearchDocument::fromJSON( $document_json );
		$indexed_document = $this->search_api->index($document);
		$this->assertInstanceOf(SearchDocument::class, $indexed_document);
		$this->assertNotEmpty($indexed_document->_id);
	}

	public function test_bulk_index_documents(): void {
		$documents_json = file_get_contents(__DIR__ . '/test-data/small_test_doc_collection.json');
		$documents = SearchDocument::fromJSON($documents_json);
		$indexed_documents = $this->search_api->bulk_index($documents);
		$this->assertIsArray($indexed_documents);
		$this->assertCount(count($documents), $indexed_documents);
		foreach ($indexed_documents as $indexed_document) {
			$this->assertNotEmpty($indexed_document->_id);
		}
	}

	public function test_update_document(): void {
		$document_json = file_get_contents(__DIR__ . '/test-data/single_test_doc.json');
		$document = SearchDocument::fromJSON( $document_json );
		$indexed_document = $this->search_api->index($document);
		$indexed_document->title = 'On Open Scholarship, Revised';
		$this->assertTrue($this->search_api->update($indexed_document));
		$revised_document = $this->search_api->get_document($indexed_document->_id);
		$this->assertEquals('On Open Scholarship, Revised', $revised_document->title);
	}

	public function test_delete_document(): void {
		$document_json = file_get_contents(__DIR__ . '/test-data/single_test_doc.json');
		$document = SearchDocument::fromJSON( $document_json );
		$indexed_document = $this->search_api->index($document);
		$id = $indexed_document->_id;
		$this->assertTrue($this->search_api->delete($indexed_document->_id));
		$deleted_document = $this->search_api->get_document($id);
		$this->assertFalse($deleted_document);
	}

	public function test_get_document(): void {
		$document_json = file_get_contents(__DIR__ . '/test-data/single_test_doc.json');
		$document = SearchDocument::fromJSON( $document_json );
		$indexed_document = $this->search_api->index($document);
		$document->_id = $indexed_document->_id;
		$retrieved_document = $this->search_api->get_document($indexed_document->_id);
		$this->assertObjectEquals($document, $retrieved_document);
	}

	public function test_search_documents(): void {
		$documents_json = file_get_contents(__DIR__ . '/test-data/small_test_doc_collection.json');
		$documents = SearchDocument::fromJSON($documents_json);
		$this->search_api->bulk_index($documents);
		sleep(1);
		$search_params = new SearchParams(query:'open scholarship');
		$search_results = $this->search_api->search($search_params);
		$this->assertIsArray($search_results->hits);
		$this->assertGreaterThan(0, $search_results->total);
	}

	public function test_search_documents_time_range(): void {
		$documents_json = file_get_contents(__DIR__ . '/test-data/small_test_doc_collection.json');
		$documents = SearchDocument::fromJSON($documents_json);
		$this->search_api->bulk_index($documents);
		sleep(1);
		$search_params = new SearchParams(query:'art', start_date:'2022-01-01', end_date:'2022-12-31');
		$search_results = $this->search_api->search($search_params);
		$this->assertIsArray($search_results->hits);
		foreach ($search_results->hits as $hit) {
			$this->assertLessThanOrEqual(new \DateTime('2022-12-31'), $hit->publication_date);
			$this->assertLessThanOrEqual(new \DateTime('2022-12-31'), $hit->publication_date);
		}
	}
}
