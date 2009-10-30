#!/usr/bin/env php -q
<?php

/* Configuration. Adjust as necessary. */

$phpCommand = '/opt/local/bin/php';

$checkEach = array();
$checkAll = array();

/* Checks. Disable/enable or add and modify. */

/* Enforce Lithium coding standards using phpca. */
$checkEach['phpca'] = function($file) use ($phpCommand) {
	if (!file_exists($file) || !preg_match('/\.php$/', $file)) {
		return null;
	}
	$phpcaCommand = escapeshellarg(__DIR__ . '/libraries/phpca/src/phpca.php');
	$file = escapeshellarg($file);
	$standard = escapeshellarg(__DIR__ . '/extensions/phpca/Standard/lithium.ini');
	exec("php {$phpcaCommand} -p {$phpCommand} --standard {$standard} {$file}", $output, $return);

	if ($return != 0) {
		return array_slice($output, 9, -3);
	}
};

/* Check validity of file using PHP lint. */
// $checkEach['phplint'] = function($file) {
// 	if (!file_exists($file) || !preg_match('/\.php$/', $file)) {
// 		return null;
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

/* Run checks for multiple files. */
foreach ($checkAll as $name => $check) {
	echo "Running check `{$name}` against {$numberFiles} file(s).\n";

	if ($errors = $check($files)) {
		echo implode("\n", $errors) . "\n";
		$errorState = true;
	}
}

/* Run checks for single files. */
foreach ($files as $file) {
	foreach ($checkEach as $name => $check) {
		echo "Running check `{$name}` against `{$file}`.\n";

		if ($errors = $check($file)) {
			echo implode("\n", $errors) . "\n\n";
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