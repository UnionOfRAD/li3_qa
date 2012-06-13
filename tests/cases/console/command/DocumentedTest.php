<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_qa\tests\cases\console\command;

use lithium\console\Request;
use lithium\core\Libraries;
use li3_qa\extensions\command\Documented;

class DocumentedTest extends \lithium\test\Unit {

	protected $_files = array();

	public function setUp() {
		$this->classes = array('response' => 'lithium\tests\mocks\console\MockResponse');
		$this->request = new Request(array('input' => fopen('php://temp', 'w+')));
	}

	public function tearDown() {
		foreach ($this->_files as $file) {
			if (file_exists($file)) {
				unlink($file);
			}
		}
	}

	public function testRun() {
		$command = new Documented(array('request' => $this->request, 'classes' => $this->classes));
		$this->assertTrue($command->run(''));
	}

	public function testPath() {
		$command = new Documented(array('request' => $this->request, 'classes' => $this->classes));
		$command->run('/foo/bar');
		$expected = preg_quote('Not a valid path');
		$this->assertPattern("/{$expected}/", $command->response->error);

		$command = new Documented(array('request' => $this->request, 'classes' => $this->classes));
		$command->run(__FILE__);
		$expected = preg_quote('Check complete');
		$this->assertPattern("/{$expected}/", $command->response->output);
	}

	public function testHeader() {
		$command = new Documented(array('request' => $this->request, 'classes' => $this->classes));
		$noHeader = $this->_tempFileWithContents("<?php echo 'foo'; ?>");
		$result = $command->checkFile($noHeader);
		$expected = preg_quote('No file header found');
		$this->assertPattern("/{$expected}/", $command->response->output);
		$this->assertTrue(!$result);

		$command = new Documented(array('request' => $this->request, 'classes' => $this->classes));
		$contents = "<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace foo\bar;

/**
 * Class docs.
 *
 */
class Foo {}
?>";
		$noHeader = $this->_tempFileWithContents($contents);
		$this->assertTrue($command->checkFile($noHeader));
	}

	protected function _tempFileWithContents($contents) {
		$path = Libraries::get(true, 'resources') . '/tmp/' . uniqid() . '.php';
		$this->_files[] = $path;
		file_put_contents($path, $contents);
		return $path;
	}

	protected function _expectResponseFromFile($expected, $contents, $not = false) {
		$tempFile = $this->_tempFileWithContents($contents);
		$command = new Documented(array('request' => $this->request, 'classes' => $this->classes));
		$command->run($tempFile);
		$expected = preg_quote($expected);
		if (!$not) {
			$this->assertPattern("/{$expected}/", $command->response->output);
		} else {
			$this->assertNoPattern("/{$expected}/", $command->response->output);
		}

	}

	public function testClassDocBlock() {
		$contents = "<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace foo\bar;

class Foo {}
?>";
		$this->_expectResponseFromFile('Class Foo not documented', $contents);
	}

	public function testClassVarsDocBlocks() {
		$contents = "<?php
class Foo {
	public \$foo = 'bar';
}
?>";
		$this->_expectResponseFromFile('$foo not documented', $contents);

		$contents = "<?php
class Foo {
	/**
	 * Some docs for this string var.
	 *
	 * @var string
	 */
	public \$foo = 'bar';
}
?>";
		$this->_expectResponseFromFile('$foo not documented', $contents, true);

		$contents = "<?php
class Foo {
	public static \$foo = 'bar';
}
?>";
		$this->_expectResponseFromFile('$foo not documented', $contents);

		$contents = "<?php
class Foo {
	/**
	 * Some docs for this string var.
	 *
	 * @var string
	 */
	public static \$foo = 'bar';
}
?>";
		$this->_expectResponseFromFile('$foo not documented', $contents, true);

		$contents = "<?php
class Foo {
	abstract public static \$foo = 'bar';
}
?>";
		$this->_expectResponseFromFile('$foo not documented', $contents);

		$contents = "<?php
class Foo {
	/**
	 * Some docs for this string var.
	 *
	 * @var string
	 */
	abstract public static \$foo = 'bar';
}
?>";
		$this->_expectResponseFromFile('$foo not documented', $contents, true);
	}

	public function testMethodDocBlocks() {
		$contents = "<?php
class Foo {
	public function foo() {}
}
?>";
		$this->_expectResponseFromFile('foo() not documented', $contents);

		$contents = "<?php
class Foo {
	/**
	 * Some docs for this string var.
	 *
	 * @var string
	 */
	public function foo() {}
}
?>";
		$this->_expectResponseFromFile('$foo not documented', $contents, true);

		$contents = "<?php
class Foo {
	public static function foo() {}
}
?>";
		$this->_expectResponseFromFile('foo() not documented', $contents);

		$contents = "<?php
class Foo {
	/**
	 * Some docs for this string var.
	 *
	 * @var string
	 */
	public static function foo() {}
}
?>";
		$this->_expectResponseFromFile('foo() not documented', $contents, true);

		$contents = "<?php
class Foo {
	abstract public static function foo() {}
}
?>";
		$this->_expectResponseFromFile('foo() not documented', $contents);

		$contents = "<?php
class Foo {
	/**
	 * Some docs for this string var.
	 *
	 * @var string
	 */
	abstract public static function foo() {}
}
?>";
		$this->_expectResponseFromFile('foo() not documented', $contents, true);
	}
}

?>