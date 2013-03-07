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
        $this->given->theFolder('contenttype');
        $this->given->theModule_In('contenttype\ContentType', 'contenttype');
        $this->given->theFile_In_WithContent('someStyle.css', 'contenttype', '// CSS Stuff');
        $this->given->theFile_In_WithContent('someHtml.html', 'contenttype', '<!-- HTML stuff -->');

        $this->when->iRequest_From('someStyle.css', 'contenttype\ContentType');
        $this->then->theResponseHeader_ShouldBe(Response::HEADER_CONTENT_TYPE, 'text/css');

        $this->when->iRequest_From('someHtml.html', 'contenttype\ContentType');
        $this->then->theResponseHeader_ShouldBe(Response::HEADER_CONTENT_TYPE, 'text/html');
    }

    public function testComponent() {
        $this->given->theFolder('component');
        $this->given->theModule_In('component\ComponentModule', 'component');
        $this->given->theComponent_In_WithTheMethod_ThatReturns('component\Index', 'component', 'doGet', '{"test":"me"}');

        $this->when->iRequest_From('index.html', 'component\ComponentModule');

        $this->then->theResponseBodyShouldBe('{"test":"me"}');
    }

    public function testComponentWithTemplate() {
        $this->given->theFolder('templatetest');
        $this->given->theModule_In('templatetest\TemplateModule', 'templatetest');
        $this->given->theComponent_In_WithTheMethod_ThatReturns('templatetest\IndexWithTemplate', 'templatetest', 'doGet', '{"test":"World"}');
        $this->given->theFile_In_WithContent('indexWithTemplate.html', 'templatetest', 'Hello %test%');

        $this->when->iRequest_From('indexWithTemplate.html', 'templatetest\TemplateModule');

        $this->then->theResponseBodyShouldBe('Hello World');
    }

    public function testComponentInFolder() {
        $this->given->theFolder('outer');
        $this->given->theFolder('outer/inner');
        $this->given->theModule_In('outer\InnerModule', 'outer');
        $this->given->theComponent_In_WithTheMethod_ThatReturns('outer\inner\InnerComponent', 'outer/inner', 'doGet', '{"inner":"found"}');

        $this->when->iRequest_From('inner/InnerComponent.php', 'outer\InnerModule');

        $this->then->theResponseBodyShouldBe('{"inner":"found"}');
    }

    public function testNonExistingComponent() {
        $this->given->theFolder('wrongmodule');
        $this->given->theModule_In('wrongmodule\EmptyModule', 'wrongmodule');

        $this->when->iTryToRequest_From('notExist.php', 'wrongmodule\EmptyModule');
        $this->then->anExceptionContaining_ShouldBeThrown('notExist.php');

        $this->when->iTryToRequest_From('index.php', 'wrongmodule\EmptyModule');
        $this->then->anExceptionContaining_ShouldBeThrown('index.php');
    }

    public function testRedirectFromModule() {
        $this->given->theFolder('redirectmodule');
        $this->given->theModule_InThatRedirectsTo('redirectmodule\Module', 'redirectmodule', 'relative/path');

        $this->when->iRequest_From('some/thing/else.html', 'redirectmodule\Module');

        $this->then->theResponseHeader_ShouldBe(Response::HEADER_LOCATION, '/base/relative/path');
    }

    public function testRedirectFromComponent() {
        $this->given->theFolder('redirectcomponent');
        $this->given->theModule_In('redirectcomponent\Module', 'redirectcomponent');
        $this->given->theFolder('redirectcomponent/inner');
        $this->given->theComponent_In_ThatRedirectsTo('redirectcomponent\inner\Component', 'redirectcomponent/inner', 'some/path');

        $this->when->iRequest_From('inner/component.html', 'redirectcomponent\Module');

        $this->then->theResponseHeader_ShouldBe(Response::HEADER_LOCATION, '/base/inner/some/path');
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

        $this->cleanDir($fullFolder);

        mkdir($fullFolder);

        $this->test->undos[] = function () use ($fullFolder) {
            rmdir($fullFolder);
        };
    }

    public function theModule_In($moduleName, $folder) {
        $classPath = explode('\\', $moduleName);
        $shortName = array_pop($classPath);
        $namespace = implode('\\', $classPath);
        $classFile = $shortName . '.php';

        $classDef = "<?php namespace $namespace; class $shortName extends \\watoki\\webco\\Module {}";

        $this->theFile_In_WithContent($classFile, $folder, $classDef);
    }

    public function theModule_InThatRedirectsTo($moduleName, $folder, $target) {
        list($namespace, $shortName) = explode('\\', $moduleName);
        $classFile = $shortName . '.php';

        $classDef = "<?php namespace $namespace; class $shortName extends \\watoki\\webco\\Module {
            public function respond(\\watoki\\webco\\Request \$request) {
                \$this->redirect(new \\watoki\\webco\\Url('$target'));
                return \$this->getResponse();
            }
        }";

        $this->theFile_In_WithContent($classFile, $folder, $classDef);
    }

    public function theComponent_In_WithTheMethod_ThatReturns($className, $folder, $method, $returnJson) {
        $classPath = explode('\\', $className);
        $shortName = array_pop($classPath);
        $namespace = implode('\\', $classPath);
        $classFile = $shortName . '.php';

        $classDef = "<?php namespace $namespace; class $shortName extends \\watoki\\webco\\Component {
            public function $method () {
                return json_decode('$returnJson', true);
            }

            protected function doRender(\$model, \$template) {
                foreach (\$model as \$key => \$value) {
                    \$template = str_replace('%' . \$key . '%', \$value, \$template);
                }
                return \$template;
            }
        }";

        $this->theFile_In_WithContent($classFile, $folder, $classDef);
    }

    public function theComponent_In_ThatRedirectsTo($className, $folder, $target) {
        $classPath = explode('\\', $className);
        $shortName = array_pop($classPath);
        $namespace = implode('\\', $classPath);
        $classFile = $shortName . '.php';

        $classDef = "<?php namespace $namespace; class $shortName extends \\watoki\\webco\\Component {
            public function respond(\\watoki\\webco\\Request \$request) {
                \$this->redirect(new \\watoki\\webco\\Url('$target'));
                return \$this->getResponse();
            }

            protected function doRender(\$model, \$template) {}
        }";

        $this->theFile_In_WithContent($classFile, $folder, $classDef);
    }

    public function theFile_In_WithContent($fileName, $folder, $content) {
        $file = __DIR__ . '/' . $folder . '/' . $fileName;
        file_put_contents($file, $content);

        $test = $this->test;
        $this->test->undos[] = function () use ($file, $test) {
            if (!unlink($file)) {
                $test->fail('Could not delete ' . $file);
            }
        };
    }

    private function cleanDir($folder) {
        if (!file_exists($folder)) {
            return;
        }

        foreach(glob(rtrim($folder, '/') . '/' . '*') as $item) {
            is_dir($item) ? $this->cleanDir($item) : unlink($item);
        }
        rmdir($folder);
    }
}

class ModuleTest_When extends Step {

    /**
     * @var Response
     */
    public $response;

    /**
     * @var null|\Exception
     */
    public $caught;

    public function iRequest_From($resource, $module) {
        $factory = new Factory();
        $route = '/base/';

        $request = new Request(Request::METHOD_GET, $resource, new Map(), new Map());

        /** @var $module Module */
        $module = new $module($factory, $route);

        $this->response = $module->respond($request);
    }

    public function iTryToRequest_From($resource, $module) {
        $this->caught = null;
        try {
            $this->iRequest_From($resource, $module);
        } catch (\Exception $e) {
            $this->caught = $e;
        }
    }
}

/**
 * @property ModuleTest test
 */
class ModuleTest_Then extends Step {

    public function theResponseBodyShouldBe($body) {
        $this->test->assertEquals($body, $this->test->when->response->getBody());
    }

    public function theResponseHeader_ShouldBe($field, $value) {
        $this->test->assertEquals($value, $this->test->when->response->getHeaders()->get($field));
    }

    public function anExceptionContaining_ShouldBeThrown($message) {
        $this->test->assertNotNull($this->test->when->caught);
        $this->test->assertContains($message, $this->test->when->caught->getMessage());
    }
}