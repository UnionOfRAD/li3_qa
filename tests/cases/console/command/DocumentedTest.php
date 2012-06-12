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
			if(file_exists($file)) {
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

	public function testClassDocBlock() {
		$command = new Documented(array('request' => $this->request, 'classes' => $this->classes));
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
		$noClassDocs = $this->_tempFileWithContents($contents);
		$this->assertTrue(!$command->checkFile($noClassDocs));

		$command = new Documented(array('request' => $this->request, 'classes' => $this->classes));
		$command->run($noClassDocs);
		$expected = preg_quote('Class Foo not documented');
		$this->assertPattern("/{$expected}/", $command->response->output);
	}

	public function testClassVarsDocBlocks() {
		$command = new Documented(array('request' => $this->request, 'classes' => $this->classes));
	}
}

?>