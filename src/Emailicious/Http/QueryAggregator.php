<?php

namespace Emailicious\Http;

use Guzzle\Http\QueryAggregator\DuplicateAggregator;
use Guzzle\Http\QueryString;

class QueryAggregator extends DuplicateAggregator {
	const ARRAY_KEY_FORMAT = "%s[%d]";
	const ASSOC_KEY_FORMAT = "%s.%s";

	private static function _containsArray(array $array) {
		foreach ($array as $value) {
			if (is_array($value)) {
				return true;
			}
		}
		return false;
	}

	private static function _isAssocArray(array $array) {
		return array_diff_key($array, array_keys(array_keys($array)));
	}

	public function aggregate($key, $value, QueryString $query) {
		$containsArray = self::_containsArray($value);
		$isAssocArray = self::_isAssocArray($value);
		if ($containsArray || $isAssocArray) {
			$nestedKeyFormat = $isAssocArray ? self::ASSOC_KEY_FORMAT : self::ARRAY_KEY_FORMAT;
			$return = array();
			foreach ($value as $k => $v) {
				$nestedKey = sprintf($nestedKeyFormat, $key, $k);
				if (is_array($v)) {
					$return = array_merge($return, self::aggregate($nestedKey, $v, $query));
				} else $return[$query->encodeValue($nestedKey)] = $query->encodeValue($v);
			}
			return $return;
		} else return parent::aggregate($key, $value, $query);
	}
}