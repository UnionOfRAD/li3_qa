#!/usr/bin/env php -q
<?php
/**
 * Lithium Hooks
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium_hooks;

/* Checks. Disable/enable or add and modify. */

$checks = array();

/* Enforce Lithium coding standards using phpca. */
$checks['phpca'] = function($file) {
	if (!file_exists($file) || !preg_match('/\.php$/', $file)) {
		return false;
	}
	$phpCommand = escapeshellarg(trim(shell_exec('which php')));
	$phpcaCommand = escapeshellarg(__DIR__ . '/libraries/phpca/src/phpca.php');
	$file = escapeshellarg($file);
	$standard = escapeshellarg(__DIR__ . '/extensions/phpca/Standard/lithium.ini');
	exec("php {$phpcaCommand} -p {$phpCommand} --standard {$standard} {$file}", $output, $return);

	if ($return != 0) {
		return array_filter(array_slice($output, 9, -3));
	}
};

/* Check validity of file using PHP lint. */
// $checks['phplint'] = function($file) {
// 	if (!file_exists($file) || !preg_match('/\.php$/', $file)) {
// 		return false;
// 	}
// 	$file = escapeshellarg($file);
// 	exec("php -l {$file} 2> /dev/null", $output, $return);
//
// 	if ($return == 0) {
// 		return null;
// 	}
// 	return array_filter($output, function($error) { return !empty($error) && $error[0] == 'P'; });
// };

/* Main execution. You should not need to change anything below. */

/* Obtain a list of files with changes. */
exec('git rev-parse --verify HEAD 2> /dev/null', $output, $return);
$against = $return ? '4b825dc642cb6eb9a060e54bf8d69288fbee4904' : 'HEAD';
exec("git diff-index --cached --name-only {$against}", $output);

$files = $output;
$numberFiles = count($files);
$errorState = false;
$hr = str_repeat('-', 80);

/* Run checks. */
foreach ($files as $file) {
	foreach ($checks as $name => $check) {
		echo "Running check `{$name}` against `{$file}`... ";

		if ($errors = $check($file)) {
			echo "Failed!\n";
			echo implode("\n", $errors) . "\n\n";
			$errorState = true;
		} elseif ($errors === null) {
			echo "Passed.\n";
		} else {
			echo "Skipped.\n";
		}
	}
}

if ($errorState) {
	echo "\nError(s) found. Bypass this check with the --no-verify flag\n";
	exit(1);
}
exit(0);

?>