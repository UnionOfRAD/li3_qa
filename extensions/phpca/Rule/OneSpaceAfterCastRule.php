<?php

namespace spriebsch\PHPca\Rule;

/**
 * Ensures that there is one space after a cast.
 */
class OneSpaceAfterCastRule extends Rule
{
    /**
     * Performs the rule check.
     *
     * @returns null
     */
    protected function doCheck()
    {
        $casts = array(
            T_BOOL_CAST, T_INT_CAST, T_ARRAY_CAST,
            T_DOUBLE_CAST, T_OBJECT_CAST, T_UNSET_CAST
        );
        foreach ($casts as $id) {
            while ($this->file->seekTokenId($id)) {
                $this->file->next();
                $token = $this->file->current();

                if ($token->getId() !== T_WHITESPACE) {
                    $this->addViolation('No space after cast', $token);
                } elseif (strlen($token->getText()) > 1) {
                    $this->addViolation('More than one space after cast', $token);
                }
            }
        }
    }
}
?>