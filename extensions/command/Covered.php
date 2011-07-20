<?php
/**
 * Lithium QA: a collection of commands to ensure code quality for development in the
 *             Lithium core and with Lithium applications.
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium_qa\extensions\command;

use lithium\core\Libraries;
use lithium\test\Dispatcher;
use lithium\test\Group;

/**
 * Checks all classes of certain library for a corresponding test
 * and retrieves test coverage percentage.
 */
class Covered extends \lithium\console\Command {

	/**
	 * Main method.
	 *
	 * @param string $path Absolute path to file or directory.
	 * @return boolean
	 */
	public function run() {
		$path = $this->request->action;

		if (!$path = realpath($path)) {
			$this->error('Not a valid path.');
			return false;
		}
		if (!$library = $this->_library($path)) {
			$this->error("No library registered for path `{$path}`.");
			return false;
		}

		$classes = Libraries::find($library, array(
		   'recursive' => true,
		   'exclude' => '/tests|resources|webroot|index$|^app\\\\config|^app\\\\views|Exception$/'
		));

		$tests = array();
		foreach (Group::all() as $test) {
			$class = preg_replace('/(tests\\\[a-z]+\\\|Test$)/', null, $test);
			$tests[$class] = $test;
		}

		foreach ($classes as $class) {
			$coverage = null;

			if ($hasTest = isset($tests[$class])) {
				$report = Dispatcher::run($tests[$class], array(
					'reporter' => 'console',
					'format' => 'txt',
					'filters' => array('Coverage')
				));
				$coverage = $report->results['filters']['lithium\test\filter\Coverage'];
				$coverage = isset($coverage[$class]) ? $coverage[$class]['percentage'] : null;
			}
			$this->out(sprintf('%10s | %7s | %s',
				$hasTest ? 'has test' : 'no test',
				is_numeric($coverage) ? sprintf('%.2f%%', $coverage) : 'n/a',
				$class
			));
		}
	}

	/**
	 * Finds a library for given path.
	 *
	 * @return string|void The library's name on success.
	 */
	protected function _library($path) {
		foreach (Libraries::get() as $name => $library) {
			if (strpos($path, $library['path']) === 0) {
				return $name;
			}
		}
	}
}

?>