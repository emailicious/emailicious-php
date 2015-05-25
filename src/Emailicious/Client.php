<?php

namespace Emailicious;

use Guzzle\Http\Client as GuzzleClient;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Message\EntityEnclosingRequest;
use Guzzle\Http\Message\PostFile;
use Guzzle\Http\Message\Request;
use Guzzle\Http\QueryString;
use Emailicious\Http\QueryAggregator;

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
		$this->_aggregator = new QueryAggregator;
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
		$this->_latestRequest = $request;
		$response = $request->send();
		$this->_latestResponse = $response;
		return $response;
	}

	private function _createMulipartField($boundary, $name, $value) {
		return implode("\r\n", array(
			"--$boundary",
			"Content-Disposition: form-data; name=\"$name\"",
			'',
			$value,
		));
	}

	private function _createMulipartFile($boundary, $fieldName, $file) {
		if (!($file instanceof PostFile)) {
			$file = new PostFile($fieldName, $file);
		}
		$postName = $file->getPostname();
		$contentType = $file->getContentType();
		if (is_null($contentType)) $contentType = $file->guessContentType();
		$content = file_get_contents($file->getFilename());
		return implode("\r\n", array(
			"--$boundary",
			"Content-Disposition: form-data; name=\"$fieldName\"; filename=\"$postName\"",
			"Content-Type: $contentType",
			'',
			$content,
		));
	}

	private function _createMultipartBody($boundary, QueryString $fields, $files) {
		$body = array();
		foreach ($fields->useUrlEncoding(false)->urlEncode() as $name => $value) {
			if (is_array($value)) {
				foreach ($value as $v) {
					$body[] = $this->_createMulipartField($boundary, $name, $v);
				}
			} else {
				$body[] = $this->_createMulipartField($boundary, $name, $value);
			}
		}
		foreach ($files as $name => $file) {
			$body[] = $this->_createMulipartFile($boundary, $name, $file);
		}
		if (count($body)) $body[] = "--$boundary--\r\n";
		return implode("\r\n", $body);
	}

	protected function _sendEntityEnclosingRequest(EntityEnclosingRequest $request, array $files = null, array $parameters = null) {
		$request->getPostFields()->setAggregator($this->_aggregator);
		if ($files) {
			// The PHP cURL bindings can't deal with duplicate field names when CURL_POSTFIELDS is an array.
			// In order to work around this limitation we have to build the body ourselves.
			// See https://bugs.php.net/bug.php?id=51634.
			$boundary = md5(time());
			$body = $this->_createMultipartBody($boundary, $request->getPostFields(), $files);
			$request->setBody($body, "multipart/form-data; boundary=$boundary");
		}
		return $this->_sendRequest($request, $parameters);
	}

	public function get($ressource, array $parameters = null) {
		$request = $this->_client->get($ressource);
		return $this->_sendRequest($request, $parameters)->json();
	}

	public function post($ressource, array $fields = null, array $files = null, array $parameters = null) {
		$request = $this->_client->post($ressource, null, $fields);
		return $this->_sendEntityEnclosingRequest($request, $files, $parameters)->json();
	}

	public function put($ressource, array $fields = null, array $files = null, array $parameters = null) {
		$request = $this->_client->put($ressource, null, $fields);
		return $this->_sendEntityEnclosingRequest($request, $files, $parameters)->json();
	}

	public function patch($ressource, array $fields = null, array $files = null, array $parameters = null) {
		$request = $this->_client->patch($ressource, null, $fields);
		return $this->_sendEntityEnclosingRequest($request, $files, $parameters)->json();
	}
}
