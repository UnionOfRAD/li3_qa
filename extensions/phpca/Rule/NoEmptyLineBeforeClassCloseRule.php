<?php

namespace spriebsch\PHPca\Rule;

/**
 * Ensures that there are no empty lines before the closing curly brace of a class.
 */
class NoEmptyLineBeforeClassCloseRule extends Rule
{
    /**
     * Performs the rule check.
     *
     * @returns null
     */
    protected function doCheck()
    {
        while ($this->file->seekTokenId(T_CLASS)) {
            $this->file->seekTokenId(T_OPEN_CURLY);
            $rewind = $this->file->current();
            $this->file->seekMatchingCurlyBrace($rewind);
            $this->file->prev();

            $token = $this->file->current();
            $lineEndings = $this->configuration->getLineEndings();

            if (addcslashes($token->getText(), "\0..\37") == $lineEndings . $lineEndings) {
                $this->addViolation('Empty line before class closing curly brace', $token);
            }

            $this->file->seekToken($rewind);
            $this->file->next();
        }
    }
}
?>