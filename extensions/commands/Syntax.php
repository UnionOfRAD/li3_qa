<?php
/**
 * Lithium Hooks: A collection of git hooks & scripts that can be used for development in the
 * Lithium core and with Lithium applications.
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace app\extensions\commands;

use lithium\core\Libraries;
use lithium\util\Inflector;
use \RecursiveIteratorIterator;
use \RecursiveDirectoryIterator;

class Syntax extends \lithium\console\Command {

	public $checks;

	public $project;

	public $exclude = '\.';

	public function run($file = null) {
		if (!$this->checks) {
			$this->help();
			return 1;
		}
		$this->checks = explode(',' , $this->checks);

		if (!$this->project) {
			$this->project = $this->request->env['working'];
		}

		if ($file[0] !== '/') {
			$file = $this->project . '/' . $file;
		}
		if (is_file($file)) {
			return $this->_checkFile($file) ? 0 : 1;
		}
		return $this->_checkDirectory($file) ? 0 : 1;

	}

	protected function _checkFile($file) {
		$message = 'Checking `' . str_replace($this->project . '/', null, $file) .'`. ';
		$this->out($message, false);
		$failures = array();

		foreach ($this->checks as $check) {
			$class = Libraries::locate('commands.syntax', Inflector::camelize($check));
			$check = new $class(array('request' => $this->request));

			if (!$check->accepts($file)) {
				$this->out("Skipped. ", false);
			} elseif ($failures = $check->process($file)) {
				$this->out("Failed. ", false);
			} else {
				$this->out("Passed. ", false);
			}
		}
		$this->nl();

		if ($failures) {
			$this->error($failures);
			$this->nl();
			return false;
		}
		return true;
	}

	protected function _checkDirectory($directory) {
		$base = new RecursiveDirectoryIterator($directory);
		$iterator = new RecursiveIteratorIterator($base);
		$errors = false;

		foreach ($iterator as $item) {
			$basename = $item->getBasename();
			$file = $item->getPathname();

			if (preg_match('/\/' . $this->exclude . '/', $file) || $basename == 'empty') {
				continue;
			}
			$errors = !$this->_checkFile($file) || $errors;
		}
		return !$errors;
	}

	public function checks() {
		$this->header('Available Checks:');
		$classes = array_unique(Libraries::locate('commands.syntax', null, array(
			'recursive' => false
		)));
		foreach ($classes as $command) {
            $command = explode('\\', $command);
            $this->out(' - ' . Inflector::underscore(array_pop($command)));
		}
	}

	public function help() {
		$message  = 'Usage: li3 syntax [--project=PROJECT] [--exclude=REGEX] ';
		$message .= '--checks=<CHECK>[,CHECK] [FILE]';
		$this->out($message);
	}
}

?>