<?php

namespace Emailicious\Tests;

use Emailicious\Client;
use Mockery;

abstract class EmailiciousTest extends \PHPUnit_Framework_TestCase {
	public function setUp() {
		$this->client = Mockery::mock(new Client('foo', 'bar', 'baz'));
	}

	public function tearDown() {
		Mockery::close();
	}
}
