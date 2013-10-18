<?php
namespace spec\watoki\curir\resources;

use spec\watoki\curir\fixtures\ResourceFixture;
use watoki\scrut\Specification;

/**
 * @property ResourceFixture resource <-
 */
class DynamicResourceParameterInflationTest extends Specification {

    function testSimpleParameters() {
        $this->resource->givenTheDynamicResource_WithTheBody('SimpleParameters', 'function doGet($one, $two) {
            return new \watoki\curir\responder\DefaultPresenter(array($one, $two));
        }');
        $this->resource->givenTheRequestParameter_Is('one', 'uno');
        $this->resource->givenTheRequestParameter_Is('two', 'dos');

        $this->resource->whenIRequestAResponseFromThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('["uno","dos"]');
    }

    function testMissingParameter() {
        $this->resource->givenTheDynamicResource_WithTheBody('MissingParameter', 'function doGet($default = "yay", $notThere) {}');
        $this->resource->whenITryToRequestAResponseFromThatResource();
        $this->resource->thenTheRequestShouldFailWith('Missing parameter [notThere] for method [doGet]');
    }

    function testInflateParameters() {
        $this->markTestIncomplete();

        $this->resource->givenTheDynamicResource_WithTheBody('InflateParameters', 'function doGet(\DateTime $d) {
            return new \watoki\curir\responder\DefaultPresenter($d);
        }');
        $this->resource->givenTheRequestParameter_Is('d', '2000-01-01');
        $this->resource->givenIRequestTheFormat('json');

        $this->resource->whenIRequestAResponseFromThatResource();

        $this->resource->thenTheResponseShouldHaveTheBody('');
    }

} 