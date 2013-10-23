<?php
namespace spec\watoki\curir\resources;

use spec\watoki\curir\fixtures\FileFixture;
use spec\watoki\curir\fixtures\ResourceFixture;
use watoki\scrut\Specification;

/**
 * @property ResourceFixture resource <-
 * @property FileFixture file <-
 */
class PlaceholderRoutesTest extends Specification {

    protected function background() {
        $this->resource->givenIRequestTheFormat('json');
    }

    function testFindPlaceholderResource() {
        $this->resource->givenTheDynamicResource_In_WithTheBody('_Something', 'DynamicRoute', 'function doGet() {
            return new \watoki\curir\responder\DefaultPresenter($this->getName());
        }');
        $this->resource->givenTheRequestHasTheTarget('Anything');
        $this->resource->givenTheContainer('DynamicRoute');

        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('"Anything"');
    }

    function testRouteWithPlaceholderContainer() {
        $this->resource->givenTheRequestHasTheTarget('ThisOne/Real');
        $this->resource->givenTheDynamicResource_In_WithTheBody('Real', 'PlaceholderRoute/_Placeholder', 'function doGet() {
            return new \watoki\curir\responder\DefaultPresenter($this->getParent()->getName());
        }');
        $this->resource->givenTheContainer_In('_Placeholder', 'PlaceholderRoute');
        $this->resource->givenTheContainer('PlaceholderRoute');

        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('"ThisOne"');
    }

    function testStaticPlaceholderResource() {
        $this->file->givenTheFile_WithTheContent('PlaceholderResource/some/where/_here.txt', 'Hello World');
        $this->resource->givenTheContainer('PlaceholderResource');

        $this->resource->givenTheRequestHasTheTarget('some/where/overTheRainbow.txt');
        $this->resource->whenISendTheRequestToThatResource();

        $this->resource->thenTheResponseShouldHaveTheBody('Hello World');
    }

    function testStaticPlaceholderContainer() {
        $this->resource->givenTheDynamicResource_In_WithTheBody('TheRainbow', 'PlaceholderContainer/someWhere/_under', 'function doGet() {
            return new \watoki\curir\responder\DefaultPresenter($this->getParent()->getName());
        }');
        $this->resource->givenTheContainer('PlaceholderContainer');

        $this->resource->givenTheRequestHasTheTarget('someWhere/over/TheRainbow');
        $this->resource->whenISendTheRequestToThatResource();

        $this->resource->thenTheResponseShouldHaveTheBody('"over"');
    }

} 