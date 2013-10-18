<?php
namespace spec\watoki\curir\resources;
 
use spec\watoki\curir\fixtures\ResourceFixture;
use watoki\scrut\Specification;

/**
 * @property ResourceFixture resource <-
 */
class DynamicResourceTest extends Specification {

    function testMethodNotExisting() {
        $this->resource->givenTheDynamicResource('NoMethods');
        $this->resource->whenITryToRequestAResponseFromThatResource();
        $this->resource->thenTheRequestShouldFailWith('Method NoMethods::doGet() does not exist');
    }

    function testRenderFormatNotRegistered() {
        $this->resource->givenTheRequestHasTheFormat('nothing');
        $this->resource->givenTheDynamicResource_WithTheBody('NoFormat', 'function doGet() {return array();}');
        $this->resource->whenITryToRequestAResponseFromThatResource();
        $this->resource->thenTheRequestShouldFailWith('No Renderer set for format [nothing].');
    }

    function testInvokeMethodAndRenderModel() {
        $this->markTestIncomplete();
    }

    function testUnSerializeParameters() {
        $this->markTestIncomplete();
    }

    function testRedirectRequest() {
        $this->markTestIncomplete();
    }

}
 