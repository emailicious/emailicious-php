<?php

namespace Emailicious\Tests\Subscribers;

use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Http\Message\Request;
use Guzzle\Http\Message\Response;

use Emailicious\Client;
use Emailicious\Subscribers\Subscriber;
use Emailicious\Subscribers\Exceptions\SubscriberConflict;
use Emailicious\Subscribers\Exceptions\SubscriberNotFound;
use Emailicious\Tests\EmailiciousTest;

class TestSubscriber extends Subscriber {
	public static function createInstance(Client $client, $listId, $data) {
		return new TestSubscriber($client, $listId, $data);
	}
}

class SubscriberTest extends EmailiciousTest {
	public function setUp() {
		parent::setUp();
		$this->listId = 1;
	}

	public function testToString() {
		$this->assertEquals(
			"Subscriber #1 of list #1: email@domain.com",
			TestSubscriber::createInstance($this->client, $this->listId, array(
				'id' => 1, 'email' => 'email@domain.com'
			))
		);
	}

	public function testAll() {
		$ressource = Subscriber::getListRessource($this->listId);
		$status = 'active';
		$this->client->shouldReceive('get')->once()->with($ressource, array('subscription' => $status))->andReturn(array(
			'next' => null,
			'results'=> array(
				array('id' => 0),
				array('id' => 1),
				array('id' => 2),
			)
		));
		foreach (Subscriber::all($this->client, $this->listId, $status) as $id => $subscriber) {
			$this->assertInstanceOf('Emailicious\Subscribers\Subscriber', $subscriber);
			$this->assertEquals($id, $subscriber->id);
		}
	}

	public function testGet() {
		$instanceId = 1;
		$ressource = Subscriber::getInstanceRessource($this->listId, $instanceId);
		$this->client->shouldReceive('get')->once()->with($ressource)->andReturn(array(
			'id' => 1,
			'email' => 'email@domain.com',
		));
		$subscriber = Subscriber::get($this->client, $this->listId, $instanceId);
		$this->assertInstanceOf('Emailicious\Subscribers\Subscriber', $subscriber);
		$this->assertEquals($instanceId, $subscriber->id);
		$this->assertEquals('email@domain.com', $subscriber->email);
		$this->assertEquals($ressource, $subscriber->getRessource());
	}

	public function testGetNotFound() {
		$instanceId = 1;
		$ressource = Subscriber::getInstanceRessource($this->listId, $instanceId);
		$request = new Request('GET', '');
		$response = new Response(404);
		$exception = ClientErrorResponseException::factory($request, $response);
		$this->client->shouldReceive('get')->once()->with($ressource)->andThrow($exception);
		try {
			Subscriber::get($this->client, $this->listId, $instanceId);
			$this->fail('SubscriberNotFound not thrown.');
		} catch (SubscriberNotFound $notFound) {
			$this->assertEquals($this->listId, $notFound->getListId());
			$this->assertEquals('id', $notFound->getLookup());
			$this->assertEquals($instanceId, $notFound->getValue());
		}
	}

	public function testGetExceptionNotSilenced() {
		$instanceId = 1;
		$ressource = Subscriber::getInstanceRessource($this->listId, $instanceId);
		$request = new Request('GET', '');
		$response = new Response(400);
		$exception = ClientErrorResponseException::factory($request, $response);
		$this->client->shouldReceive('get')->once()->with($ressource)->andThrow($exception);
		$this->setExpectedException('Guzzle\Http\Exception\ClientErrorResponseException');
		Subscriber::get($this->client, $this->listId, $instanceId);
	}

	public function testGetByEmail() {
		$email = 'email@domain.com';
		$ressource = Subscriber::getListRessource($this->listId);
		$this->client->shouldReceive('get')->once()->with($ressource, array('email' => $email))->andReturn(array(
			'count' => 1,
			'results' => array(
				array(
					'id' => 1,
					'email' => 'email@domain.com',
				)
			)
		));
		$subscriber = Subscriber::getByEmail($this->client, $this->listId, $email);
		$this->assertInstanceOf('Emailicious\Subscribers\Subscriber', $subscriber);
		$this->assertEquals(1, $subscriber->id);
		$this->assertEquals($email, $subscriber->email);
	}

