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

class Phpca extends \app\extensions\commands\syntax\Base {

	public function accepts($file) {
		return file_exists($file) && preg_match('/\.php$/', $file);
	}

	public function process($file) {
		$plugin = dirname(dirname(dirname(__DIR__)));
		$command = '{:php} {:phpca} -p {:php} --standard {:standard} {:file}';
		$replace = array(
			'php' => trim(shell_exec('which php')),
			'phpca' => $plugin . '/libraries/phpca/src/phpca.php',
			'standard' => $plugin . '/config/phpca_lithium_standard.ini',
			'file' => $file
		);
		exec(String::insert($command, $replace), $output, $return);

		if ($return == 0) {
			return null;
		}
		$format = function($line) use ($file) {
			$regex = '/(?P<line>\d+)\|.*(?P<column>\d+)\|\s+(?P<message>.*)/';
			preg_match($regex, $line, $matches);

			return array(
				'file' => $file,
				'line' => isset($matches['line']) ? $matches['line'] : null,
				'column' => isset($matches['column']) ? $matches['column'] : null,
				'message' => isset($matches['message']) ? $matches['message'] : null
			);
		};
		return array_map($format, array_filter(array_slice($output, 9, -3)));
	}
}

?>