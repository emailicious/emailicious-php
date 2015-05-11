<?php

namespace Emailicious\Tests;

use Emailicious\Model;

class TestModel extends Model {
	private $_ressource;

	public function __construct($client, $ressource, array $data = array()) {
		$this->setClient($client);
		$this->_ressource = $ressource;
		$this->setData($data);
	}

	public function getRessource() {
		return $this->_ressource;
	}
}

class ModelTest extends EmailiciousTest {
	public function testGet() {
		$instance = new TestModel($this->client, 'foo', array('foo' => 'bar'));
		$this->assertEquals($instance->foo, 'bar');
	}

	public function testSet() {
		$instance = new TestModel($this->client, 'foo');
		$instance->foo = 'bar';
		$this->assertEquals($instance->getData(), array('foo' => 'bar'));
	}

	public function testUpdate() {
		$ressource = 'ressource';
		$data = array('foo' => 'bar');
		$this->client->shouldReceive('put')->once()->with($ressource, $data)->andReturn($data);
		$instance = new TestModel($this->client, $ressource, $data);
		$instance->update();
	}

	public function testPartialUpdate() {
		$ressource = 'ressource';
		$data = array('foo' => 'bar', 'baz' => 'foo');
		$this->client->shouldReceive('patch')->once()->with($ressource, array('baz' => 'foo'))->andReturn($data);
		$instance = new TestModel($this->client, $ressource, $data);
		$instance->update(array('baz'));
	}
}