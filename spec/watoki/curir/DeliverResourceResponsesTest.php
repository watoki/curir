<?php
namespace spec\watoki\curir;

use spec\watoki\curir\fixtures\ClassesFixture;
use spec\watoki\curir\fixtures\WebDeliveryFixture;
use spec\watoki\curir\fixtures\WebRequestBuilderFixture;
use spec\watoki\stores\FileStoreFixture;
use watoki\curir\delivery\WebResponse;
use watoki\curir\renderer\DefaultRenderer;
use watoki\curir\renderer\Renderer;
use watoki\scrut\Specification;

/**
 * The WebResponse of a Resource is created by a Responder, which might render it using a template or set different headers.
 *
 * @property ClassesFixture class <-
 * @property WebRequestBuilderFixture request <-
 * @property WebDeliveryFixture delivery <-
 * @property FileStoreFixture file <-
 */
class DeliverResourceResponsesTest extends Specification {

    public function background() {
        $this->class->givenTheClass_Extending_In_WithTheBody('SomePresenter', '\watoki\curir\responder\Presenter', 'folder', '
            public function renderBig() {
                return strtoupper($this->getModel());
            }
            public function renderSmall() {
                return strtolower($this->getModel());
            }
            public function renderTxt() {
                return $this->getModel() . "!";
            }
            public function renderFoo() {
                return $this->getTemplate("foo");
            }
        ');
    }

    function testMethodNotExisting() {
        $this->givenTheTargetClass_In_WithTheBody('some\EmptyClass', 'some/folder', '');
        $this->request->givenTheRequestMethodIs('notExisting');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseStatusShouldBe(WebResponse::STATUS_METHOD_NOT_ALLOWED);
        $this->delivery->thenTheResponseBodyShouldBe('Method [notExisting] is not allowed here.');
    }

    function testMissingArgument() {
        $this->givenTheTargetClass_In_WithTheBody('MissingArgument', 'folder', '
            public function doThis($arg) {
                return "Made it";
            }
        ');
        $this->request->givenTheRequestMethodIs('this');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseBodyShouldBe('A request parameter is invalid or missing.');
        $this->delivery->thenTheResponseStatusShouldBe(WebResponse::STATUS_BAD_REQUEST);
    }

    function testRedirectToAbsoluteUrl() {
        $this->givenTheTargetResource_In_WithTheBody('RedirectAbsolute', 'folder', '
            public function doThis() {
                return \watoki\curir\responder\Redirecter::fromString("http://example.com");
            }
        ');
        $this->request->givenTheRequestMethodIs('this');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseBodyShouldBe('');
        $this->delivery->thenTheResponseStatusShouldBe(WebResponse::STATUS_SEE_OTHER);
        $this->delivery->thenTheResponseHeader_ShouldBe(WebResponse::HEADER_LOCATION, 'http://example.com');
    }

    function testRedirectToRelativeUrl() {
        $this->givenTheTargetResource_In_WithTheBody('RedirectRelative', 'folder', '
            public function doThis() {
                return \watoki\curir\responder\Redirecter::fromString("../relative/path?with=query#andFragmet");
            }
        ');
        $this->request->givenTheRequestMethodIs('this');
        $this->request->givenTheContextIs('http://some.host/some/path');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseHeader_ShouldBe(WebResponse::HEADER_LOCATION,
            'http://some.host/some/relative/path?with=query#andFragmet');
    }

    function testRedirectToSameUrl() {
        $this->givenTheTargetResource_In_WithTheBody('RedirectSame', 'folder', '
            public function doThis() {
                return \watoki\curir\responder\Redirecter::fromString("");
            }
        ');
        $this->request->givenTheRequestMethodIs('this');
        $this->request->givenTheContextIs('http://some.host/some/path');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseHeader_ShouldBe(WebResponse::HEADER_LOCATION, 'http://some.host/some/path');
    }

    function testReturnWebResponse() {
        $this->givenTheTargetResource_In_WithTheBody('ReturnWebResponse', 'folder', '
            public function doFoo() {
                return new \watoki\curir\delivery\WebResponse("Hello World");
            }
        ');
        $this->request->givenTheRequestMethodIs('foo');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseBodyShouldBe('Hello World');
    }

    function testRespondInAcceptedFormat() {
        $this->givenTheTargetResource_In_WithTheBody('RespondInAcceptedFormat', 'folder', '
            public function doThis() {
                return new SomePresenter("Hello World", $this, $this->factory);
            }
        ');
        $this->request->givenTheRequestMethodIs('this');

        $this->request->givenTheTargetPathIs('this.big');
        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseBodyShouldBe('HELLO WORLD');

        $this->request->givenTheTargetPathIs('this.small');
        $this->request->givenTheRequestMethodIs('this');
        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseBodyShouldBe('hello world');

        $this->request->givenTheTargetPathIs('this.txt');
        $this->request->givenTheRequestMethodIs('this');
        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseBodyShouldBe('Hello World!');
        $this->delivery->thenTheResponseHeader_ShouldBe(WebResponse::HEADER_CONTENT_TYPE, 'text/plain');
    }

    function testRenderMethodMissing() {
        $this->givenTheTargetResource_In_WithTheBody('RenderMethodMissing', 'folder', '
            public function doThis() {
                return new SomePresenter("Hello World", $this, $this->factory);
            }
        ');
        $this->request->givenTheRequestMethodIs('this');
        $this->request->givenTheTargetPathIs('something.unknown');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseStatusShouldBe(WebResponse::STATUS_NOT_ACCEPTABLE);
        $this->delivery->thenTheResponseBodyShouldBe('Could not render the resource in an accepted format.');
    }

    function testRenderTemplate() {
        $this->givenTheTargetResource_In_WithTheBody('RenderTemplateResource', 'some/folder', '
            public function doThis() {
                return new SomePresenter("", $this, $this->factory);
            }
        ');
        $this->request->givenTheRequestMethodIs('this');
        $this->request->givenTheTargetPathIs('something.foo');
        $this->file->givenAFile_WithContent('some/folder/renderTemplate.foo', 'Hello World');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseBodyShouldBe('Hello World');
    }

    function testTemplateFileDoesNotExist() {
        $this->givenTheTargetResource_In_WithTheBody('NoTemplateResource', 'that/folder', '
            public function doThis() {
                return new SomePresenter("", $this, $this->factory);
            }
        ');
        $this->request->givenTheRequestMethodIs('this');
        $this->request->givenTheTargetPathIs('something.foo');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseStatusShouldBe(WebResponse::STATUS_SERVER_ERROR);
        $this->delivery->thenTheResponseBodyShouldBe('Exception: Could not find template [noTemplate.foo] for [NoTemplateResource]');
    }

    /**
     * By default, the [tempan] Renderer is used for HTML responses
     *
     * [tempan]: http://github.com/watoki/temap
     */
    function testDefaultHtmlRenderer() {
        $this->givenTheDefaultRendererIs(DefaultRenderer::$CLASS);
        $this->file->givenAFile_WithContent('folder/defaultHtml.html', '<h1 property="message">Hello</h1>');
        $this->givenTheTargetResource_In_WithTheBody('DefaultHtmlResource', 'folder', '
            public function doThis() {
                return new \watoki\curir\responder\Presenter(array("message" => "Hello World"), $this, $this->factory);
            }
        ');
        $this->request->givenTheRequestMethodIs('this');
        $this->request->givenTheTargetPathIs('something.html');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseBodyShouldBe('<h1 property="message">Hello World</h1>');
    }

    function testDefaultJsonRenderer() {
        $this->givenTheTargetResource_In_WithTheBody('DefaultJsonRenderer', 'folder', '
            public function doThis() {
                return new \watoki\curir\responder\Presenter(array("foo" => array(42, 73)), $this, $this->factory);
            }
        ');
        $this->request->givenTheRequestMethodIs('this');
        $this->request->givenTheTargetPathIs('something.json');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseBodyShouldBe('{"foo":[42,73]}');
    }

    function testConvenienceWrappingStringIntoWebResponse() {
        $this->givenTheTargetResource_In_WithTheBody('WrappingStringIntoWebResponse', 'folder', '
            public function doReturnString() {
                return "Hello World";
            }
        ');
        $this->request->givenTheRequestMethodIs('returnString');
        $this->request->givenTheTargetPathIs('something.json');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseBodyShouldBe('Hello World');
    }

    function testConvenienceWrappingModelIntoResponder() {
        $this->givenTheTargetResource_In_WithTheBody('WrappingModelIntoResponde', 'folder', '
            public function doReturnModel() {
                return array("foo" => array(42, 73));
            }
        ');
        $this->request->givenTheRequestMethodIs('returnModel');
        $this->request->givenTheTargetPathIs('something.json');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseBodyShouldBe('{"foo":[42,73]}');
    }

    private function givenTheTargetResource_In_WithTheBody($fullName, $folder, $body) {
        $this->class->givenTheClass_Extending_In_WithTheBody($fullName, '\watoki\curir\Resource', $folder, "
            public function getDirectory() {
                return '$folder';
            }" . $body);
        $this->delivery->givenTheTargetIsTheClass($fullName);
    }

    private function givenTheTargetClass_In_WithTheBody($fullName, $folder, $body) {
        $this->class->givenTheClass_In_WithTheBody($fullName, $folder, $body);
        $this->delivery->givenTheTargetIsTheClass($fullName);
    }

    private function givenTheDefaultRendererIs($class) {
        $this->factory->setSingleton(Renderer::RENDERER, $this->factory->getInstance($class));
    }

} 