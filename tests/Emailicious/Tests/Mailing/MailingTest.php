<?php

namespace Emailicious\Tests\Mailing;

use Emailicious\Client;
use Emailicious\Mailing\Mailing;
use Emailicious\Tests\EmailiciousTest;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Message\EntityEnclosingRequest;
use Guzzle\Http\Message\PostFile;
use Mockery;

class MailingTest extends EmailiciousTest {
	public function setUp() {
		parent::setUp();
		$this->listId = 1;
	}

	public function testCreate() {
		$ressource = Mailing::getListRessource();
		$zipFile = null;
		$this->client->shouldReceive('post')->once()->with($ressource, array(
			'list' => $this->listId,
			'name' => 'Mailing',
			'campaign' => 1,
			'segments' => array(1, 2, 3),
			'variants' => array(array(
				'from_email' => 'from@email.local',
				'from_name' => 'From name',
				'replyto_email' => 'replyto@email.local',
				'subject' => 'Subject',
				'layout' => array(
					'text' => 'text',
				),
				'deliveries' => array(array(
					'scheduled_datetime' => '2015-05-25T14:00:00',
				)),
			))
		), Mockery::on(function($files) use (&$zipFile) {
			$key = 'variants[0]layout.zip_file';
			$zipFile = $files[$key];
			unset($files[$key]);
			return ($zipFile instanceof PostFile) && count($files) == 0;
		}))->andReturn(array(
			'id' => 1,
			'list' => $this->listId,
			'name' => 'Mailing',
			'campaign' => 1,
			'segments' => array(1, 2, 3),
			'variants' => array(array(
				'from_email' => 'from@email.local',
				'from_name' => 'From name',
				'replyto_email' => 'replyto@email.local',
				'subject' => 'Subject',
				'layout' => array(
					'source' => '<html><\html>',
					'text' => 'text',
				),
				'deliveries' => array(array(
					'scheduled_datetime' => '2015-05-25T14:00:00',
				)),
			))
		));
		$mailing = Mailing::create($this->client, $this->listId, 'Mailing', array(
			'campaign' => 1,
			'segments' => array(1, 2, 3),
			'from_email' => 'from@email.local',
			'from_name' => 'From name',
			'replyto_email' => 'replyto@email.local',
			'subject' => 'Subject',
			'html' => '<html><\html>',
			'text' => 'text',
			'scheduled_datetime' => '2015-05-25T14:00:00',
		));
		$this->assertNotNull($mailing->getRessource());
		$this->assertNotNull($zipFile);
		$this->assertEquals($zipFile->getContentType(), 'application/zip');
		$this->assertEquals($zipFile->getPostname(), 'layout.zip');
		$this->assertFalse(
			file_exists($zipFile->getFilename()), 'Temporary layout ZIP file should have been deleted.'
		);
	}

	public function testWithExplicitVariants() {
		$ressource = Mailing::getListRessource();
		$zipFiles = array();
		$self = $this;  // Required since PHP 5.3 doesn't support outer scope $this reference in closures.
		$this->client->shouldReceive('post')->once()->with($ressource, array(
			'list' => $this->listId,
			'name' => 'Mailing',
			'variants' => array(
				array(
					'language' => 'fr',
					'from_email' => 'francais@from.email',
					'from_name' => 'De',
					'replyto_email' => 'francais@replyto.email',
					'subject' => 'Sujet',
					'layout' => array(
						'text' => 'Texte',
					),
					'deliveries' => array(
						array('scheduled_datetime' => '2015-05-25T16:25'),
					),
				),
				array(
					'language' => 'en',
					'from_email' => 'default@from.email',
					'from_name' => 'Default from name',
					'replyto_email' => 'default@replyto.email',
					'subject' => 'Default subject',
					'layout' => array(
						'text' => 'Default text',
					),
					'deliveries' => array(
						array('scheduled_datetime' => '2015-05-25T14:00:00'),
					),
				)
			)
		), Mockery::on(function($files) use ($self, &$zipFiles) {
			$index = 0;
			foreach ($files as $fieldName => $file) {
				$self->assertEquals("variants[$index]layout.zip_file", $fieldName);
				$index++;
				$zipFiles[] = $file;
			}
			return True;
		}))->andReturn(array(
			'id' => 1,
			'list' => $this->listId,
			'name' => 'Mailing',
			'variants' => array(
				array(
					'language' => 'fr',
					'from_email' => 'francais@from.email',
					'from_name' => 'De',
					'replyto_email' => 'francais@replyto.email',
					'subject' => 'Sujet',
					'layout' => array(
						'text' => 'Texte',
					),
					'deliveries' => array(
						array('scheduled_datetime' => '2015-05-25T16:25'),
					),
				),
				array(
					'language' => 'en',
					'from_email' => 'default@from.email',
					'from_name' => 'Default from name',
					'replyto_email' => 'default@replyto.email',
					'subject' => 'Default subject',
					'layout' => array(
						'text' => 'Default text',
					),
					'deliveries' => array(
						array('scheduled_datetime' => '2015-05-25T14:00:00'),
					),
				)
			)
		));
		$mailing = Mailing::create($this->client, $this->listId, 'Mailing', array(
			'from_email' => 'default@from.email',
			'from_name' => 'Default from name',
			'replyto_email' => 'default@replyto.email',
			'subject' => 'Default subject',
			'html' => '<html><body>default</body><\html>',
			'text' => 'Default text',
			'deliveries' => array(array()),
			'scheduled_datetime' => '2015-05-25T14:00:00',
			'variants' => array(
				array(
					'language' => 'fr',
					'from_email' => 'francais@from.email',
					'from_name' => 'De',
					'replyto_email' => 'francais@replyto.email',
					'subject' => 'Sujet',
					'layout' => array(
						'text' => 'Texte',
						'zip_file' => 'layout.zip',
					),
					'deliveries' => array(
						array('scheduled_datetime' => '2015-05-25T16:25'),
					),
				),
				array(
					'language' => 'en'
				),
			)
		));
		$this->assertNotNull($mailing->getRessource());
	}

	public function testTemporaryFileDeletionOnException() {
		$ressource = Mailing::getListRessource();
		$zipFiles = null;
		$exception = new \Exception('Testing temporary file deletion.');
		$this->client->shouldReceive('post')->with($ressource, array(
			'list' => $this->listId,
			'name' => 'Mailing',
			'variants' => array(array()),
		), Mockery::on(function($files) use (&$zipFiles) {
			$zipFiles = $files;
			return true;
		}))->andThrow($exception);
		try {
			Mailing::create($this->client, $this->listId, 'Mailing', array(
				'html' => '<html></html>',
			));
		} catch (\Exception $e) {
			$this->assertEquals($exception, $e);
		}
		$this->assertNotNull($zipFiles);
		foreach ($zipFiles as $zipFile) {
			$this->assertFalse(file_exists($zipFile->getFilename()));
		}
	}

	public function testCreateWithUnsupportedOption() {
		try {
			Mailing::create($this->client, $this->listId, 'Mailing', array('foo' => 'bar'));
			$this->fail('RuntimeException not thrown.');
		} catch (\RuntimeException $exception) {
			$this->assertEquals('The foo option is not supported.', $exception->getMessage());
		}
	}
}
