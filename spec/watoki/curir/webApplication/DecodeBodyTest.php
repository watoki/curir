<?php
namespace spec\watoki\curir\webApplication;

use spec\watoki\curir\fixtures\WebApplicationFixture;
use watoki\scrut\Specification;

/**
 * @property WebApplicationFixture app <-
 */
class DecodeBodyTest extends Specification {

    function testUndefinedContentType() {
        $this->app->givenTheMethodIs('put');
        $this->app->whenIRunTheWebApplication();
        $this->app->thenTheParametersShouldBeEmpty();
    }

    function testDecodeFormData() {
        $this->app->givenTheMethodIs('put');
        $this->app->givenTheRequestContentTypeIs('application/x-www-form-urlencoded');
        $this->app->givenTheRequestBodyIs('a[]=1&a[]=2&a[b]=4');

        $this->app->whenIRunTheWebApplication();
        $this->app->thenTheParameter_ShouldBe('a', array(0 => '1', 1 => '2', 'b' => '4'));
    }

    function testDecodeEmptyFormData() {
        $this->app->givenTheMethodIs('put');
        $this->app->givenTheRequestContentTypeIs('application/x-www-form-urlencoded');
        $this->app->givenTheRequestBodyIs('');

        $this->app->whenIRunTheWebApplication();
        $this->app->thenTheParametersShouldBeEmpty();
    }

    function testDecodeJson() {
        $this->app->givenTheMethodIs('put');
        $this->app->givenTheRequestContentTypeIs('application/json');
        $this->app->givenTheRequestBodyIs('{"a":["c", "d"],"b":1}');

        $this->app->whenIRunTheWebApplication();
        $this->app->thenTheParameter_ShouldBe('a', array('c', 'd'));
        $this->app->thenTheParameter_ShouldBe('b', 1);
    }

    function testDecodeEmptyJson() {
        $this->app->givenTheMethodIs('put');
        $this->app->givenTheRequestContentTypeIs('application/json');
        $this->app->givenTheRequestBodyIs('');

        $this->app->whenIRunTheWebApplication();
        $this->app->thenTheParametersShouldBeEmpty();
    }

    function testDecodeInvalidJson() {
        $this->app->givenTheMethodIs('put');
        $this->app->givenTheRequestContentTypeIs('application/json');
        $this->app->givenTheRequestBodyIs('not json');

        $this->app->whenIRunTheWebApplication();
        $this->app->thenTheParametersShouldBeEmpty();
    }

    function testOverwriteQueryParameters() {
        $this->app->givenTheMethodIs('put');
        $this->app->givenTheRequestContentTypeIs('application/json');
        $this->app->givenTheRequestBodyIs('{"a":["c", "d"]}');
        $this->app->givenTheTheRequestParameter_Is('a', array(1, 2));
        $this->app->givenTheTheRequestParameter_Is('b', 'foo');

        $this->app->whenIRunTheWebApplication();
        $this->app->thenTheParameter_ShouldBe('a', array('c', 'd'));
        $this->app->thenTheParameter_ShouldBe('b', 'foo');
    }

} 