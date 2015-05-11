<?php

namespace Emailicious\Tests;

use Emailicious\ResultsIterator;
use Emailicious\Tests\EmailiciousTest;

class ResultsIteratorTest extends EmailiciousTest {
	public function testIteration() {
		$this->client->shouldReceive('get')->with('results?page=2')->once()->andReturn(
			array('results' => ['3', '4', '5'], 'next' => NULL)
		);
		$response = array(
			'next' => 'https://foo.emailicious.com/api/v1/results?page=2',
			'results' => array('0', '1', '2')
		);
		$iterator = new ResultsIterator($this->client, $response, function($result) {
			return (int) $result;
		});
		$expected = array(0, 1, 2, 3, 4, 5);
		foreach ($iterator as $key => $value) {
			$this->assertInternalType('int', $value);
			$this->assertEquals($expected[$key], $value);
		}
	}

	public function testDefaultInstanceConstruction() {
		$response = array('next' => NULL, 'results' => array(0, 1, 2));
		$iterator = new ResultsIterator($this->client, $response);
		$expected = array(0, 1, 2);
		foreach ($iterator as $key => $value) {
			$this->assertInternalType('int', $value);
			$this->assertEquals($expected[$key], $value);
		}
	}
}
