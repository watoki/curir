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
        $this->markTestIncomplete();
    }

    function testInvokeMethodAndRenderModel() {
        $this->markTestIncomplete();
    }

}
 