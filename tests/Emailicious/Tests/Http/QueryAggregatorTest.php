<?php

namespace Emailicious\Tests\Http;

use Emailicious\Http\QueryAggregator;
use Emailicious\Tests\EmailiciousTest;
use Guzzle\Http\QueryString;

class QueryAggregatorTest extends EmailiciousTest {
	public function setUp() {
		parent::setUp();
		$this->aggregator = new QueryAggregator;
		$this->query = new QueryString;
	}

	public function testAssocArray() {
		$this->assertEquals(
			array('assoc.foo' => 'bar', 'assoc.baz' => 'bar'),
			$this->aggregator->aggregate('assoc', array('foo' => 'bar', 'baz' => 'bar'), $this->query)
		);
	}

	public function testNonNestedArray() {
		$this->assertEquals(
			array('array' => array(1, 2, 3)),
			$this->aggregator->aggregate('array', array(1, 2, 3), $this->query)
		);
	}

	public function testNestedArray() {
		$this->assertEquals(
			array('array.nested' => array(1, 2, 3)),
			$this->aggregator->aggregate('array', array('nested' => array(1, 2, 3)), $this->query)
		);
	}

	public function testNestedAssocArray() {
		$this->assertEquals(
			array('array.nested.foo' => 'bar'),
			$this->aggregator->aggregate('array', array('nested' => array('foo' => 'bar')), $this->query)
		);
	}

	public function testTwoDepthNestedArray() {
		$this->assertEquals(
			array('array%5B0%5D.foo.bar' => array(1, 2, 3)),
			$this->aggregator->aggregate('array', array(array('foo' => array('bar' => array(1, 2, 3)))), $this->query)
		);
	}
}
