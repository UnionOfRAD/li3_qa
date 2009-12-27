<?php

namespace spriebsch\PHPca\Rule;

/**
 * Ensures that there is one space before and after an operator.
 */
class OperatorsSurroundedBySpacesRule extends Rule
{
    /**
     * Performs the rule check.
     *
     * Currently the binary ampersand operator and increment and decrement operators
     * are excluded from the check as the ampersand operator would require a lot more complex
     * logic and the in/decrement operatos are special cases which should not be spaced in general.
     *
     * @returns null
     */
    protected function doCheck()
    {
        $operators = array(
            /* Builtins */
            T_AND_EQUAL, T_BOOLEAN_AND, T_BOOLEAN_OR, T_CONCAT_EQUAL, T_DIV_EQUAL, T_IS_EQUAL,
            T_IS_GREATER_OR_EQUAL, T_IS_IDENTICAL, T_IS_NOT_EQUAL, T_IS_NOT_IDENTICAL,
            T_IS_SMALLER_OR_EQUAL, T_LOGICAL_AND, T_LOGICAL_XOR, T_MINUS_EQUAL, T_MOD_EQUAL,
            T_MUL_EQUAL, T_OR_EQUAL, T_PLUS_EQUAL, T_SL, T_SL_EQUAL, T_SR, T_SR_EQUAL,
            T_XOR_EQUAL, /* T_INC, T_DEC */

            /* Tokens defined by PHPca */
            T_DOT, T_EQUAL, T_LT, T_GT, T_PLUS, T_MINUS, T_MULT, T_DIV, T_PERCENT, T_PIPE,
            T_CARET, T_TILDE, /* T_AMPERSAND */
        );

        $beforeOk = array(T_WHITESPACE);
        $afterOk = array(T_WHITESPACE, T_EQUAL, T_AMPERSAND);

        $before = $current = $after = null;

        while ($this->file->valid()) {
            $this->file->next();

            $before = $current;
            $current = $after;
            $after = $this->file->current();

            if (!$current || !in_array($current->getId(), $operators)) {
               continue;
            }
            if ($before && !in_array($before->getId(), $beforeOk)) {
                $this->addViolation('No space before operator', $before);
            } elseif ($after && !in_array($after->getId(), $afterOk)) {
                $this->addViolation('No space after operator', $after);
            }
        }
    }
}
?>