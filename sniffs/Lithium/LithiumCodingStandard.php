<?php

class PHP_CodeSniffer_Standards_Lithium_LithiumCodingStandard extends PHP_CodeSniffer_Standards_CodingStandard {

	public function getIncludedSniffs() {
		return array(
			'Generic/Sniffs/Formatting/NoSpaceAfterCastSniff.php',
			'Generic/Sniffs/ControlStructures/InlineControlStructureSniff.php',
			'Generic/Sniffs/CodeAnalysis/UselessOverridingMethodSniff.php',
			// Uncomment if not allowed anymore
			// 'Generic/Sniffs/Commenting/TodoSniff.php',
			'Generic/Sniffs/Files/LineEndingsSniff.php',
			'Generic/Sniffs/Metrics/NestingLevelSniff.php',
			'Generic/Sniffs/PHP/LowerCaseConstantSniff.php',
			'Generic/Sniffs/PHP/NoSilencedErrorsSniff.php',
			'PEAR/Sniffs/Commenting/InlineCommentSniff.php',
			'PEAR/Sniffs/Functions/FunctionCallArgumentSpacingSniff.php',
			'PEAR/Sniffs/Functions/ValidDefaultValueSniff.php',
			'Squiz/Sniffs/Arrays/ArrayBracketSpacingSniff.php',
			'Squiz/Sniffs/Classes/LowercaseClassKeywordsSniff.php',
			'Squiz/Sniffs/Commenting/DocCommentAlignmentSniff.php',
			'Squiz/Sniffs/ControlStructures/ForEachLoopDeclarationSniff.php',
			'Squiz/Sniffs/ControlStructures/ForLoopDeclarationSniff.php',
			'Squiz/Sniffs/ControlStructures/LowercaseDeclarationSniff.php',
			'Squiz/Sniffs/Functions/GlobalFunctionSniff.php',
			'Squiz/Sniffs/Operators/ValidLogicalOperatorsSniff.php',
			// Uncomment if not allowed anymore
			// 'Squiz/Sniffs/PHP/CommentedOutCodeSniff.php',
			'Squiz/Sniffs/PHP/GlobalKeywordSniff.php',
			'Squiz/Sniffs/Scope/StaticThisUsageSniff.php',
			'Squiz/Sniffs/WhiteSpace/CastSpacingSniff.php',
			'Squiz/Sniffs/WhiteSpace/FunctionOpeningBraceSpaceSniff.php',
			'Squiz/Sniffs/WhiteSpace/LanguageConstructSpacingSniff.php',
			'Squiz/Sniffs/WhiteSpace/ObjectOperatorSpacingSniff.php',
			'Squiz/Sniffs/WhiteSpace/SemicolonSpacingSniff.php',
			'Squiz/Sniffs/WhiteSpace/SuperfluousWhitespaceSniff.php',
			// Build similar ones
			// 'PEAR/Sniffs/ControlStructures/ControlSignatureSniff.php'
			// 'Squiz/Sniffs/Commenting/FunctionCommentSniff.php',
		);
	}
}

?>