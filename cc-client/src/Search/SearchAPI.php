<?php
/**
 * Wrapper for the CC-Search API
 * 
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Search;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

use function MeshResearch\CCClient\get_ccc_options;

class SearchAPI {
	const MAX_DOCUMENTS_PER_BULK_INDEX_REQUEST = 100;
	
	private $client;

	public function __construct(
		private string $api_key = '',
		private string $api_url = '',
		private string $admin_api_key = ''
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
		if ( empty($this->admin_api_key ) ) {
			$this->admin_api_key = $options['cc_search_admin_key'] ?? '';
		}
		
		$handler_stack = HandlerStack::create(new CurlHandler());
		$handler_stack->push($this->_retryMiddleware());
		$this->client = new Client(
			[
				'handler' => $handler_stack,
				'timeout' => 60,
			]
		);
	}

	/**
	 * Ping the API to check if it is up
	 */
	public function ping(): bool {
		$response = $this->client->request('GET', $this->api_url . '/ping');
		return $response->getStatusCode() == 200;
	}

	/**
	 * Check if the API key is valid
	 */
	public function check_api_key(): bool {
		try {
			$response = $this->client->request('GET', $this->api_url . '/auth_check', [
				'headers' => [
					'Authorization' => 'Bearer ' . $this->api_key,
					'Content-Type' => 'application/json'
				],
			]);
		} catch ( \GuzzleHttp\Exception\ClientException $e ) {
			return false;
		}
		return $response->getStatusCode() == 200;
	}

	/**
	 * Check if the admin API key is valid
	 */
	public function check_admin_api_key(): bool {
		try {
			$response = $this->client->request('GET', $this->api_url . '/admin_auth_check', [
				'headers' => [
					'Authorization' => 'Bearer ' . $this->admin_api_key,
					'Content-Type' => 'application/json'
				],
			]);
		} catch ( \GuzzleHttp\Exception\ClientException $e ) {
			return false;
		}
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
	public function bulk_index(array $documents, bool $show_progress = false): array {
		$document_chunks = array_chunk($documents, self::MAX_DOCUMENTS_PER_BULK_INDEX_REQUEST);
		$returned_documents = [];
		if ( $show_progress && class_exists('WP_CLI') ) {
			\WP_CLI::line('Indexing ' . count($documents) . ' documents in ' . count($document_chunks) . ' chunks...');
		}

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
		if ( $show_progress && class_exists('WP_CLI') ) {
			\WP_CLI::line('Indexing complete');
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
		try {
			$response = $this->client->request('DELETE', $this->api_url . '/documents/' . $id, [
				'headers' => [
					'Authorization' => 'Bearer ' . $this->api_key
				]
			]);
		} catch ( \GuzzleHttp\Exception\ClientException $e ) {
			return false;
		}
		return $response->getStatusCode() == 200;
	}

	/**
	 * Delete all documents from a node
	 */
	public function delete_node(string $node ): bool {
		try {
			$response = $this->client->request('DELETE', $this->api_url . '/documents?network_node=' . $node, [
				'headers' => [
					'Authorization' => 'Bearer ' . $this->admin_api_key
				]
			]);
		} catch ( \GuzzleHttp\Exception\ClientException $e ) {
			return false;
		}
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

	/**
	 * Possibly retry failed requests.
	 *
	 * @see: https://github.com/guzzle/guzzle/issues/1806
	 */
	public function _retryMiddleware(): callable {
		return Middleware::retry(
			function (
				int $retries,
				Request $request,
				Response $response = null,
				Exception $error = null
			) {
				if ($retries >= 5) {
					if ( class_exists('WP_CLI') ) {
						\WP_CLI::warning('Maximum retries reached. Aborting...');
					}
					return false;
				}

				if ($error instanceof ConnectException) {
					if ( class_exists('WP_CLI') ) {
						\WP_CLI::warning('Connection error. Retrying...');
					}
					return true;
				}

				if ($response) {
					if ($response->getStatusCode() >= 500) {
						if ( class_exists('WP_CLI') ) {
							\WP_CLI::warning('Server error. Retrying...');
						}
						return true;
					}
				}

				if ( $error && class_exists('WP_CLI') ) {
					\WP_CLI::warning('Exception thrown: ' . $error->getMessage());
					\WP_CLI::warning('Unhandled HTTP error. Aborting...');
				}
				return false;
			},
			function (int $retries) {
				return 1000 * $retries;
			}
		);
	}
}