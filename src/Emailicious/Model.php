<?php

namespace Emailicious;

use Emailicious\Client;

abstract class Model {
	private $_client;
	private $_data;

	public static function getListRessource() {
		$args = func_get_args();
		array_unshift($args, static::LIST_RESSOURCE_FORMAT);
		return call_user_func_array('sprintf', $args);
	}

	public static function getInstanceRessource() {
		$args = func_get_args();
		array_unshift($args, static::INSTANCE_RESSOURCE_FORMAT);
		return call_user_func_array('sprintf', $args);
	}

	abstract public function getRessource();

	public function setClient(Client $client) {
		$this->_client = $client;
	}

	public function getClient() {
		return $this->_client;
	}

	public function setData($data) {
		$this->_data = $data;
	}

	public function getData() {
		return $this->_data;
	}

	public function __get($name) {
		return $this->_data[$name];
	}

	public function __set($name, $value) {
		$this->_data[$name] = $value;
	}

	public function update(array $only = null) {
		if ($only) {
			$data = array();
			foreach ($only as $key) {
				$data[$key] = $this->_data[$key];
			}
			$this->getClient()->patch($this->getRessource(), $data);
		} else {
			$this->getClient()->put($this->getRessource(), $this->_data);
		}
	}
}