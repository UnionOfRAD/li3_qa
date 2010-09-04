<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use lithium\core\Environment;
use lithium\core\Libraries;

/**
 * Define core paths, if we are operating as an independent app.
 */
if (!defined('LITHIUM_LIBRARY_PATH')) {
	define('LITHIUM_APP_PATH', dirname(__DIR__));
	define('LITHIUM_LIBRARY_PATH', dirname(dirname(__DIR__)) . '/libraries');
}

/**
 * Locate and load Lithium core library files.  Throws a fatal error if the core can't be found.
 * If your Lithium core directory is named something other than 'lithium', change the string below.
 */
if (!include_once LITHIUM_LIBRARY_PATH . '/lithium/core/Libraries.php') {
	$message  = "Lithium core could not be found.  Check the value of LITHIUM_LIBRARY_PATH in ";
	$message .= "config/bootstrap.php.  It should point to the directory containing your ";
	$message .= "/libraries directory.";
	trigger_error($message, E_USER_ERROR);
}

/**
 * Add the Lithium core library.  This sets default paths and initializes the autoloader.  You
 * generally should not need to override any settings.
 */
if (!Libraries::get('lithium')) {
	Libraries::add('lithium');
}

/**
 * Add the application.  You can pass a `'path'` key here if this bootstrap file is outside of
 * your main application, but generally you should not need to change any settings.
 */
if (!Libraries::get('lithium_qa')) {
	Libraries::add('lithium_qa', array('default' => true));
}

/**
 * Add libraries from submodules.
 */
Libraries::add('phpca', array(
	'prefix' => 'spriebsch\\PHPca\\',
	'path' => dirname(__DIR__) . '/libraries/phpca/src',
	'bootstrap' => 'Autoload.php'
));

?>