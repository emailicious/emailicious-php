<?php

namespace Emailicious\Mailing;

use Emailicious\Model;
use Guzzle\Http\Message\PostFile;

class Mailing extends Model {
	const LIST_RESSOURCE_FORMAT = 'mailings';
	const INSTANCE_RESSOURCE_FORMAT = 'mailings/%d';

	private static $_createOptions = array(
		'campaign', 'segments', 'variants'
	);
	private static $_createVariantOptions = array(
		'from_email', 'from_name', 'replyto_email', 'subject', 'html', 'text', 'deliveries'
	);
	private static $_createDeliveryOptions = array(
		'scheduled_datetime',
	);

	private static function _buildVariantLayout(&$files, array &$variants) {
		$createdFiles = array();
		foreach ($variants as $index => &$variant) {
			if (isset($variant['layout']['zip_file'])) {
				$files["variants[$index]layout.zip_file"] = $variant['layout']['zip_file'];
				unset($variant['layout']['zip_file']);
			}
			if (isset($variant['html'])) {
				$zipFileKey = "variants[$index]layout.zip_file";
				if (!isset($files[$zipFileKey])) {
					$html = $variant['html'];
					$archive = new \ZipArchive;
					$archiveName = tempnam(sys_get_temp_dir(), 'html-email-');
					$archive->open($archiveName, \ZipArchive::CREATE);
					$archive->addFromString('email.html', $html);
					$archive->close();
					$createdFiles[] = $archiveName;
					$curlFile = new PostFile($zipFileKey, $archiveName, 'application/zip', 'layout.zip');
					$files[$zipFileKey] = $curlFile;
				}
				unset($variant['html']);
			}
			if (isset($variant['text'])) {
				if (!isset($variant['layout']['text'])) {
					$variant['layout'] = array('text' => $variant['text']);
				}
				unset($variant['text']);
			}
		}
		return $createdFiles;
	}

	public static function create($client, $listId, $name, array $options) {
		$data = array(
			'list' => $listId,
			'name' => $name,
		);
		$files = null;
		$createdFiles = array();

		foreach (self::$_createOptions as $option) {
			if (isset($options[$option])) {
				$data[$option] = $options[$option];
				unset($options[$option]);
			}
		}
		$variant_options = array();
		foreach (self::$_createVariantOptions as $option) {
			if (isset($options[$option])) {
				$variant_options[$option] = $options[$option];
				unset($options[$option]);
			}
		}
		$deliveries_options = array();
		foreach (self::$_createDeliveryOptions as $option) {
			if (isset($options[$option])) {
				$deliveries_options[$option] = $options[$option];
				unset($options[$option]);
			}
		}
		foreach (array_keys($options) as $option) {
			throw new \RuntimeException("The $option option is not supported.");
		}

		if (isset($variant_options['deliveries'])) {
			$deliveries =& $variant_options['deliveries'];
			foreach ($deliveries as &$delivery) {
				foreach ($deliveries_options as $key => $value) {
					if (!isset($delivery[$key])) $delivery[$key] = $value;
				}
			}
		} else if (count($deliveries_options)) {
			$variant_options['deliveries'] = array($deliveries_options);
		}

		if (isset($data['variants'])) {
			$variants =& $data['variants'];
			foreach ($variants as &$variant) {
				foreach ($variant_options as $key => $value) {
					if (!isset($variant[$key])) $variant[$key] = $value;
				}
			}
			$createdFiles =  self::_buildVariantLayout($files, $variants);
		} else if (count($variant_options)) {
			$variants = array($variant_options);
			$createdFiles = self::_buildVariantLayout($files, $variants);
			$data['variants'] = $variants;
		}

		# Odd construct to workaround missing finally statement in PHP < 5.5
		$exception = null;
		try {
			$data = $client->post(self::getListRessource(), $data, $files);
		} catch (\Exception $e) {
			$exception = $e;
		}
		// Finally rid of all the temporary created files.
		foreach ($createdFiles as $filename) {
			unlink($filename);
		}
		if ($exception) throw $exception;
		return new static($client, $data);
	}

	/*
	 * TODO: Change visibility when support for PHP 5.3 is dropped.
	 */
	public function __construct($client, array $data) {
		$this->setClient($client);
		$this->setData($data);
	}

	public function getRessource() {
		return self::getInstanceRessource($this->id);
	}
}
