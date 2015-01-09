<?php

namespace Ameos\AmeosKickstart;

class Utility {

	/**
	 * camel case
	 * @param string $string
	 * @return string
     */
	public static function camelCase($string) {
		$output = '';
		foreach( explode('_', $string) as $part) {
			$output.= ucfirst($part);
		}
		return $output;
	}
}
