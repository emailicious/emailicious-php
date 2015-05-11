<?php

namespace Emailicious\Subscribers\Exceptions;

class SubscriberNotFound extends \RuntimeException {
	private $_listId;
	private $_lookup;
	private $_value;

	public function __construct($listId, $lookup, $value) {
		parent::__construct("Subscriber with $lookup = $value not found in list $listId", 404);
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
