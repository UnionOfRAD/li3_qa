#!/usr/bin/env php -q
<?php
/**
 * Check validity of file using PHP lint.
 */
$phplint = function($file) {
	if (!file_exists($file) || !preg_match('/\.php$/', $file)) {
		return null;
	}
	$file = escapeshellarg($file);
	exec("php -l {$file} 2> /dev/null", $output, $return);

	if ($return == 0) {
		return null;
	}
	return array_filter($output, function($error) { return !empty($error) && $error[0] == 'P'; });
};

/**
 * Enforce Lithium coding standards using PHP CodeSniffer.
 */
$phpcs = function($file) {
	if (!file_exists($file) || !preg_match('/\.php$/', $file)) {
		return null;
	}
	$file = escapeshellarg($file);
	$standard = escapeshellarg(__DIR__ . '/sniffs/Lithium');
	exec("phpcs -n --standard={$standard} {$file}", $output, $return);

	if ($return != 0) {
		return array_slice($output, 5, -2);
	}
};

/**
 * Register checks and corresponding names for display.
 */
$checks = array(
	'PHP Lint' => $phplint,
	'Lithium Coding Standards' => $phpcs
);

/**
 * Main execution.
 */
exec('git rev-parse --verify HEAD 2> /dev/null', $output, $return);
$against = $return ? '4b825dc642cb6eb9a060e54bf8d69288fbee4904' : 'HEAD';
exec("git diff-index --cached --name-only {$against}", $output);

$errorState = false;
$hr = str_repeat('-', 80);

foreach ($output as $file) {
	echo "\nChecking file {$file}...\n";

	foreach ($checks as $name => $check) {
		if ($errors = $check($file)) {
			echo "\nFailed check {$name}.\n{$hr}\n";
			echo implode("\n", $errors) . "\n";
			$errorState = true;
		}
	}
}

if ($errorState) {
	echo "\nError(s) found. Bypass this check with the --no-verify flag\n";
	exit(1);
}
exit(0);

?>