<?php
namespace spec\watoki\curir;

use spec\watoki\curir\fixtures\ClassesFixture;
use spec\watoki\curir\fixtures\WebDeliveryFixture;
use spec\watoki\curir\fixtures\WebRequestBuilderFixture;
use spec\watoki\stores\FileStoreFixture;
use watoki\curir\delivery\WebResponse;
use watoki\scrut\Specification;

/**
 * If the target of the Request is a file, a Response is created with its content and the correct MIME type.
 *
 * @property ClassesFixture class <-
 * @property FileStoreFixture file <-
 * @property WebRequestBuilderFixture request <-
 * @property WebDeliveryFixture delivery <-
 */
class DeliverStaticFilesTest extends Specification {

    protected function background() {
        $this->class->givenTheContainer_In('just\SomeResource', 'some/folder');
        $this->delivery->givenTheTargetIsTheRespondingClass('just\SomeResource');
    }

    function testRespondWithFileContentAndMimeType() {
        $this->file->givenAFile_WithContent('some/folder/some/static/file', 'Hello World');
        $this->request->givenTheTargetPathIs('static/file');
        $this->request->givenTheHeader_Is('HTTP_ACCEPT', 'text/plain');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseBodyShouldBe('Hello World');
        $this->delivery->thenTheResponseHeader_ShouldBe(WebResponse::HEADER_CONTENT_TYPE, 'text/plain');
    }

    function testFileWithExtension() {
        $this->file->givenAFile_WithContent('some/folder/some/static/file.txt', 'Hello World');
        $this->request->givenTheTargetPathIs('static/file.txt');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseBodyShouldBe('Hello World');
        $this->delivery->thenTheResponseHeader_ShouldBe(WebResponse::HEADER_CONTENT_TYPE, 'text/plain');
    }

    function testEdgeCaseFileNameStartingWithDot() {
        $this->file->givenAFile_WithContent('some/folder/some/static/.file', 'Hello World');
        $this->request->givenTheTargetPathIs('static/.file');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseBodyShouldBe('Hello World');
        $this->delivery->thenTheResponseHeader_ShouldBe(WebResponse::HEADER_CONTENT_TYPE, null);
    }

    function testEdgeCaseFileNameWithTwoDots() {
        $this->file->givenAFile_WithContent('some/folder/some/static/file.foo.bar', 'Hello World');
        $this->request->givenTheTargetPathIs('static/file.foo.bar');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseBodyShouldBe('Hello World');
    }

}