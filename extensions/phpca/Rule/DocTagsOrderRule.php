<?php

namespace spriebsch\PHPca\Rule;

use spriebsch\PHPca\Token;

/**
 * Ensures documentation Tags are ordered properly
 */
class DocTagsOrderRule extends Rule {

    /**
     * List of possible tags, ordered
     *
     * All tags will be referenced against this list. If they
     * appear out of order, a violation will be raised. Simple
     * order check regardless of missing tags.
     */
    protected $tagsOrdered = array (
        "@link",
        "@see",
        "@params",
        "@return"
    );

    /**
     * Performs the rule check
     *
     * @return null
     */
    protected function doCheck() {
        while ($this->file->seekTokenId(T_DOC_COMMENT)) {
            $token = $this->file->current();
            $docText = $token->getText();

            //Grab ordered array of the tags in this token
            $docTags = array();
            preg_match_all('/@.*?\s/', $docText, $docTags);
            $docTags = array_shift($docTags);

            $docIndex = 0;
            $lastTag = "";
            foreach ($docTags as $tag) {
                $tag = trim($tag);
                $tagIndex = array_search($tag, $this->tagsOrdered);

                if ($tagIndex !== false) {
                    if ($tagIndex < $docIndex) {
                        $this->addViolation(
                            "Doc tag `" . $tag . "` not ordered correctly, came after `" . $lastTag . "`",
                            $token
                        );
                        continue;
                    }

                    $docIndex = $tagIndex;
                    $lastTag = $tag;
                }

            }
            $this->file->next();
        }
    }
}

?>