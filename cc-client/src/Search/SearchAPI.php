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

use MeshResearch\CCClient\CCClientOptions;

class SearchAPI {
	const MAX_DOCUMENTS_PER_BULK_INDEX_REQUEST = 100;
	
	private $client;
	private $api_key;
	private $api_url;
	private $admin_api_key;

	public function __construct(
		private CCClientOptions $options,
	) {
		if ( empty($options->cc_search_key ) ) {
			throw new \Exception('API key is required');
		}
		$this->api_key = $options->cc_search_key;
		if ( empty($options->cc_search_endpoint ) ) {
			throw new \Exception('API URL is required');
		}
		$this->api_url = $options->cc_search_endpoint;
		$this->admin_api_key = $options->cc_search_admin_key ?? '';
		
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
		try {
			$response = $this->client->request('GET', $this->api_url . '/ping');
		} catch ( \GuzzleHttp\Exception\ClientException $e ) {
			return false;
		}
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
	public function index(SearchDocument $document): SearchDocument | false {
		try {
			$response = $this->client->request('POST', $this->api_url . '/documents', [
				'headers' => [
					'Authorization' => 'Bearer ' . $this->api_key,
					'Content-Type' => 'application/json'
				],
				'body' => $document->toJSON()
			]);
		} catch ( \GuzzleHttp\Exception\ClientException $e ) {
			return false;
		}
		if ( $response->getStatusCode() != 200 ) {
			return false;
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
		$current_chunk = 0;
		foreach ( $document_chunks as $chunk ) {
			$current_chunk++;
			$documents_json = json_encode($chunk);
			try {
				$response = $this->client->request('POST', $this->api_url . '/documents/bulk', [
					'headers' => [
						'Authorization' => 'Bearer ' . $this->api_key,
						'Content-Type' => 'application/json'
					],
					'body' => $documents_json
				]);
			} catch ( \Exception $e ) {
				if ( $show_progress && class_exists('WP_CLI') ) {
					\WP_CLI::warning("Failed to index document chunk: $current_chunk : " . $e->getMessage());
				}
				continue;
			}
			if ( $response->getStatusCode() != 200 ) {
				if ( $show_progress && class_exists('WP_CLI') ) {
					\WP_CLI::warning("Failed to index document chunk: $current_chunk . Received response code: " . $response->getStatusCode());
				}
				continue;
			}
			$returned_documents = array_merge($returned_documents, SearchDocument::fromJSON($response->getBody()));
			if ( $show_progress && class_exists('WP_CLI') ) {
				\WP_CLI::line('Indexed ' . count($returned_documents) . ' documents');
			}
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
		try {
			$response = $this->client->request('PUT', $this->api_url . '/documents/' . $document->_id, [
				'headers' => [
					'Authorization' => 'Bearer ' . $this->api_key,
					'Content-Type' => 'application/json'
				],
				'body' => $document->toJSON()
			]);
		} catch ( \Exception $e ) {
			return false;
		}
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
		} catch ( \Exception $e ) {
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
		} catch ( \Exception $e ) {
			return false;
		}
		return $response->getStatusCode() == 200;
	}

	/**
	 * Reset the index
	 */
	public function reset_index(): bool {
		try {
			$response = $this->client->request('POST', $this->api_url . '/index', [
				'headers' => [
					'Authorization' => 'Bearer ' . $this->admin_api_key
				]
			]);
		} catch ( \Exception $e ) {
			return false;
		}
		return $response->getStatusCode() == 200;
	}

	/**
	 * Get a document
	 */
	public function get_document(string $id, $fields = []): SearchDocument | false {
		$field_query = '';
		if ( ! empty($fields) ) {
			$field_query = '?fields=' . implode(',', $fields);
		}
		try {
			$response = $this->client->request('GET', $this->api_url . '/documents/' . $id . $field_query);
		} catch ( \Exception $e ) {
			return false;
		}
		if ( $response->getStatusCode() != 200 ) {
			return false;
		}
		$returned_document = SearchDocument::fromJSON($response->getBody());
		return $returned_document;
	}

	/**
	 * Search for documents
	 */
	public function search(SearchParams $params): SearchResult | false {
		$request_url = $this->api_url . '/search?' . $params->toQueryString();
		try {
			$response = $this->client->request('GET', $request_url, [
				'headers' => [
					'Authorization' => 'Bearer ' . $this->api_key,
					'Content-Type' => 'application/json'
				],
			]);
		} catch ( \Exception $e ) {
			return false;
		}
		if ( $response->getStatusCode() != 200 ) {
			return false;
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