<?php

namespace Emailicious\Subscribers\Exceptions;

class SubscriberConflict extends \RuntimeException {
	private $_listId;
	private $_creationData;
	private $_conflictualSubscriber;

	public function __construct($listId, $creationData, $conflictualSubscriber) {
		parent::__construct(
			"Subscriber creation data conflicts with subscriber #{$conflictualSubscriber->id} of list #{$listId}", 409
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
