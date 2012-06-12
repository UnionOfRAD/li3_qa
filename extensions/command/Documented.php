<?php
/**
 * Lithium QA: a collection of commands to ensure code quality for development in the
 *             Lithium core and with Lithium applications.
 *
 * @copyright     Copyright 2012, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_qa\extensions\command;

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
	protected $ignore = '/template|tests|views/i';

	/**
	 * Main method.
	 *
	 * @param string $path Absolute path to file or directory.
	 * @return boolean
	 */
	public function run($path = '.') {
		if ($files = $this->_getFiles($path)) {
			foreach ($files as $file) {
				$this->checkFile($file[0]);
			}
		}
		$this->out("\n{:green}Check complete.{:end}");
		return true;
	}

	/**
	 * Checks a file for documentation.
	 *
	 * @param string $path Path to file to be inspected.
	 * @return void
	 **/
	public function checkFile($path) {
		if (preg_match($this->ignore, $path) == 1){
			return true;
		}

		$this->errors = array();
		$this->tokens = token_get_all(file_get_contents($path));
		$this->path = $path;
		$this->_checkHeader();
		$this->_checkClassDocBlock();
		$this->_checkDocBlocks();

		if (count($this->errors)) {
			$this->out("\n" . $this->path . "\n" . implode("\n", $this->errors));
			return false;
		}
		return true;
	}

	protected function debug($tokens = null) {
		if ($tokens == null) {
			$tokens = $this->tokens;
		}

		foreach ($tokens as &$token) {
			if (is_array($token)) {
				$token[0] = token_name($token[0]);
			}
		}
		echo print_r($tokens, true);
	}

	/**
	 * Ensures each class variable or method is documented. The assumption is that if a
	 * member is preceeded by a scope definition, it needs docs.
	 *
	 * @return void
	 **/
	protected function _checkDocBlocks() {
		for ($i = 0; $i < count($this->tokens); $i++) {
			if ($this->tokens[$i][0] == T_VARIABLE || $this->tokens[$i][0] == T_FUNCTION) {
				// Get the previous tokens.
				// Max is 8, allows for abstract, scope, static markers.
				$leadingTokens = array();
				$leadingTokenTypes = array();
				for ($j = 1; $j < 9; $j++) {
						if (!isset($this->tokens[$i - $j])) {
							break;
						}
						$leadingTokens[$i - $j] = $this->tokens[$i - $j];
						$leadingTokenTypes[$i - $j] = $this->tokens[$i - $j][0];
						// Don't parse farther past if you run into another var/function.
						if (in_array($this->tokens[$i - $j][0], array(T_VARIABLE, T_FUNCTION))) {
							break;
						}
				}
				// Check for a scope operator. Lack of one means we've hit a
				// closure or a non-class var that can be ignored.
				// Hit means this needs documentation.
				$scoped = in_array(T_PUBLIC, $leadingTokenTypes) ||
				          in_array(T_PROTECTED, $leadingTokenTypes) ||
				          in_array(T_PRIVATE, $leadingTokenTypes);
				if ($scoped && !in_array(T_DOC_COMMENT, $leadingTokenTypes)) {
					$this->_error($i);
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
		for ($i = 0; $i < count($this->tokens); $i++) {
			if ($this->tokens[$i][0] == T_CLASS) {
				$abMod = 0;
				if ($this->tokens[$i - 2][0] == T_ABSTRACT) {
					$abMod = 2;
				}
				if ($this->tokens[$i - (2 + $abMod)][0] != T_DOC_COMMENT) {
					$line = str_pad("line {$this->tokens[$i][2]}:", 10);
					$this->_error($i);
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
		if (!isset($this->tokens[1]) || !is_array($this->tokens[1])) {
			$this->_error(1);
		}
		$containsHeader = strstr($this->tokens[1][1], 'the most rad php framework') === false;
		if ($this->tokens[1][0] != T_DOC_COMMENT || $containsHeader) {
			$this->_error(1);
		}
	}

	/**
	 * Outputs an error to the console based on the current file being processed.
	 *
	 * @param integer $tokenIndex Token related to error.
	 * @return void
	 **/
	protected function _error($tokenIndex) {
		if ($tokenIndex == 1) {
			$error = "line: 1\tNo file header found.";
			$this->errors[] = "\t{:red}$error{:end}";
			return;
		}

		$error = '';
		switch ($this->tokens[$tokenIndex][0]) {
			case T_VARIABLE:
				$varname = $this->tokens[$tokenIndex][1];
				$error = "line: {$this->tokens[$tokenIndex][2]}\t$varname not documented.";
				break;
			case T_FUNCTION:
				$funcname = $this->tokens[$tokenIndex + 2][1];
				$error = "line: {$this->tokens[$tokenIndex][2]}\t$funcname() not documented.";
				break;
			case T_CLASS:
				$classname = $this->tokens[$tokenIndex + 2][1];
				$error = "line: {$this->tokens[$tokenIndex][2]}\tClass $classname not documented.";
				break;
		}
		$this->errors[] = "\t{:red}$error{:end}";
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
		if (is_file($path)) {
			return array(array($path));
		}
		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
		return new \RegexIterator($iterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);
	}
}

?>