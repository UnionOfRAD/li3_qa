<?php

namespace spriebsch\PHPca\Rule;

use spriebsch\PHPca\Token;

/**
 * Ensures there is no EOL marker at the EOF.
 */
class NoEolAtEofRule extends Rule
{
    /**
     * Performs the rule check.
     *
     * @returns null
     */
    protected function doCheck()
	{
		$content = $this->file->getSourceCode();
		$eol = stripcslashes($this->configuration->getLineEndings());
		$eof = substr($content, - strlen($eol));

		if ($eof == $eol) {
			$line = substr_count($content, $eol);
			$token = new Token(T_WHITESPACE, $eof, $line - 1);
			$this->addViolation('EOL at EOF', $token);
		}
	}
}
?>