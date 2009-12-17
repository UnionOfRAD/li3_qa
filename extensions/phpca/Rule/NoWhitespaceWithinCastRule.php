<?php

namespace spriebsch\PHPca\Rule;

/**
 * Ensures that there are no whitespaces within a cast.
 */
class NoWhitespaceWithinCastRule extends Rule
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
                $token = $this->file->current();

                if ($token->hasWhitespace()) {
                    $this->addViolation('Whitespace whithin cast', $token);
                }
                $this->file->next();
            }
        }
    }
}
?>