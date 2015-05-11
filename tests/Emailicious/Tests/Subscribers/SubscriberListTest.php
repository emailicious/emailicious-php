<?php

namespace Emailicious\Tests\Subscribers;

use Emailicious\Client;
use Emailicious\Subscribers\SubscriberList;
use Emailicious\Tests\EmailiciousTest;

class SubscriberListTest extends EmailiciousTest {
	public function testAll() {
		$this->client->shouldReceive('get')->once()->with(SubscriberList::getListRessource())->andReturn(array(
			'next' => NULL,
			'results'=> array(
				array('id' => 0),
				array('id' => 1),
				array('id' => 2),
			)
		));
		foreach (SubscriberList::all($this->client) as $id => $list) {
			$this->assertInstanceOf('Emailicious\Subscribers\SubscriberList', $list);
			$this->assertEquals($list->id, $id);
		}
	}

	public function testGet() {
		$instanceId = 1;
		$ressource = SubscriberList::getInstanceRessource($instanceId);
		$this->client->shouldReceive('get')->once()->with($ressource)->andReturn(array(
			'id' => $instanceId,
			'name' => 'name'
		));
		$list = SubscriberList::get($this->client, 1);
		$this->assertInstanceOf('Emailicious\Subscribers\SubscriberList', $list);
		$this->assertEquals($list->id, $instanceId);
		$this->assertEquals($list->name, 'name');
		$this->assertEquals($list->getRessource(), $ressource);
	}
}
