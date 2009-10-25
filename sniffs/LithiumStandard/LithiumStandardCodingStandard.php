<?php

class PHP_CodeSniffer_Standards_LithiumStandard_LithiumStandardCodingStandard extends PHP_CodeSniffer_Standards_CodingStandard {

	public function getIncludedSniffs() {
		return array(
			'Generic/Sniffs/Formatting/NoSpaceAfterCastSniff.php',
			'Generic/Sniffs/ControlStructures/InlineControlStructureSniff.php',
			'Generic/Sniffs/CodeAnalysis/UnusedFunctionParameterSniff.php',
			'Generic/Sniffs/CodeAnalysis/UselessOverridingMethodSniff.php',
			// Uncomment if not allowed anymore
			// 'Generic/Sniffs/Commenting/TodoSniff.php',
			'Generic/Sniffs/Files/LineEndingsSniff.php',
			'Generic/Sniffs/Metrics/CyclomaticComplexitySniff.php',
			'Generic/Sniffs/Metrics/NestingLevelSniff.php',
			'Generic/Sniffs/NamingConventions/UpperCaseConstantNameSniff.php',
			'Generic/Sniffs/PHP/LowerCaseConstantSniff.php',
			'Generic/Sniffs/PHP/NoSilencedErrorsSniff.php',
			'Generic/Sniffs/PHP/ForbiddenFunctionsSniff.php',
		);
	}
}

?>