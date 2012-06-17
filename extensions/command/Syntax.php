<?php
/**
 * Lithium QA: a collection of commands to ensure code quality for development in the
 *             Lithium core and with Lithium applications.
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_qa\extensions\command;

use Exception;
use lithium\core\Libraries;
use lithium\util\String;
use spriebsch\PHPca\Result;
use spriebsch\PHPca\Application;
use spriebsch\PHPca\Configuration;

/**
 * Runs syntax checks against files. This will validate
 * against the Lithium coding, documentation and testing standards.
 *
 * @link https://github.com/UnionOfRAD/lithium/wiki/Spec%3A-Coding
 * @link https://github.com/UnionOfRAD/lithium/wiki/Spec%3A-Documenting
 * @link https://github.com/UnionOfRAD/lithium/wiki/Spec%3A-Testing
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
	 * Enable plain to prevent any headers or similar decoration being output.
	 * Good for command calls embedded into other scripts.
	 *
	 * @var boolean
	 */
	public $plain = false;

	/**
	 * Absolute path to PHP executable (optional for most environments).
	 *
	 * @var string
	 */
	public $php;

	/**
	 * Alternative syntax standards to use.
	 *
	 * @var string
	 */
	public $standard;

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

		$file = $this->_standard();

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
		if (!$this->plain) {
			$this->header('Syntax');
			$this->out(null, 1);
		}
		$begin = microtime(true);

		try {
			$result = $app->run($this->php, $path, $config);
		} catch (Exception $e) {
			$this->out($message = $e->getMessage());
			return $message == 'No PHP files to analyze';
		}

		if ($this->metrics) {
			$this->_metrics($result, microtime(true) - $begin);
		}
		return !$result->hasErrors();
	}

	protected function _standard() {
		$default = Libraries::get('phpca', 'path') . '/Standard/lithium.ini';
		$config = Libraries::get('li3_qa') + array('standard' => false);
		$notFound = ' Could not find `{:standard}`.';
		$nextBest = ' Using `{:standard}` instead.';
		$msg = '';

		foreach (array($this->standard, $config['standard']) as $standard) {
			if (!$standard) {
				continue;
			}
			if (file_exists($standard)) {
				if ($msg) {
					$msg .=  String::insert($nextBest, compact('standard'));
					$this->error(trim($message));
				}
				return $standard;
			}
			$msg .= String::insert($notFound, compact('standard'));
		}
		if ($msg) {
			$standard = 'Lithium defaults';
			$msg .=  String::insert($nextBest, compact('standard'));
			$this->error(trim($message));
		}
		return $default;
	}

	public function showProgress($file, Result $result, Application $application) {
		$message = str_replace($this->_project . '/', null, $file);
		$self = $this;

		$format = function ($result) use ($self) {
			return sprintf(
				$self->blame ? '%1$4s| %2$3s| %3$20s| %4$s' : '%1$4s| %2$3s| %4$s',
				$result->getLine() ?: '??',
				$result->getColumn() ?: '??',
				$self->invokeMethod('_blame', array($result)) ?: '??',
				$result->getMessage() ?: '??'
			);
		};

		if ($result->wasSkipped($file)) {
			$this->out("[{:cyan}skip{:end}     ] {$message}");
		} elseif ($result->hasLintError($file)) {
			$this->out("[{:purple}exception{:end}] {$message}");
			$this->out($format($result->getLintError($file)));
		} elseif ($result->hasRuleError($file)) {
			$this->out("[{:purple}exception{:end}] {$message}");

			foreach ($result->getRuleErrors($file) as $error) {
				$this->out($format($error));
			}
		} elseif ($result->hasViolations($file)) {
			$this->out("[{:red}fail{:end}     ] {$message}");

			foreach ($result->getViolations($file) as $violation) {
				$this->out($format($violation));
			}
		} else {
			$this->out("[{:green}pass{:end}     ] {$message}");
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