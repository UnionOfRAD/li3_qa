<?php

namespace spriebsch\PHPca\Rule;

use spriebsch\PHPca\Token;

/**
 * Ensures that no line is longer than a certain amount of charcters.
 */
class MaximumLineLengthRule extends Rule
{
    /**
     * Performs the rule check.
     *
     * @returns null
     */
    protected function doCheck()
    {
        $lines = explode(
            stripcslashes($this->configuration->getLineEndings()),
            $this->file->getSourceCode()
        );
        foreach ($lines as $i => $line) {
            if (strlen($line) > $this->settings['line_length']) {
                $this->addViolation(
                    'Maximum line length exceeded',
                    null, $i +1, $this->settings['line_length'] + 1
                );
            }
        }
    }
}
?>