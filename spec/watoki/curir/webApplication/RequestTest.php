<?php
namespace spec\watoki\curir\webApplication;

use spec\watoki\curir\fixtures\WebApplicationFixture;
use watoki\scrut\Specification;

/**
 * @property WebApplicationFixture app <-
 */
class RequestTest extends Specification {

    function testNormalRequest() {
        $this->app->givenTheMethodIs('GET');
        $this->app->givenTheRequestIs('one/two/three.txt');

        $this->app->whenIRunTheWebApplicationUnderTheUrl('http://example.com');

        $this->app->thenTheTargetShouldBe('one/two/three');
        $this->app->thenTheFormatShouldBe('txt');
        $this->app->thenTheMethodShouldBe('get');
    }

    function testDefaultFormat() {
        $this->app->givenTheRequestIs('one/two');

        $this->app->whenIRunTheWebApplicationUnderTheUrl('http://example.com');

        $this->app->thenTheFormatShouldBe(null);
    }

    function testParameters() {
        $this->app->givenTheTheRequestParameter_Is('one', 'two');

        $this->app->whenIRunTheWebApplicationUnderTheUrl('http://example.com');

        $this->app->thenTheParameter_ShouldBe('one', 'two');
    }

    function testOverwriteMethod() {
        $this->app->givenTheMethodIs('POST');
        $this->app->givenTheTheRequestParameter_Is('method', 'somethingElse');

        $this->app->whenIRunTheWebApplicationUnderTheUrl('http://example.com');

        $this->app->thenTheMethodShouldBe('somethingElse');
    }

    function testHeaders() {
        $this->app->givenRequestTheHeader_Is('HTTP_ACCEPT', '*/*');
        $this->app->givenRequestTheHeader_Is('HTTP_PRAGMA', null);

        $this->app->whenIRunTheWebApplicationUnderTheUrl('http://example.com');

        $this->app->thenTheHeader_ShouldBe('Accept', '*/*');
        $this->app->thenThereShouldBeNoHeader('Pragma');
    }

} 