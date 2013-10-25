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
            return new \TestPresenter($this->getName());
        }');
        $this->resource->givenTheRequestHasTheTarget('Anything');
        $this->resource->givenTheContainer('DynamicRoute');

        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('"Anything"');
    }

    function testRouteWithPlaceholderContainer() {
        $this->resource->givenTheRequestHasTheTarget('ThisOne/Real');
        $this->resource->givenTheDynamicResource_In_WithTheBody('Real', 'PlaceholderRoute/_Placeholder', 'function doGet() {
            return new \TestPresenter($this->getParent()->getName());
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
            return new \TestPresenter($this->getParent()->getName());
        }');
        $this->resource->givenTheContainer('PlaceholderContainer');

        $this->resource->givenTheRequestHasTheTarget('someWhere/over/TheRainbow');
        $this->resource->whenISendTheRequestToThatResource();

        $this->resource->thenTheResponseShouldHaveTheBody('"over"');
    }

    function testPlaceholderSetParameter() {
        $this->resource->givenTheDynamicResource_In_WithTheBody('_Where', 'SetParameter', '
            function doGet($place) {
                return new \TestPresenter($place);
            }
            function getPlaceholderKey() {
                return "place";
            }');
        $this->resource->givenTheContainer('SetParameter');

        $this->resource->givenTheRequestHasTheTarget('here');
        $this->resource->whenISendTheRequestToThatResource();

        $this->resource->thenTheResponseShouldHaveTheBody('"here"');
    }

    function testPlaceholderDoesNotOverwriteParameter() {
        $this->resource->givenTheDynamicResource_In_WithTheBody('_Where', 'DoesNotOverwriteParameter', '
            function doGet($place) {
                return new \TestPresenter($place);
            }
            function getPlaceholderKey() {
                return "place";
            }');
        $this->resource->givenTheContainer('DoesNotOverwriteParameter');
        $this->resource->givenTheRequestParameter_Is('place', 'there');

        $this->resource->givenTheRequestHasTheTarget('here');
        $this->resource->whenISendTheRequestToThatResource();

        $this->resource->thenTheResponseShouldHaveTheBody('"there"');
    }

} 