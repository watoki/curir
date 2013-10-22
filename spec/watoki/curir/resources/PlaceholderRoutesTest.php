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
            return new \watoki\curir\responder\DefaultPresenter($this->getUrl()->getPath()->last());
        }');
        $this->resource->givenTheRequestHasTheTarget('Anything');
        $this->resource->givenTheContainer('DynamicRoute');

        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('"Anything"');
    }

    function testRouteWithPlaceholderContainer() {
        $this->resource->givenTheRequestHasTheTarget('ThisOne/Real');
        $this->resource->givenTheDynamicResource_In_WithTheBody('Real', 'PlaceholderRoute/_Placeholder', 'function doGet() {
            return new \watoki\curir\responder\DefaultPresenter($this->getUrl()->getPath()->get(-2));
        }');
        $this->resource->givenTheContainer_In('_Placeholder', 'PlaceholderRoute');
        $this->resource->givenTheContainer('PlaceholderRoute');

        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('"ThisOne"');
    }

} 