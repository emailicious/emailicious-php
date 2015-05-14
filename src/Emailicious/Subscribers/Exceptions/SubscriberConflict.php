<?php

namespace Emailicious\Subscribers\Exceptions;

use Guzzle\Http\Exception\ClientErrorResponseException;

class SubscriberConflict extends ClientErrorResponseException {
	private $_listId;
	private $_creationData;
	private $_conflictualSubscriber;

	public static function fromException(
			ClientErrorResponseException $exception, $listId, $creationData, $conflictualSubscriber) {
		$instance = new static($listId, $creationData, $conflictualSubscriber);
		$instance->setRequest($exception->getRequest());
		$instance->setResponse($exception->getResponse());
		return $instance;
	}

	public function __construct($listId, $creationData, $conflictualSubscriber) {
		parent::__construct(
			"Subscriber creation data conflicts with subscriber #{$conflictualSubscriber->id} of list #{$listId}"
		);
		$this->_listId = $listId;
		$this->_creationData = $creationData;
		$this->_conflictualSubscriber = $conflictualSubscriber;
	}

	public function getListId() {
		return $this->_listId;
	}

	public function getCreationData() {
		return $this->_creationData;
	}

	public function getConflictualSubscriber() {
		return $this->_conflictualSubscriber;
	}
}
