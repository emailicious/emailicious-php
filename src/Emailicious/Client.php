<?php

namespace Emailicious;

use Guzzle\Http\Client as GuzzleClient;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Message\Request;

class Client {
	const BASE_URL = 'https://{account}.emailicious.com/api/{version}';
	const API_VERSION = 'v1';
	const VERSION = '1.0.0';
	const USER_AGENT_FORMAT = 'emailicious/%s;php';

	protected $_client;
	private $_latestRequest;
	private $_latestResponse;

	public function __construct($account, $username, $password) {
		$this->_client = new GuzzleClient(self::BASE_URL, array(
			'account' => $account,
			'version' => self::API_VERSION,
		));
		$this->_client->setUserAgent(sprintf(self::USER_AGENT_FORMAT, self::VERSION));
		$this->_client->setDefaultOption('auth', array($username, $password, 'Basic'));
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

	protected function _sendRequest(Request $request) {
		$request->setHeader('Accept', 'application/json');
		$this->_latestRequest = $request;
		$response = $request->send();
		$this->_latestResponse = $response;
		return $response;
	}

	private function _buildRessource($ressource, $parameters) {
		if (is_array($parameters)) {
			$ressource .= '?' . http_build_query($parameters);
		}
		return $ressource;
	}

	public function get($ressource, $parameters = NULL) {
		$request = $this->_client->get($this->_buildRessource($ressource, $parameters));
		return $this->_sendRequest($request)->json();
	}

	public function post($ressource, $data = NULL, $parameters = NULL) {
		$request = $this->_client->post($this->_buildRessource($ressource, $parameters), NULL, $data);
		return $this->_sendRequest($request)->json();
	}

	public function put($ressource, $data = NULL, $parameters = NULL) {
		$request = $this->_client->put($this->_buildRessource($ressource, $parameters), NULL, $data);
		return $this->_sendRequest($request)->json();
	}

	public function patch($ressource, $data = NULL, $parameters = NULL) {
		$request = $this->_client->patch($this->_buildRessource($ressource, $parameters), NULL, $data);
		return $this->_sendRequest($request)->json();
	}
}
