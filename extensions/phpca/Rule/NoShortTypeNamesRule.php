<?php

namespace spriebsch\PHPca\Rule;

/**
 * Ensures that no short type names are used for casting and documenting.
 */
class NoShortTypeNamesRule extends Rule
{
    /**
     * Performs the rule check.
     *
     * @returns null
     */
    protected function doCheck()
    {
        $enforce = array(
            T_BOOL_CAST => 'boolean',
            T_INT_CAST => 'integer'
        );
        foreach ($enforce as $id => $text) {
            while ($this->file->seekTokenId($id)) {
                $token = $this->file->current();

                if ($token->getText() !== $text) {
                    $this->addViolation('Short type name', $token);
                }
                $this->file->next();
            }
        }

        $blacklist = array(
            'int',
            'bool'
        );
        while ($this->file->seekTokenId(T_DOC_COMMENT)) {
            $token = $this->file->current();
            $lines = explode(
                stripcslashes($this->configuration->getLineEndings()),
                $token->getText()
            );
            foreach ($lines as $i => $line) {
                if (preg_match('/\b(int|bool)\b/', $line)) {
                    $this->addViolation('Short type name in docblock', $token, $token->getLine() + $i);
                }
            }

            $this->file->next();
        }
    }
}
?>