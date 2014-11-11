<?php
namespace spec\watoki\curir;

use spec\watoki\curir\fixtures\ClassesFixture;
use spec\watoki\curir\fixtures\WebDeliveryFixture;
use spec\watoki\curir\fixtures\WebRequestBuilderFixture;
use spec\watoki\stores\FileStoreFixture;
use watoki\curir\delivery\WebRequest;
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

    public function background() {
        $this->class->givenTheContainer_In('just\some\IndexResource', 'some/folder/some');
        $this->delivery->givenTheTargetIsTheRespondingClass('just\some\IndexResource');
    }

    function testRespondWithFileContentAndMimeType() {
        $this->file->givenAFile_WithContent('some/folder/some/static/file', 'Hello World');
        $this->request->givenTheTargetPathIs('static/file');
        $this->request->givenTheHeader_Is(WebRequest::HEADER_ACCEPT, 'text/plain');

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

    function testIndexFile() {
        $this->file->givenAFile_WithContent('some/folder/some/static/index', 'Hello Index');
        $this->request->givenTheTargetPathIs('static/');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseBodyShouldBe('Hello Index');
    }

    function testFileAndClassWithSameName() {
        $this->class->givenTheClass_In_WithTheBody('just\some\DoubleResource', 'some/folder/some', '
            public function doGet() {
                return new \watoki\curir\delivery\WebResponse("But me");
            }');
        $this->file->givenAFile_WithContent('some/folder/some/double', 'Not me');
        $this->request->givenTheTargetPathIs('double');
        $this->request->givenTheRequestMethodIs('get');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseBodyShouldBe('But me');
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