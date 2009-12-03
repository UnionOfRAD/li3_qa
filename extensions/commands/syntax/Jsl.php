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

class Jsl extends \app\extensions\commands\syntax\Base {

	public function accepts($file) {
		return file_exists($file) && preg_match('/\.js$/', $file);
	}

	public function process($file) {
		$command = '{:jsl} -nologo -nosummary -nofilelisting -nocontext -process {:file}';
		$replace = array(
			'jsl' => 'jsl',
			'file' => $file
		);
		exec(String::insert($command, $replace), $output, $return);

		if ($return != 3) {
			return null;
		}
		$format = function($line) use ($file) {
			$regex = '/\((?P<line>\d+)\)\:\s(?P<message>.*)/';
			preg_match($regex, $line, $matches);
			return array(
				'file' => $file,
				'line' => isset($matches['line']) ? $matches['line'] : null,
				'column' => null,
				'message' => isset($matches['message']) ? $matches['message'] : null
			);
		};
		return array_map($format, $output);
	}
}

?>