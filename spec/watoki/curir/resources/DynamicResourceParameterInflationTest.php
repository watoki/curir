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
        $this->resource->givenTheDynamicResource_WithTheBody('InflateParameters', 'function doGet(\DateTime $d) {
            return new \watoki\curir\responder\DefaultPresenter($d);
        }');
        $this->resource->givenTheRequestParameter_Is('d', '2011-12-13 14:15:16 UTC');
        $this->resource->givenIRequestTheFormat('json');

        $this->resource->whenIRequestAResponseFromThatResource();

        $this->resource->thenTheResponseShouldHaveTheBody('{"date":"2011-12-13 14:15:16","timezone_type":3,"timezone":"UTC"}');
    }

    function testTypeHintInComment() {
        $this->resource->givenTheDynamicResource_WithTheBody('TypeHints', '
            /**
             * @param int $int
             * @param bool $bool
             * @param float $float
             * @param string $string
             * @param \DateTime $date
             */
            function doGet($int, $bool, $float, $string, $date) {
                return new \watoki\curir\responder\DefaultPresenter(array($int, $bool, $float, $string, $date));
            }');
        $this->resource->givenTheRequestParameter_Is('int', '1');
        $this->resource->givenTheRequestParameter_Is('bool', 'false');
        $this->resource->givenTheRequestParameter_Is('float', '3.1415');
        $this->resource->givenTheRequestParameter_Is('string', 'test');
        $this->resource->givenTheRequestParameter_Is('date', '2000-01-01 UTC');

        $this->resource->whenIRequestAResponseFromThatResource();

        $this->resource->thenTheResponseShouldHaveTheBody('[1,false,3.1415,"test",{"date":"2000-01-01 00:00:00","timezone_type":3,"timezone":"UTC"}]');
    }

    function testInvalidTypeHint() {
        $this->resource->givenTheDynamicResource_WithTheBody('InvalidTypeHint', '
            /**
             * @param invalid $one
             */
            function doGet($one) {}');
        $this->resource->givenTheRequestParameter_Is('one', 'not');

        $this->resource->whenITryToRequestAResponseFromThatResource();
        $this->resource->thenTheRequestShouldFailWith('Error while inflating parameter [one] of [InvalidTypeHint::doGet()]: Could not find inflater for type [invalid]');
    }

} 