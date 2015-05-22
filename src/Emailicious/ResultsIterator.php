<?php

namespace Emailicious;

class ResultsIterator implements \Iterator {
	private $_client;
	private $_baseUrl;
	private $_next;
	private $_position;
	private $_results;
	private $_constructResult;

	public function __construct(Client $client, $response, $constructResult = null) {
		$this->_client = $client;
		$this->_baseUrlLen = strlen($client->getBaseUrl());
		$this->_setNext($response);
		$this->_position = 0;
		$this->_constructResult = $constructResult ? $constructResult : array($this, 'constructResult');
		$this->_results = array_map($this->_constructResult, $response['results']);
	}

	private function _setNext($response) {
		$next = $response['next'];
		if (is_null($next)) {
			$this->_next = null;
		} else {
			$this->_next = substr($next, $this->_baseUrlLen + 1);
		}
	}

	public function constructResult($result) {
		return $result;
	}

	public function rewind() {
		$this->_position = 0;
	}

	public function current() {
		return $this->_results[$this->_position];
	}

	public function key() {
		return $this->_position;
	}

	public function next() {
		$this->_position++;
		if (!$this->valid() && $this->_next) {
			$response = $this->_client->get($this->_next);
			$this->_setNext($response);
			$this->_results = array_merge(
				$this->_results, array_map($this->_constructResult, $response['results'])
			);
		}
	}

	public function valid() {
		return isset($this->_results[$this->_position]);
	}
}
