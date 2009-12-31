<?php

namespace spriebsch\PHPca\Rule;

/**
 * Ensures that there is one space before and after an operator.
 *
 * However there are a few exceptions to the spacing rule◌:
 *  1. Increment and decrement operators must be directly followed by or following the variable.
 *  2. The exclamation mark must be directly followed by the variable.
 *  3. Colons appearing as part of a case condition must have no spaces surrounding them.
 *  4. Labels must have no spaces surrounding them.
 *  5. Negative literal integers or floats must have the minus sign directly attached.
 *  6. Minus signs involved in negations of i.e. variables can be spaced or directly attached.
  */
class OperatorsSurroundedBySpacesRule extends Rule
{
    protected $composed = array(
        T_AND_EQUAL, T_BOOLEAN_AND, T_BOOLEAN_OR, T_CONCAT_EQUAL, T_DIV_EQUAL, T_IS_EQUAL,
        T_IS_GREATER_OR_EQUAL, T_IS_IDENTICAL, T_IS_NOT_EQUAL, T_IS_NOT_IDENTICAL,
        T_IS_SMALLER_OR_EQUAL, T_LOGICAL_AND, T_LOGICAL_XOR, T_MINUS_EQUAL, T_MOD_EQUAL,
        T_MUL_EQUAL, T_OR_EQUAL, T_PLUS_EQUAL,  T_SL_EQUAL, T_SR_EQUAL,
        T_XOR_EQUAL, T_INC, T_DEC
    );

    protected $single = array(
        T_SL, T_SR,
        T_DOT, T_EQUAL, T_LT, T_GT, T_PLUS, T_MINUS, T_MULT, T_DIV, T_PERCENT, T_PIPE,
        T_CARET, T_TILDE, T_AMPERSAND, T_QUESTIONMARK, T_COLON, T_EXCLAMATIONMARK
    );

    /**
     * Performs the rule check.
     *
     * @returns null
     */
    protected function doCheck()
    {
        $operators = array_merge($this->single, $this->composed);
        $before = $current = $after = null;

        while ($this->file->valid()) {
            $this->file->next();

            $before = $current;
            $current = $after;
            $after = $this->file->current();

            if (!$before || !$current || !$after) {
                continue;
            }

            if ($this->isException($before, $current, $after)) {
                $this->file->next();
                $this->file->next();
                $before = $current = $after = null;
                continue;
            }

            if (!in_array($current->getId(), $operators)) {
               continue;
            }
            if ($before && $before->getId() !== T_WHITESPACE) {
                $this->addViolation('No space before operator', $before);
            } elseif ($after && $after->getId() !== T_WHITESPACE) {
                $this->addViolation('No space after operator', $after);
            }
        }
    }

    protected function isException($before, $current, $after) {
        // Exclamation mark
        $center = array(T_EXCLAMATIONMARK);

        if (in_array($current->getId(), $center)) {
            return true;
        }

        // Increment and decrement
        $center = array(T_MINUS, T_PLUS);
        $right = array(T_MINUS, T_PLUS);

        if (in_array($current->getId(), $center) && in_array($after->getId(), $right)) {
            return true;
        }

        $center = array(T_DEC, T_INC);

        if (in_array($current->getId(), $center)) {
            return true;
        }

        // Single & composed - equal
        $center = array(
            T_SL, T_SR,
            T_DOT, T_EQUAL, T_LT, T_GT, T_PLUS, T_MINUS, T_MULT, T_DIV, T_PERCENT, T_PIPE,
            T_CARET, T_TILDE,

        );
        $right = array(T_EQUAL);

        if (in_array($current->getId(), $center) && in_array($after->getId(), $right)) {
            return true;
        }

        // The ampersand problem
        $center = array(T_AMPERSAND);
        $right = array(T_VARIABLE, T_EQUAL, T_STRING);

        if (in_array($current->getId(), $center) && in_array($after->getId(), $right)) {
            return true;
        }

        $center = array(T_EQUAL);
        $right = array(T_AMPERSAND);

        if (in_array($current->getId(), $center) && in_array($after->getId(), $right)) {
            return true;
        }

        // Minus and plus literal number, variable but not within operations
        $center = array(T_MINUS, T_PLUS);
        $right = array(T_DNUMBER, T_LNUMBER, T_VARIABLE);

        if (in_array($current->getId(), $center) && in_array($after->getId(), $right)) {
            $this->file->prev();
            $this->file->prev();
            $beforebefore = $this->file->current();
            $this->file->next();
            $this->file->next();

            $bad = array(T_DNUMBER, T_LNUMBER, T_VARIABLE, T_ARRAY);

            if (!in_array($before->getId(), $bad) && !in_array($beforebefore->getId(), $bad)) {
                return true;
            }
            return false;
        }

        // Questionmark and colon, ternary operator
        $center = array(T_QUESTIONMARK);
        $right = array(T_COLON);

        if (in_array($current->getId(), $center) && in_array($after->getId(), $right)) {
            return true;
        }

        // Case
        $left = array(T_WHITESPACE);
        $center = array(T_COLON);

        if (in_array($before->getId(), $left) && in_array($current->getId(), $center)) {
            return true;
        }

        // Label
        $center = array(T_COLON);
        $right = array(T_WHITESPACE);

        if (in_array($current->getId(), $center) && in_array($after->getId(), $right)) {
            return true;
        }
        return false;
    }
}
?>