<?php
namespace spec\watoki\curir\resources;
 
use spec\watoki\curir\fixtures\FileFixture;
use spec\watoki\curir\fixtures\ResourceFixture;
use watoki\scrut\Specification;

/**
 * @property FileFixture file <-
 * @property ResourceFixture resource <-
 */
class StaticResourceTest extends Specification {

    function testRespondWithFileContentAndMimeType() {
        $this->file->givenTheFile_WithTheContent('someFile.txt', 'Hello World');
        $this->resource->givenTheStaticResourceFor('someFile.txt');

        $this->resource->whenISendTheRequestToThatResource();

        $this->resource->thenTheResponseShouldHaveTheBody('Hello World');
        $this->resource->thenTheResponseShouldHaveTheContentType('text/plain');
    }

    function testResourceWithoutExtension() {
        $this->file->givenTheFile_WithTheContent('someFile', 'Hello World');
        $this->resource->givenTheStaticResourceFor('someFile');

        $this->resource->whenISendTheRequestToThatResource();

        $this->resource->thenTheResponseShouldHaveTheBody('Hello World');
        $this->resource->thenTheResponseShouldHaveTheContentType('text/plain');
    }

    function testResourceStartingWithDot() {
        $this->file->givenTheFile_WithTheContent('.someFile', 'Hello World');
        $this->resource->givenTheStaticResourceFor('.someFile');

        $this->resource->whenISendTheRequestToThatResource();

        $this->resource->thenTheResponseShouldHaveTheBody('Hello World');
        $this->resource->thenTheResponseShouldHaveTheContentType('text/plain');
    }

}
 