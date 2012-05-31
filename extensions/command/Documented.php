<?php
/**
 * Lithium QA: a collection of commands to ensure code quality for development in the
 *             Lithium core and with Lithium applications.
 *
 * @copyright     Copyright 2012, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_qa\extensions\command;

use lithium\core\Libraries;

/**
 * Checks files for documentation coverage (via doc blocks).
 */
class Documented extends \lithium\console\Command {

	/**
	 * Current file's token set.
	 *
	 * @var array
	 **/
	protected $tokens = array();

	/**
	 * Current file path.
	 *
	 * @var string
	 **/
	protected $path = '';

	/**
	 * Errors for the current file.
	 *
	 * @var array
	 **/
	protected $errors = array();

	/**
	 * Filename skip regex.
	 *
	 * @var string
	 **/
	protected $ignore = '/template|tests/i';

	/**
	 * Method name skip regex.
	 *
	 * @var string
	 **/
	protected $methodIgnore = '/__construct|_init/i';

	/**
	 * Main method.
	 *
	 * @param string $path Absolute path to file or directory.
	 * @return boolean
	 */
	public function run() {
		if($files = $this->_getFiles($this->request->action)) {
			foreach($files as $file) {
				$this->_checkFile($file[0]);
			}
		}
		return true;
	}

	/**
	 * Checks a file for documentation.
	 *
	 * @param string $path Path to file to be inspected.
	 * @return void
	 **/
	protected function _checkFile($path) {
		if(preg_match($this->ignore, $path) == 1){
			return;
		}

		$this->errors = array();
		$this->tokens = token_get_all(file_get_contents($path));
		$this->path = $path;
		$this->_checkHeader();
		$this->_checkClassDocBlock();
		$this->_checkFunctionDocBlocks();
		$this->_checkVarDocBlocks();

		if(count($this->errors)) {
			$this->out($this->path);
			$this->out(implode("\n", $this->errors));
		}

		// foreach($this->tokens as &$token) {
		// 	$token[0] = token_name($token[0]);
		// }
		// die('<pre>' . print_r($this->tokens, true) . '</pre>');
	}

	/**
	 * Ensures each class variable is documented. The assumption is that if a
	 * variable is preceeded by a scope definition, it's a class variable.
	 *
	 * @return void
	 **/
	protected function _checkVarDocBlocks() {

	}

	/**
	 * Checks each function in a file to ensure documentation.
	 *
	 * @return void
	 **/
	protected function _checkFunctionDocBlocks() {
		for($i = 0; $i < count($this->tokens); $i++) {
			if($this->tokens[$i][0] == T_FUNCTION) {
				if( // likely a closure.
				   !is_array($this->tokens[$i + 2]) ||
				   substr($this->tokens[$i + 2][1], 0, 1) == '$' ||
				   strlen($this->tokens[$i + 2][1]) < 1) {
					continue;
				} else {
					$functionName = $this->tokens[$i + 2][1];
				}
				if(preg_match($this->methodIgnore, $functionName) == 1) {
					continue;
				}
				if($this->tokens[$i - 2][0] == T_STATIC) {
					$staticMod = 2;
					$docAt = 6;
				} else {
					$staticMod = 0;
					$docAt = 4;
				}
				if(!in_array($this->tokens[$i - (2 + $staticMod)][0], array(T_PUBLIC, T_PRIVATE, T_PROTECTED))) {
					$this->_error("$functionName() has no scope operator.");
					$docAt = (2 + $staticMod);
				}
				if($this->tokens[$i - $docAt][0] == T_ABSTRACT) {
					$docAt -= 2;
				}
				if($this->tokens[$i - $docAt][0] != T_DOC_COMMENT) {
					$line = str_pad("line {$this->tokens[$i][2]}:", 10);
					$this->_error("$line Function $functionName() has no documentation.");
				}
			}
		}
	}

	/**
	 * Makes sure main class is documented.
	 *
	 * @return void
	 **/
	protected function _checkClassDocBlock() {
		for($i = 0; $i < count($this->tokens); $i++) {
			if($this->tokens[$i][0] == T_CLASS) {
				$abMod = 0;
				if($this->tokens[$i - 2][0] == T_ABSTRACT) {
					$abMod = 2;
				}
				if($this->tokens[$i - (2 + $abMod)][0] != T_DOC_COMMENT) {
					$line = str_pad("line {$this->tokens[$i][2]}:", 10);
					$this->_error("$line Main class has no associated doc block.");
				}
			}
		}
	}

	/**
	 * Makes sure the file includes the UoR header.
	 *
	 * @return void
	 **/
	protected function _checkHeader() {
		if(!isset($this->tokens[1][0]) ||
		   !isset($this->tokens[1][1]) ||
		   $this->tokens[1][0] != T_DOC_COMMENT ||
		   strstr($this->tokens[1][1], 'Lithium: the most rad php framework') === false
		) {
			$line = str_pad("line {$this->tokens[1][2]}:", 10);
			$this->_error("File does not contain Lithium header.");
		}
	}

	/**
	 * Outputs an error to the console based on the current file being processed.
	 *
	 * @param string $message Message to output.
	 * @return void
	 **/
	protected function _error($msg) {
		$this->errors[] = "\t{:red}$msg{:end}";
	}

	/**
	 * Gets the PHP files found under the supplied path.
	 *
	 * @param string $path Path to PHP files.
	 * @return RegexIterator
	 **/
	protected function _getFiles($path) {
		if (!$path = realpath($path)) {
			$this->error('Not a valid path.');
			return false;
		}
		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
		return new \RegexIterator($iterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);
	}
}