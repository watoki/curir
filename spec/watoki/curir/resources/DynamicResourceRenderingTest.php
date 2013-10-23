<?php
namespace spec\watoki\curir\resources;
 
use spec\watoki\curir\fixtures\FileFixture;
use spec\watoki\curir\fixtures\ResourceFixture;
use watoki\scrut\Specification;

/**
 * @property ResourceFixture resource <-
 * @property FileFixture file <-
 */
class DynamicResourceRenderingTest extends Specification {

    function testMethodNotExisting() {
        $this->resource->givenTheDynamicResource('NoMethods');
        $this->resource->whenITryToSendTheRequestToThatResource();
        $this->resource->thenTheRequestShouldFailWith('Method NoMethodsResource::doGet() does not exist');
    }

    function testRenderFormatNotRegistered() {
        $this->resource->givenIRequestTheFormat('nothing');
        $this->resource->givenTheDynamicResource_WithTheBody('NoFormat', 'function doGet() {
            return new \watoki\curir\responder\Presenter();
        }');
        $this->resource->whenITryToSendTheRequestToThatResource();
        $this->resource->thenTheRequestShouldFailWith("renderNothing() does not exist");
    }

    function testRedirectRequest() {
        $this->resource->givenTheDynamicResource_WithTheBody('RedirectMe', 'function doGet() {
            return new \watoki\curir\responder\Redirecter(\watoki\curir\http\Path::parse("redirect/me/here"));
        }');
        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenIShouldBeRedirectedTo('redirect/me/here');
    }

    function testRenderModel() {
        $this->resource->givenTheDynamicResource_WithTheBody('RenderMe', 'function doGet() {
            return new \TestPresenter(array("foo" => "Hello", "bar" => "World"));
        }');
        $this->resource->givenIRequestTheFormat('json');
        $this->resource->whenISendTheRequestToThatResource();

        $this->resource->thenTheResponseShouldHaveTheBody('{"foo":"Hello","bar":"World"}');
    }

    function testRenderTemplate() {
        $this->resource->givenTheDynamicResource_WithTheBody('RenderTemplate', 'function doGet() {
            return new \TestPresenter(array("foo" => "Hello", "bar" => "World"));
        }');
        $this->resource->givenIRequestTheFormat('test');
        $this->file->givenTheFile_WithTheContent('renderTemplate.test', '%foo% %bar%');

        $this->resource->whenISendTheRequestToThatResource();

        $this->resource->thenTheResponseShouldHaveTheBody('Hello World');
    }

    function testNoFormatGiven() {
        $this->resource->givenTheDynamicResource_WithTheBody('DefaultFormat', 'function doGet() {
            return new \TestPresenter(array("foo" => "bar"));
        }');
        $this->file->givenTheFile_WithTheContent('defaultFormat.html', 'Here');

        $this->resource->whenISendTheRequestToThatResource();

        $this->resource->thenTheResponseShouldHaveTheBody('Here');
    }

    function testCaseInsensitivity() {
        $this->resource->givenTheDynamicResource_WithTheBody('CaseInsensitivity', 'function doGet() {
            return new \TestPresenter(array("foo" => "bar"));
        }');
        $this->file->givenTheFile_WithTheContent('CaseInsEnsitIvity.HTML', 'There');

        $this->resource->whenISendTheRequestToThatResource();

        $this->resource->thenTheResponseShouldHaveTheBody('There');
    }

}
 