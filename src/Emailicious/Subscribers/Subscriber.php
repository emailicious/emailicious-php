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

	public static function all($client, $listId, $status = NULL) {
		$parameters = array('subscription' => $status) ? $status : NULL;
		$response = $client->get(self::getListRessource($listId), $parameters);
		return new ResultsIterator($client, $response, function($result) use ($client, $listId) {
			return new static($client, $listId, $result);
		});
	}

	public static function get($client, $listId, $id) {
		try {
			$data = $client->get(self::getInstanceRessource($listId, $id));
		} catch (ClientErrorResponseException $exception) {
			if ($exception->getResponse()->getStatusCode() == 404) {
				throw new SubscriberNotFound($listId, 'id', $id);
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
		throw new SubscriberNotFound($listId, 'email', $email);
	}

	public static function create($client, $listId, $creationData) {
		try {
			$data = $client->post(self::getListRessource($listId), $creationData);
		} catch (ClientErrorResponseException $exception) {
			$response = $exception->getResponse();
			if ($exception->getResponse()->getStatusCode() == 409) {
				$conflictualSubscriber = new static($client, $listId, $response->json());
				throw new SubscriberConflict($listId, $creationData, $conflictualSubscriber);
			}
			throw $exception;
		}
		return new static($client, $listId, $data);
	}

	protected function __construct(Client $client, $listId, $data) {
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

	public function activate($confirm = NULL) {
		$data = $confirm ? array('confirm' => 1) : NULL;
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
