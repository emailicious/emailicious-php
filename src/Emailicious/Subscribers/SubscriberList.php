<?php

namespace Emailicious\Subscribers;

use Emailicious\Client;
use Emailicious\Model;
use Emailicious\ResultsIterator;

class SubscriberList extends Model {
	const LIST_RESSOURCE_FORMAT = 'lists';
	const INSTANCE_RESSOURCE_FORMAT = 'lists/%d';

	public static function all(Client $client) {
		$response = $client->get(self::getListRessource());
		return new ResultsIterator($client, $response, function($result) use ($client) {
			return new SubscriberList($client, $result);
		});
	}

	public static function get(Client $client, $id) {
		$data = $client->get(self::getInstanceRessource($id));
		return new SubscriberList($client, $data);
	}

	private function __construct(Client $client, $data) {
		$this->setClient($client);
		$this->setData($data);
	}

	public function getRessource() {
		return self::getInstanceRessource($this->id);
	}
}