	public function testGetByEmailNotFound() {
		$email = 'email@domain.com';
		$ressource = Subscriber::getListRessource($this->listId);
		$this->client->shouldReceive('get')->once()->with($ressource, array('email' => $email))->andReturn(array(
			'count' => 0,
			'results' => array()
		));
		$this->client->shouldReceive('getLatestRequest')->once()->andReturn(new Request('GET', $ressource));
		$this->client->shouldReceive('getLatestResponse')->once()->andReturn(new Response(200));
		try {
			Subscriber::getByEmail($this->client, $this->listId, $email);
			$this->fail('SubscriberNotFound not thrown.');
		} catch (SubscriberNotFound $notFound) {
			$this->assertEquals($this->listId, $notFound->getListId());
			$this->assertEquals('email', $notFound->getLookup());
			$this->assertEquals($email, $notFound->getValue());
		}
	}

	public function testCreate() {
		$data = array('email' => 'email@domain.com', 'first_name' => 'foo');
		$ressource = Subscriber::getListRessource($this->listId);
		$this->client->shouldReceive('post')->once()->with($ressource, $data)->andReturn(
			array_merge(array('id' => 1), $data)
		);
		$subscriber = Subscriber::create($this->client, $this->listId, $data);
		$this->assertInstanceOf('Emailicious\Subscribers\Subscriber', $subscriber);
		$this->assertEquals(1, $subscriber->id);
		$this->assertEquals($data['email'], $subscriber->email);
		$this->assertEquals($data['first_name'], $subscriber->first_name);
	}

	public function testCreateConflict() {
		$email = 'email@domain.com';
		$data = array('email' => $email, 'first_name' => 'foo');
		$ressource = Subscriber::getListRessource($this->listId);
		$request = new Request('POST', '');
		$response = new Response(409, null, json_encode(array('id' => 1, 'email' => $email)));
		$exception = ClientErrorResponseException::factory($request, $response);
		$this->client->shouldReceive('post')->once()->with($ressource, $data)->andThrow($exception);
		try {
			Subscriber::create($this->client, $this->listId, $data);
			$this->fail('SubscriberConflict not thrown.');
		} catch (SubscriberConflict $conflict) {
			$this->assertEquals($this->listId, $conflict->getListId());
			$this->assertEquals($data, $conflict->getCreationData());
			$conflictualSubscriber = $conflict->getConflictualSubscriber();
			$this->assertEquals(1, $conflictualSubscriber->id);
			$this->assertEquals($email, $conflictualSubscriber->email);
		}
	}

	public function testCreateExceptionNotSilenced() {
		$email = 'email@domain.com';
		$ressource = Subscriber::getListRessource($this->listId);
		$data = array('email' => $email, 'first_name' => 'foo');
		$request = new Request('POST', '');
		$response = new Response(400);
		$exception = ClientErrorResponseException::factory($request, $response);
		$this->client->shouldReceive('post')->once()->with($ressource, $data)->andThrow($exception);
		$this->setExpectedException('Guzzle\Http\Exception\ClientErrorResponseException');
		Subscriber::create($this->client, $this->listId, $data);
	}

	public function testActivate() {
		$subscriber = TestSubscriber::createInstance($this->client, $this->listId, array('id' => 1));
		$this->client->shouldReceive('post')->once()->with($subscriber->getActivationRessource(), null)->andReturn(array(
			'status' => 'active'
		));
		$subscriber->activate();
		$this->assertEquals('active', $subscriber->subscription);
	}

	public function testConfirmActivation() {
		$subscriber = TestSubscriber::createInstance($this->client, $this->listId, array('id' => 1));
		$this->client->shouldReceive('post')->once()->with(
			$subscriber->getActivationRessource(), array('confirm' => 1)
		)->andReturn(array(
			'status' => 'active'
		));
		$subscriber->activate(TRUE);
		$this->assertEquals('active', $subscriber->subscription);
	}

	public function testUnsubscribe() {
		$subscriber = TestSubscriber::createInstance($this->client, $this->listId, array('id' => 1));
		$this->client->shouldReceive('post')->once()->with($subscriber->getUnsubscriptionRessource())->andReturn(array(
			'status' => 'unsubscribed'
		));
		$subscriber->unsubscribe();
		$this->assertEquals('unsubscribed', $subscriber->subscription);
	}
}
