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

class Phpl extends \app\extensions\commands\verify\Base {

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

		if ($return != 0) {
			return array_filter($output, function($error) {
				return !empty($error) && $error[0] == 'P';
			});
		}
	}
}

?>