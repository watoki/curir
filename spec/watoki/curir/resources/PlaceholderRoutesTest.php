<?php
namespace spec\watoki\curir\resources;

use spec\watoki\curir\fixtures\ResourceFixture;
use watoki\scrut\Specification;

/**
 * @property ResourceFixture resource <-
 */
class PlaceholderRoutesTest extends Specification {

    function testFindPlaceholderResource() {
        $this->resource->givenTheDynamicResource_In_WithTheBody('_Something', 'DynamicRoute', 'function doGet() {
            return new \watoki\curir\responder\DefaultPresenter($this->getName());
        }');
        $this->resource->givenTheRequestHasTheTarget('Anything');
        $this->resource->givenTheContainer('DynamicRoute');

        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('"Anything"');
    }

} 