<?php
/**
 * Lithium QA: a collection of commands to ensure code quality for development in the
 *             Lithium core and with Lithium applications.
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_qa\extensions\command;

use lithium\core\Libraries;
use lithium\util\String;
use spriebsch\PHPca\Application;
use spriebsch\PHPca\Configuration;
use spriebsch\PHPca\Result;

/**
 * Runs syntax checks against files. This will validate
 * against the Lithium coding, documentation and testing standards.
 *
 * @link http://dev.lithify.me/lithium/wiki/standards/coding
 * @link http://dev.lithify.me/lithium/wiki/standards/documenting
 * @link http://dev.lithify.me/lithium/wiki/standards/testing
 */
class Syntax extends \lithium\console\Command implements \spriebsch\PHPca\ProgressPrinterInterface {

	/**
	 * Enable output of metrics.
	 *
	 * @var boolean
	 */
	public $metrics = false;

	/**
	 * Enable blaming of each failure.
	 *
	 * @var boolean
	 */
	public $blame = false;

	/**
	 * Absolute path to PHP executable (optional for most environments).
	 *
	 * @var string
	 */
	public $php;

	protected $_project;

	protected $_vcs;

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
		if (!$this->_project = $this->_project($path)) {
			$this->error('Not a valid project.');
			return false;
		}
		if (is_dir($this->_project . '/.git')) {
			$this->_vcs = 'git';
		}

		$app = new Application(getcwd());
		$app->registerProgressPrinter($this);

		$file = Libraries::get('phpca', 'path') . '/Standard/lithium.ini';

		$config = new Configuration(getcwd());
		$config->setStandard(parse_ini_file($file, true));
		$config->setConfiguration(array());

		if (!isset($this->php)) {
			$this->php = PHP_BINDIR . '/' . (substr(PHP_OS, 0, 3) == 'WIN' ? 'php.exe' : 'php');
		}
		if (!file_exists($this->php)) {
			$message  = 'You must specify a valid absolute path to a PHP executable. ';
			$message .= 'Try using `li3 syntax --php=PATH ...`.';
			$this->error($message);
			return false;
		}

		$begin = microtime(true);

		try {
			$result = $app->run($this->php, $path, $config);
		} catch (\Exception $e) {
			$this->out($message = $e->getMessage());
			return $message == 'No PHP files to analyze';
		}

		if ($this->metrics) {
			$this->_metrics($result, microtime(true) - $begin);
		}
		return !$result->hasErrors();
	}

	public function showProgress($file, Result $result, Application $application) {
		$message = 'syntax check of `' . str_replace($this->_project . '/', null, $file) . '`';

		if ($result->wasSkipped($file)) {
			$this->out("[Skipped] {$message}");
		} elseif ($result->hasLintError($file)) {
			$this->out("[Error  ] {$message}");
			$error = $result->getLintError($file);
			$this->out(sprintf(
				$this->blame ? '%1$4u| %2$3u| %3$20s| %4$s' : '%1$4u| %2$3u| %4$s',
				$error->getLine() ?: '??',
				$error->getColumn() ?: '??',
				$this->_blame($error) ?: '??',
				$error->getMessage() ?: '??'
			));

		} elseif ($result->hasRuleError($file)) {
			$this->out("[Error  ] {$message}");

		} elseif ($result->hasViolations($file)) {
			$this->out("[Failed ] {$message}");

			foreach ($result->getViolations($file) as $violation) {
				$this->out(sprintf(
					$this->blame ? '%1$4u| %2$3u| %3$20s| %4$s' : '%1$4u| %2$3u| %4$s',
					$violation->getLine() ?: '??',
					$violation->getColumn() ?: '??',
					$this->_blame($violation) ?: '??',
					$violation->getMessage() ?: '??'
				));
			}
		} else {
			$this->out("[Passed ] {$message}");
		}
	}

	protected function _project($path) {
		while ($path && !is_dir("{$path}/.git") && !is_dir("{$path}/config")) {
			$path = ($parent = dirname($path)) != $path ? $parent : false;
		}
		return $path;
	}

	protected function _metrics($result, $took) {
		$this->nl();
		$this->header('Metrics');
		$this->nl();
		$this->out(sprintf("Took: %.2ds", $took));
		$this->nl();
		$this->out('Files: ' . $result->getNumberOfFiles());
		$this->out('Skipped: ' . $result->getNumberOfSkippedFiles());
		$this->nl();
		$this->out('Lint errors: ' . $result->getNumberOfLintErrors());
		$this->out('Rule errors: ' . $result->getNumberOfRuleErrors());
		$this->out('Violations: ' . $result->getNumberOfViolations());
		$this->nl();
	}

	protected function _blame($error) {
		if (!$this->blame || !$this->_vcs == 'git') {
			return null;
		}
		$backup = getcwd();
		chdir($this->_project);
		$line = $error->getLine();
		$file = $error->getFilename();
		$lines = count(file($file));

		$command = 'git blame -L{:start},{:end} --porcelain {:file}';
		$replace = array(
			'start' => $line,
			'end' => $lines == $line ? $line : $line + 1,
			'file' => $file
		);
		exec(String::insert($command, $replace), $output, $return);
		chdir($backup);

		if ($return == 0) {
			list(, $author) = explode(' ', $output[1], 2);
			return $author;
		}
	}
}

?>
