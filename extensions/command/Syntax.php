<?php
/**
 * Lithium Hooks: A collection of git hooks & scripts that can be used for development in the
 * Lithium core and with Lithium applications.
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace app\extensions\command;

use lithium\util\String;
use spriebsch\PHPca\Application;
use spriebsch\PHPca\Configuration;
use spriebsch\PHPca\Result;

/**
 * Runs syntax checks against files.
 */
class Syntax extends \lithium\console\Command implements \spriebsch\PHPca\ProgressPrinterInterface {

	/**
	 * Enable output of metrics.
	 *
	 * @var boolean
	 */
	public $metrics;

	/**
	 * Enable blaming of each failure.
	 *
	 * @var boolean
	 */
	public $blame;

	protected $_project;

	protected $_vcs;

	/**
	 * Main method.
	 *
	 * @param string $path Absolute path to file or directory.
	 */
	public function run($path) {
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

		$file = LITHIUM_APP_PATH . '/config/phpca_lithium_standard.ini';

		$config = new Configuration(getcwd());
		$config->setStandard(parse_ini_file($file, true));
		$config->setConfiguration(array());

		$php = PHP_BINDIR . '/' . (substr(PHP_OS, 0, 3) == 'WIN' ? 'php.exe' : 'php');
		$result = $app->run($php, $path, $config);

		if ($this->metrics) {
			$this->_metrics($result);
		}
		return !$result->hasErrors();
	}

	public function showProgress($file, Result $result, Application $application) {
		$message = 'Checking syntax of `' . str_replace($this->_project . '/', null, $file) .'`. ';
		$this->out($message, false);

		if ($result->wasSkipped($file)) {
			$this->out('Skipped.');
		} elseif ($result->hasLintError($file)) {
			$this->out('Error.');
			$error = $result->getLintError($file);
			$this->out(sprintf(
				'%1$4u| %2$3u| %3$20s| %4$s',
				$error->getLine() ?: '??',
				$error->getColumn() ?: '??',
				$this->_blame($error) ?: '??',
				$error->getMessage() ?: '??'
			));

		} elseif ($result->hasRuleError($file)) {
			$this->out('Error.');

		} elseif ($result->hasViolations($file)) {
			$this->out('Failed.');

			foreach ($result->getViolations($file) as $violation) {
				$this->out(sprintf(
					'%1$4u| %2$3u| %3$20s| %4$s',
					$violation->getLine() ?: '??',
					$violation->getColumn() ?: '??',
					$this->_blame($violation) ?: '??',
					$violation->getMessage() ?: '??'
				));
			}
		} else {
			$this->out('Passed.');
		}
	}

	protected function _project($path) {
		while ($path && !is_dir($path . '/.git') && !is_dir($path . '/config/bootstrap.php')) {
			$path = ($parent = dirname($path)) != $path ? $parent : false;
		}
		return $path;
	}

	protected function _metrics($result) {
		$this->nl();
		$this->header('Metrics');
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