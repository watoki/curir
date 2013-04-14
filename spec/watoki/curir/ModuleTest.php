<?php
namespace spec\watoki\curir;

use spec\watoki\curir\steps\Given;
use spec\watoki\curir\steps\When;
use watoki\collections\Map;
use watoki\factory\Factory;
use \watoki\curir\controller\Module;
use watoki\curir\Request;
use watoki\curir\Response;

/**
 * @property ModuleTest_Given given
 * @property ModuleTest_When when
 */
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
        $this->given->theComponent_In('component\IndexComponent', 'component');

        $this->when->iRequest_From('index.html', 'component\ComponentModule');

        $this->then->theResponseBodyShouldBe('Found component\IndexComponent');
    }

    public function testInnerModule() {
        $this->given->theFolder('innerModule');
        $this->given->theModule_In('innerModule\OuterModule', 'innerModule');
        $this->given->theFolder('innerModule/inside');
        $this->given->theModule_In_WithTheBody('innerModule\inside\InsideModule', 'innerModule/inside', '
        public function respond(\watoki\curir\Request $request) {
            $response = new \watoki\curir\Response();
            $response->setBody("Found me");
            return $response;
        }');

        $this->when->iRequest_From('inside/test.txt', 'innerModule\OuterModule');

        $this->then->theResponseBodyShouldBe('Found me');
    }

    public function testComponentInFolder() {
        $this->given->theFolder('outer');
        $this->given->theFolder('outer/inner');
        $this->given->theModule_In('outer\InnerModule', 'outer');
        $this->given->theComponent_In('outer\inner\InnerComponent', 'outer/inner');

        $this->when->iRequest_From('inner/inner.php', 'outer\InnerModule');

        $this->then->theResponseBodyShouldBe('Found outer\inner\InnerComponent');
    }

    public function testAbsoluteResource() {
        $this->given->theFolder('isAbsolute');
        $this->given->theModule_In('isAbsolute\Module', 'isAbsolute');
        $this->given->theComponent_In('isAbsolute\AbsoluteComponent', 'isAbsolute');

        $this->when->iRequest_From('/base/Absolute', 'isAbsolute\Module');

        $this->then->theResponseBodyShouldBe('Found isAbsolute\AbsoluteComponent');
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
        $this->given->theComponent_In_ThatRedirectsTo('redirectcomponent\inner\ComponentComponent', 'redirectcomponent/inner', 'some/path');

        $this->when->iRequest_From('inner/component.html', 'redirectcomponent\Module');

        $this->then->theResponseHeader_ShouldBe(Response::HEADER_LOCATION, '/base/inner/some/path');
    }

}

class ModuleTest_Given extends Given {

    public function theModule_In($moduleName, $folder) {
        $this->theModule_In_WithTheBody($moduleName, $folder, '');
    }

    public function theModule_In_WithTheBody($moduleName, $folder, $body) {
        $this->theClass_In_Extending_WithTheBody($moduleName, $folder, '\watoki\curir\controller\Module', $body);
    }

    public function theModule_InThatRedirectsTo($moduleName, $folder, $target) {
        $this->theClass_In_Extending_WithTheBody($moduleName, $folder, '\watoki\curir\controller\Module', "
            public function respond(\\watoki\\curir\\Request \$request) {
                \$this->redirect(\\watoki\\curir\\Url::parse('$target'));
                return \$this->getResponse();
            }
        ");
    }

    public function theComponent_In($className, $folder) {
        $this->theClass_In_Extending_WithTheBody($className, $folder, '\watoki\curir\controller\Component', "
            public function respond(\\watoki\\curir\\Request \$request) {
                \$this->getResponse()->setBody('Found $className');
                return \$this->getResponse();
            }
            protected function doRender(\$model, \$template) {}
        ");
    }

    public function theComponent_In_ThatRedirectsTo($className, $folder, $target) {
        $this->theClass_In_Extending_WithTheBody($className, $folder, '\watoki\curir\controller\Component', '
            public function respond(\watoki\curir\Request $request) {
                $this->redirect(\watoki\curir\Url::parse("' . $target. '"));
                return $this->getResponse();
            }

            protected function doRender($model, $template) {}
        ');
    }
}

class ModuleTest_When extends When {

    public function iRequest_From($resource, $module) {
        $this->test->given->theRequestMethodIs(Request::METHOD_GET);
        $this->test->given->theRequestResourceIs($resource);

        $this->iSendTheRequestTo($module);
    }

    public function iTryToRequest_From($resource, $module) {
        $this->test->given->theRequestMethodIs(Request::METHOD_GET);
        $this->test->given->theRequestResourceIs($resource);

        $this->iTryToSendTheRequestTo($module);
    }
}