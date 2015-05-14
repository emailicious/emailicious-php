<?php

namespace Emailicious\Subscribers\Exceptions;

use Guzzle\Http\Exception\ClientErrorResponseException;

class SubscriberNotFound extends ClientErrorResponseException {
	private $_listId;
	private $_lookup;
	private $_value;

	public static function fromException(ClientErrorResponseException $exception, $listId, $lookup, $value) {
		$instance = new static($listId, $lookup, $value);
		$instance->setRequest($exception->getRequest());
		$instance->setResponse($exception->getResponse());
		return $instance;
	}

	public function __construct($listId, $lookup, $value) {
		parent::__construct("Subscriber with $lookup = $value not found in list $listId");
		$this->_listId = $listId;
		$this->_lookup = $lookup;
		$this->_value = $value;
	}

	public function getListId() {
		return $this->_listId;
	}

	public function getLookup() {
		return $this->_lookup;
	}

	public function getValue() {
		return $this->_value;
	}
}
