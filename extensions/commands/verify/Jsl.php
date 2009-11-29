<?php
/**
 * Lithium Hooks: A collection of git hooks & scripts that can be used for development in the
 * Lithium core and with Lithium applications.
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace app\extensions\commands\verify;

use lithium\util\String;

class Jsl extends \app\extensions\commands\verify\Base {

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
		return array_map(function($line) {
			$line = preg_match('/\((?P<line>\d+)\)\:\s(?P<message>.*)/', $line, $matches);
			return "{$matches['message']} on line {$matches['line']}";
		}, $output);
	}
}

?>