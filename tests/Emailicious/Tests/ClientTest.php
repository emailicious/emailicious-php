<?php

namespace Emailicious\Tests;

use Emailicious\Client;
use Guzzle\Http\Message\Request;
use Guzzle\Http\Message\Response;
use Guzzle\Plugin\Mock\MockPlugin;
use Guzzle\Http\QueryString;

class TestClient extends Client {
	public function getGuzzleClient() {
		return $this->_client;
	}
}

class ClientTest extends \PHPUnit_Framework_TestCase {
	public function setUp() {
		$client = new TestClient('foo', 'bar', 'baz');
		$guzzle = $client->getGuzzleClient();
		$mock = new MockPlugin();
		$this->parameters = array('bar' => 'baz', 'meh' => array(1, 2, 3));
		$this->data = array('foo' => 'bar', 'mah' => array('foo', 'bob', 'test'));
		$this->files = array('file' => __FILE__);
		$this->response = new Response(200, array('Content-Type' => 'application/json'), json_encode($this->data));
		$mock->addResponse($this->response);
		$guzzle->addSubscriber($mock);
		$this->client = $client;
	}

	public function _testRequestDefaults($request) {
		$this->assertEquals($request->getScheme(), 'https');
		$this->assertEquals($request->getHost(), 'foo.emailicious.com');
		$this->assertEquals((string) $request->getHeader('User-Agent'), 'emailicious/1.0.0;php');
		$this->assertEquals($request->getUsername(), 'bar');
		$this->assertEquals($request->getPassword(), 'baz');
		$this->assertEquals((string) $request->getHeader('Accept'), 'application/json');
		$this->assertEquals($request->getPath(), '/api/v1/ressource');
		$this->assertEquals($request->getQuery(true), 'bar=baz&meh=1&meh=2&meh=3');
	}

	public function testGetLatestResponse() {
		$this->client->get('ressource');
		$this->assertEquals($this->response, $this->client->getLatestResponse());
	}

	public function testGet() {
		$this->assertEquals($this->client->get('ressource', $this->parameters), $this->data);
		$request = $this->client->getLatestRequest();
		$this->_testRequestDefaults($request);
		$this->assertEquals($request->getMethod(), 'GET');
	}

	public function testPost() {
		$this->assertEquals($this->client->post('ressource', $this->data, null, $this->parameters), $this->data);
		$request = $this->client->getLatestRequest();
		$this->_testRequestDefaults($request);
		$this->assertEquals($request->getMethod(), 'POST');
		$this->assertEquals((string) $request->getHeader('Content-Type'), 'application/x-www-form-urlencoded; charset=utf-8');
		$this->assertEquals((string) $request->getPostFields(), 'foo=bar&mah=foo&mah=bob&mah=test');
	}

	public function testPostFile() {
		$this->assertEquals($this->client->post('ressource', null, $this->files), $this->data);
		$request = $this->client->getLatestRequest();
		$this->assertEquals($request->getMethod(), 'POST');
		$this->assertEquals((string) $request->getHeader('Content-Type'), 'multipart/form-data');
		foreach ($request->getPostFiles() as $fieldname => $files) {
			foreach ($files as $file) {
				$this->assertEquals($this->files[$fieldname], $file->getFileName());
			}
		}
	}

	public function testPut() {
		$this->assertEquals($this->client->put('ressource', $this->data, null, $this->parameters), $this->data);
		$request = $this->client->getLatestRequest();
		$this->_testRequestDefaults($request);
		$this->assertEquals($request->getMethod(), 'PUT');
		$this->assertEquals((string) $request->getHeader('Content-Type'), 'application/x-www-form-urlencoded; charset=utf-8');
		$this->assertEquals((string) $request->getPostFields(), 'foo=bar&mah=foo&mah=bob&mah=test');
	}

	public function testPutFile() {
		$this->assertEquals($this->client->put('ressource', null, $this->files), $this->data);
		$request = $this->client->getLatestRequest();
		$this->assertEquals($request->getMethod(), 'PUT');
		$this->assertEquals((string) $request->getHeader('Content-Type'), 'multipart/form-data');
		foreach ($request->getPostFiles() as $fieldname => $files) {
			foreach ($files as $file) {
				$this->assertEquals($this->files[$fieldname], $file->getFileName());
			}
		}
	}

	public function testPatch() {
		$this->assertEquals($this->client->patch('ressource', $this->data, null, $this->parameters), $this->data);
		$request = $this->client->getLatestRequest();
		$this->_testRequestDefaults($request);
		$this->assertEquals($request->getMethod(), 'PATCH');
		$this->assertEquals((string) $request->getHeader('Content-Type'), 'application/x-www-form-urlencoded; charset=utf-8');
		$this->assertEquals((string) $request->getPostFields(), 'foo=bar&mah=foo&mah=bob&mah=test');
	}

	public function testPatchFile() {
		$this->assertEquals($this->client->patch('ressource', null, $this->files), $this->data);
		$request = $this->client->getLatestRequest();
		$this->assertEquals($request->getMethod(), 'PATCH');
		$this->assertEquals((string) $request->getHeader('Content-Type'), 'multipart/form-data');
		foreach ($request->getPostFiles() as $fieldname => $files) {
			foreach ($files as $file) {
				$this->assertEquals($this->files[$fieldname], $file->getFileName());
			}
		}
	}
}
