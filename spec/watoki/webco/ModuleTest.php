<?php
namespace spec\watoki\webco;

/**
 * @property ModuleTest_Given given
 * @property ModuleTest_When when
 * @property ModuleTest_Then then
 */
use watoki\collections\Map;
use watoki\factory\Factory;
use watoki\webco\Module;
use watoki\webco\Request;
use watoki\webco\Response;

class ModuleTest extends Test {

    public function testStaticFile() {
        $this->given->theFolder('test');
        $this->given->theModule_In('test\Test', 'test');
        $this->given->theFile_In_WithContent('somefile.txt', 'test', 'Some test file.');

        $this->when->iRequest_From('somefile.txt', 'test\Test');

        $this->then->theResponseBodyShouldBe('Some test file.');
    }

    public function testResponseContentType() {
        $this->given->theFolder('test');
        $this->given->theModule_In('test\ContentType', 'test');
        $this->given->theFile_In_WithContent('someStyle.css', 'test', '// CSS Stuff');
        $this->given->theFile_In_WithContent('someHtml.html', 'test', '<!-- HTML stuff -->');

        $this->when->iRequest_From('someStyle.css', 'test\ContentType');
        $this->then->theResponseHeader_ShouldBe(Response::HEADER_CONTENT_TYPE, 'text/css');

        $this->when->iRequest_From('someHtml.html', 'test\ContentType');
        $this->then->theResponseHeader_ShouldBe(Response::HEADER_CONTENT_TYPE, 'text/html');
    }

}

class ModuleTest_Given extends Step {

    function __construct(Test $test) {
        parent::__construct($test);

        spl_autoload_register(function ($className) {
            $classFile = __DIR__ . '/' . str_replace('\\', '/', $className) . '.php';
            if (file_exists($classFile)) {
                require_once $classFile;
            }
        });
    }

    public function theFolder($folder) {
        $fullFolder = __DIR__ . '/' . $folder;
        mkdir($fullFolder);

        $this->test->undos[] = function () use ($fullFolder) {
            rmdir($fullFolder);
        };
    }

    public function theModule_In($moduleName, $folder) {
        list($namespace, $shortName) = explode('\\', $moduleName);
        $classFile = $shortName . '.php';

        $classDef = "<?php namespace $namespace; class $shortName extends \\watoki\\webco\\Module {}";

        $this->theFile_In_WithContent($classFile, $folder, $classDef);
    }

    public function theFile_In_WithContent($fileName, $folder, $content) {
        $file = __DIR__ . '/' . $folder . '/' . $fileName;
        file_put_contents($file, $content);

        $this->test->undos[] = function () use ($file) {
            unlink($file);
        };
    }
}

class ModuleTest_When extends Step {

    /**
     * @var Response
     */
    public $response;

    public function iRequest_From($resource, $module) {
        $factory = new Factory();
        $route = 'some/route';

        $request = new Request(Request::METHOD_GET, $resource, new Map(), new Map());

        /** @var $module Module */
        $module = new $module($factory, $route);

        $this->response = $module->respond($request);
    }
}

/**
 * @property ModuleTest test
 * @property ModuleTest test
 */
class ModuleTest_Then extends Step {

    public function theResponseBodyShouldBe($body) {
        $this->test->assertEquals($body, $this->test->when->response->getBody());
    }

    public function theResponseHeader_ShouldBe($field, $value) {
        $this->test->assertEquals($value, $this->test->when->response->getHeaders()->get($field));
    }
}