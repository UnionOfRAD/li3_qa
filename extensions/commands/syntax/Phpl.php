<?php
/**
 * Lithium Hooks: A collection of git hooks & scripts that can be used for development in the
 * Lithium core and with Lithium applications.
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace app\extensions\commands\syntax;

use lithium\util\String;

class Phpl extends \app\extensions\commands\syntax\Base {

	public function accepts($file) {
		return file_exists($file) && preg_match('/\.php$/', $file);
	}

	public function process($file) {
		$command = '{:php} -l {:file} 2> /dev/null';
		$replace = array(
			'php' => 'php',
			'file' => $file
		);
		exec(String::insert($command, $replace), $output, $return);

		if ($return == 0) {
			return null;
		}
		$filter = function($failure) {
			return !empty($failure) && $failure[0] == 'P';
		};
		$format = function($line) use ($file) {
			$regex = '/(?P<message>.*)\sin\s(?P<file>.*)\son\sline\s(?P<line>\d+)/';
			preg_match($regex, $line, $matches);
			return array(
				'file' => $file,
				'line' => isset($matches['line']) ? $matches['line'] : null,
				'column' => null,
				'message' => isset($matches['message']) ? $matches['message'] : null
			);
		};
		return array_map($format, array_filter($output, $filter));
	}
}

?>