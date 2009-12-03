<?php

namespace spriebsch\PHPca\Rule;

/**
 * Ensures there is only one class declared per file.
 */
class OneClassPerFileRule extends Rule
{
    /**
     * Performs the rule check.
     *
     * @returns null
     */
    protected function doCheck()
    {
        $count = 0;

		while ($this->file->seekTokenId(T_CLASS)) {
            $count++;
            $token = $this->file->current();

            if ($count > 1) {
                $this->addViolation('More than one class declared in file', $token);
            }

            $this->file->seekToken($token);
            $this->file->next();
        }
	}
}
?>