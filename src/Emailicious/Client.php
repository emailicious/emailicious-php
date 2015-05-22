<?php

namespace Emailicious;

use Guzzle\Http\Client as GuzzleClient;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use Guzzle\Http\Message\Request;
use Guzzle\Http\QueryAggregator\DuplicateAggregator;

class Client {
	const BASE_URL = 'https://{account}.emailicious.com/api/{version}';
	const API_VERSION = 'v1';
	const VERSION = '1.0.0';
	const USER_AGENT_FORMAT = 'emailicious/%s;php';

	protected $_client;
	private $_aggregator;
	private $_latestRequest;
	private $_latestResponse;

	public function __construct($account, $username, $password) {
		$this->_client = new GuzzleClient(self::BASE_URL, array(
			'account' => $account,
			'version' => self::API_VERSION,
		));
		$this->_client->setUserAgent(sprintf(self::USER_AGENT_FORMAT, self::VERSION));
		$this->_client->setDefaultOption('auth', array($username, $password, 'Basic'));
		$this->_aggregator = new DuplicateAggregator;
	}

	public function getBaseUrl() {
		return $this->_client->getBaseUrl();
	}

	public function getLatestRequest() {
		return $this->_latestRequest;
	}

	public function getLatestResponse() {
		return $this->_latestResponse;
	}

	protected function _sendRequest(Request $request, array $parameters = null) {
		$request->setHeader('Accept', 'application/json');
		if (is_array($parameters)) {
			$query = $request->getQuery();
			$query->setAggregator($this->_aggregator);
			foreach ($parameters as $key => $value) {
				$query->set($key, $value);
			}
		}
		if ($request instanceof EntityEnclosingRequestInterface) {
			$request->getPostFields()->setAggregator($this->_aggregator);
		}
		$this->_latestRequest = $request;
		$response = $request->send();
		$this->_latestResponse = $response;
		return $response;
	}

	public function get($ressource, array $parameters = null) {
		$request = $this->_client->get($ressource);
		return $this->_sendRequest($request, $parameters)->json();
	}

	public function post($ressource, array $data = null, array $parameters = null) {
		$request = $this->_client->post($ressource, null, $data);
		return $this->_sendRequest($request, $parameters)->json();
	}

	public function put($ressource, array $data = null, array $parameters = null) {
		$request = $this->_client->put($ressource, null, $data);
		return $this->_sendRequest($request, $parameters)->json();
	}

	public function patch($ressource, array $data = null, array $parameters = null) {
		$request = $this->_client->patch($ressource, null, $data);
		return $this->_sendRequest($request, $parameters)->json();
	}
}
