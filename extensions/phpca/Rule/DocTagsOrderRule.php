<?php

namespace spriebsch\PHPca\Rule;

use spriebsch\PHPca\Token;

/**
 * Ensures all constants are uppercase
 */
class DocTagsOrderRule extends Rule {

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

			$docTags = array();

			preg_match_all('/@.*?\s/', $docText, $docTags);

			$docIndex = 0;
			foreach ($this->tagsOrdered as $tag) {
				$tagIndex = array_search($tag . " ", $docTags[0]);
				if ($tagIndex !== false) {
					if ($tagIndex < $docIndex) {
						$this->addViolation("Doc tags not ordered correctly", $token);
						//Break so there is only one violation per code block
						break;
					}

					$docIndex = $tagIndex;
				}

			}
			$this->file->next();
		}
	}
}

?>