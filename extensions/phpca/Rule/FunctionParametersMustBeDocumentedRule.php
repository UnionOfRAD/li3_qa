<?php

namespace spriebsch\PHPca\Rule;

/**
 * Ensure that each parameter of a function/method is documented.
 */
class FunctionParametersMustBeDocumentedRule extends Rule
{
    /**
     * Performs the rule check.
     *
     * @returns null
     */
    protected function doCheck()
    {
        while ($this->file->seekTokenId(T_FUNCTION)) {
            $function = $this->file->current();
            $signature = array();

            // Skip anonymous functions
            $this->file->next();

            if ($this->file->current()->getId() != T_WHITESPACE) {
                continue;
            }

            while ($this->file->current()->getId() != T_OPEN_CURLY) {
                $this->file->next();

				if (!$this->file->valid()) {
					break;
				}
                $token = $this->file->current();


                if ($token->getId() == T_VARIABLE) {
                    $signature[] = $token;
                }
            }

            if (!$this->file->seekTokenId(T_DOC_COMMENT, true)) {
                $this->file->seekToken($function);
                $this->file->next();
                continue;
            }
            $comment = $this->file->current();

            if (($comment->getEndLine() + 1) != $function->getLine()) {
                $this->file->seekToken($function);
                $this->file->next();
                continue;
            }
            $regex = '/@param\s+(?P<types>[\w\|]*)?\s?(?P<names>\$\w*)?/';
            preg_match_all($regex, $comment->getText(), $matches);

            foreach ($signature as $i => $param) {
                if (!isset($matches['names'][$i]) || $param->getText() != $matches['names'][$i]) {
                    $this->addViolation('Parameter is not documented', $param);
                }
            }
            $this->file->seekToken($function);
            $this->file->next();
        }
    }
}
?>