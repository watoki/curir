<?php
namespace spec\watoki\curir\resources;

use spec\watoki\curir\fixtures\ResourceFixture;
use watoki\curir\http\Response;
use watoki\scrut\Specification;

/**
 * @property ResourceFixture resource <-
 */
class DynamicResourceParameterInflationTest extends Specification {

    protected function background() {
        $this->resource->givenIRequestTheFormat('json');
    }

    function testSimpleParameters() {
        $this->resource->givenTheDynamicResource_WithTheBody('SimpleParameters', 'function doGet($one, $two) {
            return new \TestPresenter($this, array($one, $two));
        }');
        $this->resource->givenTheRequestParameter_Is('one', 'uno');
        $this->resource->givenTheRequestParameter_Is('two', 'dos');

        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('["uno","dos"]');
    }

    function testMissingParameter() {
        $this->resource->givenTheDynamicResource_WithTheBody('MissingParameter', 'function doGet($notThere, $default = "yay") {}');
        $this->resource->whenITryToSendTheRequestToThatResource();
        $this->resource->thenTheRequestShouldFailWith('Cannot inject parameter [notThere] of [MissingParameterResource::doGet]');
        $this->resource->thenTheRequestShouldReturnTheStatus(Response::STATUS_BAD_REQUEST);
    }

    function testInflateParameters() {
        $this->resource->givenTheDynamicResource_WithTheBody('InflateParameters', 'function doGet(\DateTime $d) {
            return new \TestPresenter($this, $d->format("c"));
        }');
        $this->resource->givenTheRequestParameter_Is('d', '2011-12-13 14:15:16 UTC');
        $this->resource->givenIRequestTheFormat('json');

        $this->resource->whenISendTheRequestToThatResource();

        $this->resource->thenTheResponseShouldHaveTheBody('"2011-12-13T14:15:16+00:00"');
    }

    public function testDoNotInflateNullAsDateTime() {
        $this->resource->givenTheDynamicResource_WithTheBody('DoNotInflateNullAsDateTime', '
            function doGet(\DateTime $d = null) {
                return new \TestPresenter($this, $d);
            }');
        $this->resource->givenTheRequestParameter_Is('d', null);
        $this->resource->givenIRequestTheFormat('json');

        $this->resource->whenISendTheRequestToThatResource();

        $this->resource->thenTheResponseShouldHaveTheBody('null');
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
                return new \TestPresenter($this, array($int, $bool, $float, $string, $date->format("c")));
            }');
        $this->resource->givenTheRequestParameter_Is('int', '1');
        $this->resource->givenTheRequestParameter_Is('bool', 'false');
        $this->resource->givenTheRequestParameter_Is('float', '3.1415');
        $this->resource->givenTheRequestParameter_Is('string', 'test');
        $this->resource->givenTheRequestParameter_Is('date', '2000-01-01 UTC');

        $this->resource->whenISendTheRequestToThatResource();

        $this->resource->thenTheResponseShouldHaveTheBody('[1,false,3.1415,"test","2000-01-01T00:00:00+00:00"]');
    }

    function testInvalidTypeHint() {
        $this->resource->givenTheDynamicResource_WithTheBody('InvalidTypeHint', '
            /**
             * @param invalid $one
             */
            function doGet($one) {
                return new \TestPresenter($this, $one);
            }');
        $this->resource->givenTheRequestParameter_Is('one', 'not');

        $this->resource->whenISendTheRequestToThatResource();

        $this->resource->thenTheResponseShouldHaveTheBody('"not"');
    }

    function testNoTypeHint() {
        $this->resource->givenTheDynamicResource_WithTheBody('NoTypeHints', '
            function doGet($one, $two) {
                return new \TestPresenter($this, array($one, $two));
            }');
        $this->resource->givenTheRequestParameter_Is('one', 'foo');
        $this->resource->givenTheRequestParameter_Is('two', array('foo' => 'bar'));

        $this->resource->whenISendTheRequestToThatResource();

        $this->resource->thenTheResponseShouldHaveTheBody('["foo",{"foo":"bar"}]');
    }

    function testInjectRequestAsParameter() {
        $this->resource->givenTheDynamicResource_WithTheBody('InjectRequestParameter', '
            function doGet($request) {
                return $request->getBody();
            }
        ');
        $this->resource->givenTheRequestHasTheBody('Some body');
        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('Some body');
    }

    function testDoNotOverwriteRequestParameter() {
        $this->resource->givenTheDynamicResource_WithTheBody('DoNotOverwriteRequestParameter', '
            function doGet($request) {
                return $request;
            }
        ');
        $this->resource->givenTheRequestParameter_Is('request', 'Some body');
        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('Some body');
    }

} 