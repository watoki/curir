<?php
namespace spec\watoki\curir\resources;
 
use spec\watoki\curir\fixtures\FileFixture;
use spec\watoki\curir\fixtures\ResourceFixture;
use watoki\scrut\Specification;

/**
 * @property ResourceFixture resource <-
 * @property FileFixture file <-
 */
class DynamicResourceTest extends Specification {

    function testMethodNotExisting() {
        $this->resource->givenTheDynamicResource('NoMethods');
        $this->resource->whenITryToRequestAResponseFromThatResource();
        $this->resource->thenTheRequestShouldFailWith('Method NoMethods::doGet() does not exist');
    }

    function testRenderFormatNotRegistered() {
        $this->resource->givenIRequestTheFormat('nothing');
        $this->resource->givenTheDynamicResource_WithTheBody('NoFormat', 'function doGet() {
            return new \watoki\curir\responder\Presenter();
        }');
        $this->resource->whenITryToRequestAResponseFromThatResource();
        $this->resource->thenTheRequestShouldFailWith('No Renderer set for format [nothing].');
    }

    function testRedirectRequest() {
        $this->resource->givenTheDynamicResource_WithTheBody('RedirectMe', 'function doGet() {
            return new \watoki\curir\responder\Redirecter(\watoki\curir\http\Path::parse("redirect/me/here"));
        }');
        $this->resource->whenIRequestAResponseFromThatResource();
        $this->resource->thenIShouldBeRedirectedTo('redirect/me/here');
    }

    function testRenderModel() {
        $this->resource->givenTheDynamicResource_WithTheBody('RenderMe', 'function doGet() {
            return new \watoki\curir\responder\Presenter(array("foo" => "Hello", "bar" => "World"));
        }');
        $this->resource->givenIRequestTheFormat('json');
        $this->resource->whenIRequestAResponseFromThatResource();

        $this->resource->thenTheResponseShouldHaveTheBody('{"foo":"Hello","bar":"World"}');
    }

    function testRenderTemplate() {
        $this->resource->givenATestRenderer('TestRenderer');
        $this->resource->givenTheDynamicResource_WithTheBody('RenderTemplate', 'function doGet() {
            $presenter = new \watoki\curir\responder\Presenter(array("foo" => "Hello", "bar" => "World"));
            $presenter->getRendererFactory()->setRenderer("test", new TestRenderer());
            return $presenter;
        }');
        $this->resource->givenIRequestTheFormat('test');
        $this->file->givenTheFile_WithTheContent('renderTemplate.test', '%foo% %bar%');

        $this->resource->whenIRequestAResponseFromThatResource();

        $this->resource->thenTheResponseShouldHaveTheBody('Hello World');
    }

    function testUnSerializeParameters() {
        $this->markTestIncomplete();
    }

}
 