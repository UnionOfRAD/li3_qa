<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use lithium\core\Libraries;

/**
 * Add the `phpca` library.
 */
Libraries::add('phpca', array(
	'prefix' => 'spriebsch\\PHPca\\',
	'path' => dirname(__DIR__) . '/libraries/phpca/src',
	'bootstrap' => 'Autoload.php'
));

?>