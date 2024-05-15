<?php
/**
 * Wrapper for the CC-Search API
 * 
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Search;

use GuzzleHttp\Client;
use function MeshResearch\CCClient\get_ccc_options;

class SearchAPI {
	const MAX_DOCUMENTS_PER_BULK_INDEX_REQUEST = 100;
	
	private $client;

	public function __construct(
		private string $api_key = '',
		private string $api_url = ''
	) {
		$options = get_ccc_options();
		if ( empty($this->api_key ) ) {
			if ( empty($options['cc_search_key'] ) ) {
				throw new \Exception('API key is required');
			}
			$this->api_key = $options['cc_search_key'];
		}
		if ( empty($this->api_url ) ) {
			if ( empty($options['cc_search_endpoint'] ) ) {
				throw new \Exception('API URL is required');
			}
			$this->api_url = $options['cc_search_endpoint'];
		}
		$this->client = new Client();
	}

	/**
	 * Ping the API to check if it is up
	 */
	public function ping(): bool {
		$response = $this->client->request('GET', $this->api_url . '/ping');
		return $response->getStatusCode() == 200;
	}

	/**
	 * Index a document
	 */
	public function index(SearchDocument $document): SearchDocument {
		$response = $this->client->request('POST', $this->api_url . '/documents', [
			'headers' => [
				'Authorization' => 'Bearer ' . $this->api_key,
				'Content-Type' => 'application/json'
			],
			'body' => $document->toJSON()
		]);
		if ( $response->getStatusCode() != 200 ) {
			throw new \Exception('Failed to index document. Status code: ' . $response->getStatusCode());
		}
		$returned_document = SearchDocument::fromJSON($response->getBody());
		return $returned_document;
	}

	/**
	 * Bulk index documents
	 *
	 * @param array $documents An array of SearchDocument objects
	 * @return array An array of SearchDocument objects
	 */
	public function bulk_index(array $documents): array {
		$document_chunks = array_chunk($documents, self::MAX_DOCUMENTS_PER_BULK_INDEX_REQUEST);
		$returned_documents = [];
		foreach ( $document_chunks as $chunk ) {
			$documents_json = json_encode($chunk);
			$response = $this->client->request('POST', $this->api_url . '/documents/bulk', [
				'headers' => [
					'Authorization' => 'Bearer ' . $this->api_key,
					'Content-Type' => 'application/json'
				],
				'body' => $documents_json
			]);
			if ( $response->getStatusCode() != 200 ) {
				throw new \Exception('Failed to bulk index documents. Status code: ' . $response->getStatusCode());
			}
			$returned_documents = array_merge($returned_documents, SearchDocument::fromJSON($response->getBody()));
		}
		return $returned_documents;
	}

	/**
	 * Update a document
	 */
	public function update(SearchDocument $document): bool {
		if ( empty($document->_id) ) {
			throw new \Exception('Document must have an _id to update');
		}
		$response = $this->client->request('PUT', $this->api_url . '/documents/' . $document->_id, [
			'headers' => [
				'Authorization' => 'Bearer ' . $this->api_key,
				'Content-Type' => 'application/json'
			],
			'body' => $document->toJSON()
		]);
		if ( $response->getStatusCode() != 200 ) {
			return false;
		}
		return true;
	}

	/**
	 * Index a document if it doesn't have an _id, otherwise update it
	 */
	public function index_or_update(SearchDocument $document): SearchDocument {
		if ( empty($document->_id) ) {
			return $this->index($document);
		}
		$this->update($document);
		return $document;
	}

	/**
	 * Delete a document
	 */
	public function delete(string $id): bool {
		$response = $this->client->request('DELETE', $this->api_url . '/documents/' . $id, [
			'headers' => [
				'Authorization' => 'Bearer ' . $this->api_key
			]
		]);
		return $response->getStatusCode() == 200;
	}

	/**
	 * Get a document
	 */
	public function get_document(string $id, $fields = []): SearchDocument {
		$field_query = '';
		if ( ! empty($fields) ) {
			$field_query = '?fields=' . implode(',', $fields);
		}
		$response = $this->client->request('GET', $this->api_url . '/documents/' . $id . $field_query);
		if ( $response->getStatusCode() != 200 ) {
			throw new \Exception('Failed to get document. Status code: ' . $response->getStatusCode());
		}
		$returned_document = SearchDocument::fromJSON($response->getBody());
		return $returned_document;
	}

	/**
	 * Search for documents
	 */
	public function search(SearchParams $params): SearchResult {
		$request_url = $this->api_url . '/search?' . $params->toQueryString();
		$response = $this->client->request('GET', $request_url, [
			'headers' => [
				'Authorization' => 'Bearer ' . $this->api_key,
				'Content-Type' => 'application/json'
			],
		]);
		if ( $response->getStatusCode() != 200 ) {
			throw new \Exception('Failed to search documents. Status code: ' . $response->getStatusCode());
		}
		$result = SearchResult::fromJSON($response->getBody());
		return $result;
	}
}