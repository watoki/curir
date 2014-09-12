<?php
namespace spec\watoki\curir;

use spec\watoki\curir\fixtures\ClassesFixture;
use spec\watoki\curir\fixtures\WebDeliveryFixture;
use spec\watoki\curir\fixtures\WebRequestBuilderFixture;
use spec\watoki\stores\FileStoreFixture;
use watoki\curir\WebResponse;
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

    protected function background() {
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
            public function renderFoo($template) {
                return $template;
            }
        ');
    }

    function testMethodNotExisting() {
        $this->givenTheTargetResource_In_WithTheBody('some\EmptyClass', 'some/folder', '');
        $this->request->givenTheMethodArgumentIs('notExisting');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseStatusShouldBe(WebResponse::STATUS_METHOD_NOT_ALLOWED);
        $this->delivery->thenTheResponseBodyShouldBe('Method [notExisting] is not allowed here.');
    }

    function testRedirect() {
        $this->givenTheTargetResource_In_WithTheBody('Redirect', 'folder', '
            public function doThis() {
                return \watoki\curir\responder\Redirecter::fromString("http://example.com");
            }
        ');
        $this->request->givenTheMethodArgumentIs('this');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseBodyShouldBe('');
        $this->delivery->thenTheResponseStatusShouldBe(WebResponse::STATUS_SEE_OTHER);
        $this->delivery->thenTheResponseHeader_ShouldBe(WebResponse::HEADER_LOCATION, 'http://example.com');
    }

    function testRespondInAcceptedFormat() {
        $this->givenTheTargetResource_In_WithTheBody('RespondInAcceptedFormat', 'folder', '
            public function doThis() {
                return new SomePresenter("Hello World");
            }
        ');
        $this->request->givenTheMethodArgumentIs('this');

        $this->request->givenTheTargetPathIs('this.big');
        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseBodyShouldBe('HELLO WORLD');

        $this->request->givenTheTargetPathIs('this.small');
        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseBodyShouldBe('hello world');

        $this->request->givenTheTargetPathIs('this.txt');
        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseBodyShouldBe('Hello World!');
        $this->delivery->thenTheResponseHeader_ShouldBe(WebResponse::HEADER_CONTENT_TYPE, 'text/plain');
    }

    function testRenderMethodMissing() {
        $this->givenTheTargetResource_In_WithTheBody('RenderMethodMissing', 'folder', '
            public function doThis() {
                return new SomePresenter("Hello World");
            }
        ');
        $this->request->givenTheMethodArgumentIs('this');
        $this->request->givenTheTargetPathIs('something.unknown');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseStatusShouldBe(WebResponse::STATUS_NOT_ACCEPTABLE);
        $this->delivery->thenTheResponseBodyShouldBe('Could not render the resource in an accepted format.');
    }

    function testRenderTemplate() {
        $this->givenTheTargetResource_In_WithTheBody('RenderTemplateResource', 'some/folder', '
            public function doThis() {
                return new SomePresenter();
            }
        ');
        $this->request->givenTheMethodArgumentIs('this');
        $this->request->givenTheTargetPathIs('something.foo');
        $this->file->givenAFile_WithContent('some/folder/renderTemplate.foo', 'Hello World');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseBodyShouldBe('Hello World');
    }

    function testTemplateFileDoesNotExist() {
        $this->givenTheTargetResource_In_WithTheBody('NoTemplateResource', 'that/folder', '
            public function doThis() {
                return new SomePresenter();
            }
        ');
        $this->request->givenTheMethodArgumentIs('this');
        $this->request->givenTheTargetPathIs('something.foo');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseStatusShouldBe(WebResponse::STATUS_SERVER_ERROR);
        $this->delivery->thenTheResponseBodyShouldBe('Exception: Could not find template [noTemplate.foo] for [NoTemplateResource]');
    }

    private function givenTheTargetResource_In_WithTheBody($fullName, $folder, $body) {
        $this->class->givenTheClass_Extending_In_WithTheBody($fullName, '\watoki\curir\Resource', $folder, "
            public function getDirectory() {
                return '$folder';
            }" . $body);
        $this->delivery->givenTheTargetIsTheClass($fullName);
    }

} 