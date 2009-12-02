<?php

namespace spriebsch\PHPca\Rule;

/**
 * Ensures that the closing tag is separated by an empty line.
 */
class EmptyLineBeforeCloseTagRule extends Rule
{
    /**
     * Performs the rule check.
     *
     * @returns null
     */
    protected function doCheck()
	{
		$this->file->seekTokenId(T_CLOSE_TAG);
		$this->file->prev();
		$token = $this->file->current();

		if (!$token) {
			return null;
		}
		$lineEndings = $this->configuration->getLineEndings();

		if (addcslashes($token->getText(), "\0..\37") != $lineEndings . $lineEndings) {
            $this->addViolation('File has no empty line before PHP close tag', $token);
		}
    }
}
?>