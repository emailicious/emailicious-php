<?php

namespace Emailicious\Tests;

use Emailicious\Client;
use Guzzle\Http\Message\Request;
use Guzzle\Http\Message\Response;
use Guzzle\Plugin\Mock\MockPlugin;
use Guzzle\Http\QueryString;
use Guzzle\Http\EntityBody;

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
		$this->files = array('file' => __FILE__, 'curlFile' => new \CURLFile(__FILE__, 'application/php', 'file.php'));
		$this->response = new Response(200, array('Content-Type' => 'application/json'), json_encode($this->data));
		$mock->addResponse($this->response);
		$guzzle->addSubscriber($mock);
		$this->client = $client;
	}

	private function _testRequestDefaults($request) {
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

	private function _testMultipartBody($request) {
		$contenType = (string) $request->getHeader('Content-Type');
		if (preg_match('/^multipart\/form-data; boundary=(\w+)$/', $contenType, $matches)) {
			$boundary = $matches[1];
			$content = file_get_contents(__FILE__);
			$this->assertEquals((string) $request->getBody(), implode("\r\n", array(
					"--$boundary",
					'Content-Disposition: form-data; name="foo"',
					'',
					'bar',
					"--$boundary",
					'Content-Disposition: form-data; name="mah"',
					'',
					'foo',
					"--$boundary",
					'Content-Disposition: form-data; name="mah"',
					'',
					'bob',
					"--$boundary",
					'Content-Disposition: form-data; name="mah"',
					'',
					'test',
					"--$boundary",
					'Content-Disposition: form-data; name="file"; filename="ClientTest.php"',
					'Content-Type: application/octet-stream',
					'',
					$content,
					"--$boundary",
					'Content-Disposition: form-data; name="curlFile"; filename="file.php"',
					'Content-Type: application/php',
					'',
					$content,
					"--$boundary--\r\n",
			)));
		} else $this->fail('Invalid Content-Type header. Expected multipart/form-data with a boundary.');
	}

	public function testPostFile() {
		$this->assertEquals($this->client->post('ressource', $this->data, $this->files), $this->data);
		$request = $this->client->getLatestRequest();
		$this->assertEquals($request->getMethod(), 'POST');
		$this->_testMultipartBody($request);
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
		$this->assertEquals($this->client->put('ressource', $this->data, $this->files), $this->data);
		$request = $this->client->getLatestRequest();
		$this->assertEquals($request->getMethod(), 'PUT');
		$this->_testMultipartBody($request);
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
		$this->assertEquals($this->client->patch('ressource', $this->data, $this->files), $this->data);
		$request = $this->client->getLatestRequest();
		$this->assertEquals($request->getMethod(), 'PATCH');
		$this->_testMultipartBody($request);
	}
}
