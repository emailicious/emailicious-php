<?php

namespace Emailicious\Subscribers;

use Guzzle\Http\Exception\ClientErrorResponseException;

use Emailicious\Client;
use Emailicious\Model;
use Emailicious\ResultsIterator;
use Emailicious\Subscribers\Exceptions\SubscriberConflict;
use Emailicious\Subscribers\Exceptions\SubscriberNotFound;

class Subscriber extends Model {
	const LIST_RESSOURCE_FORMAT = 'lists/%d/subscribers';
	const INSTANCE_RESSOURCE_FORMAT = 'lists/%d/subscribers/%d';

	private $_listId;

	public static function all($client, $listId, $status = null) {
		$parameters = $status ? array('subscription' => $status) : null;
		$response = $client->get(self::getListRessource($listId), $parameters);
		$class = __CLASS__;
		return new ResultsIterator($client, $response, function($result) use ($class, $client, $listId) {
			return new $class($client, $listId, $result);
		});
	}

	public static function get($client, $listId, $id) {
		try {
			$data = $client->get(self::getInstanceRessource($listId, $id));
		} catch (ClientErrorResponseException $exception) {
			if ($exception->getResponse()->getStatusCode() == 404) {
				throw SubscriberNotFound::fromException($exception, $listId, 'id', $id);
			}
			throw $exception;
		}
		return new static($client, $listId, $data);
	}

	public static function getByEmail($client, $listId, $email) {
		$data = $client->get(self::getListRessource($listId), array('email' => $email));
		if ($data['count'] == 1) {
			return new static($client, $listId, $data['results'][0]);
		}
		$exception = new SubscriberNotFound($listId, 'email', $email);
		$exception->setRequest($client->getLatestRequest());
		$exception->setResponse($client->getLatestResponse());
		throw $exception;
	}

	public static function create($client, $listId, $creationData) {
		try {
			$data = $client->post(self::getListRessource($listId), $creationData);
		} catch (ClientErrorResponseException $exception) {
			$response = $exception->getResponse();
			if ($exception->getResponse()->getStatusCode() == 409) {
				$conflictualSubscriber = new static($client, $listId, $response->json());
				throw SubscriberConflict::fromException($exception, $listId, $creationData, $conflictualSubscriber);
			}
			throw $exception;
		}
		return new static($client, $listId, $data);
	}

	/*
	 * TODO: Change visibility when support for PHP 5.3 is dropped.
	 */
	public function __construct(Client $client, $listId, $data) {
		$this->_listId = $listId;
		$this->setClient($client);
		$this->setData($data);
	}

	public function __toString() {
		return "Subscriber #{$this->id} of list #{$this->_listId}: $this->email";
	}

	public function getRessource() {
		return self::getInstanceRessource($this->_listId, $this->id);
	}

	public function getActivationRessource() {
		$ressource = $this->getRessource();
		return "$ressource/activate";
	}

	public function activate($confirm = null) {
		$data = $confirm ? array('confirm' => 1) : null;
		$data = $this->getClient()->post($this->getActivationRessource(), $data);
		$this->subscription = $data['status'];
	}

	public function getUnsubscriptionRessource() {
		$ressource = $this->getRessource();
		return "$ressource/unsubscribe";
	}

	public function unsubscribe() {
		$data = $this->getClient()->post($this->getUnsubscriptionRessource());
		$this->subscription = $data['status'];
	}
}
