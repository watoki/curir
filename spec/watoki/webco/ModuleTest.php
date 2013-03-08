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
        $this->given->theComponent_In('component\Index', 'component');

        $this->when->iRequest_From('index.html', 'component\ComponentModule');

        $this->then->theResponseBodyShouldBe('Found component\Index');
    }

    public function testComponentInFolder() {
        $this->given->theFolder('outer');
        $this->given->theFolder('outer/inner');
        $this->given->theModule_In('outer\InnerModule', 'outer');
        $this->given->theComponent_In('outer\inner\InnerComponent', 'outer/inner');

        $this->when->iRequest_From('inner/InnerComponent.php', 'outer\InnerModule');

        $this->then->theResponseBodyShouldBe('Found outer\inner\InnerComponent');
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

class ModuleTest_Given extends Given {

    public function theModule_In($moduleName, $folder) {
        $this->theClass_In_Extending_WithTheBody($moduleName, $folder, '\watoki\webco\Module', '');
    }

    public function theModule_InThatRedirectsTo($moduleName, $folder, $target) {
        $this->theClass_In_Extending_WithTheBody($moduleName, $folder, '\watoki\webco\Module', "
            public function respond(\\watoki\\webco\\Request \$request) {
                \$this->redirect(new \\watoki\\webco\\Url('$target'));
                return \$this->getResponse();
            }
        ");
    }

    public function theComponent_In($className, $folder) {
        $this->theClass_In_Extending_WithTheBody($className, $folder, '\watoki\webco\Component', "
            public function respond(\\watoki\\webco\\Request \$request) {
                \$this->getResponse()->setBody('Found $className');
                return \$this->getResponse();
            }
            protected function doRender(\$model, \$template) {}
        ");
    }

    public function theComponent_In_ThatRedirectsTo($className, $folder, $target) {
        $this->theClass_In_Extending_WithTheBody($className, $folder, '\watoki\webco\Component', "
            public function respond(\\watoki\\webco\\Request \$request) {
                \$this->redirect(new \\watoki\\webco\\Url('$target'));
                return \$this->getResponse();
            }

            protected function doRender(\$model, \$template) {}
        ");
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