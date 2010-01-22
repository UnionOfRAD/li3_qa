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
     * We need to check 2 tokens previous to the closing tag otherwise a comment followed by
     * and empty line followed by the closing tag is not properly recognized.
     *
     * @returns null
     */
    protected function doCheck()
    {
        $this->file->seekTokenId(T_CLOSE_TAG);

        $this->file->prev();
        $b = $this->file->current();

        $this->file->prev();
        $a = $this->file->current();

        if (!$a || !$b) {
            return;
        }

        $string = $a->getText() . $b->getText();
        $lineEndings = $this->configuration->getLineEndings();

        if (!preg_match("/{$lineEndings}{$lineEndings}$/", $string)) {
            $this->addViolation('File has no empty line before PHP close tag', $b);
        }
    }
}
?>