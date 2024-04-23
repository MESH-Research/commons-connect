<?php
/**
 * Wrapper for the CC-Search API
 * 
 * @package MESHResearch\CCClient
 */

namespace MESHResearch\CCClient;

use GuzzleHttp\Client;

class SearchAPI
{
	private $client;
	private $api_key;
	private $api_url;

	public function __construct($api_key, $api_url)
	{
		$this->api_key = $api_key;
		$this->api_url = $api_url;
		$this->client = new Client();
	}

	/**
	 * Ping the API to check if it is up
	 */
	public function ping()
	{
		$response = $this->client->request('GET', $this->api_url . '/ping');
		return $response->getStatusCode() == 200;
	}
}