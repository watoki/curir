<?php
namespace spec\watoki\curir\webApplication;

use spec\watoki\curir\fixtures\WebApplicationFixture;
use watoki\curir\http\error\HttpError;
use watoki\curir\http\Response;
use watoki\scrut\Specification;

/**
 * @property WebApplicationFixture app <-
 */
class RequestTest extends Specification {

    function testNormalRequest() {
        $this->app->givenTheMethodIs('GET');
        $this->app->givenTheRequestIs('one/two/three.txt');

        $this->app->whenIRunTheWebApplication();

        $this->app->thenTheTargetShouldBe('one/two/three');
        $this->app->thenTheFormatsShouldBe(array('txt'));
        $this->app->thenTheMethodShouldBe('get');
    }

    function testWithoutExtensionFormat() {
        $this->app->givenTheRequestIs('one/two');

        $this->app->whenIRunTheWebApplication();

        $this->app->thenTheFormatsShouldBe(array());
    }

    function testParameters() {
        $this->app->givenTheTheRequestParameter_Is('one', 'two');

        $this->app->whenIRunTheWebApplication();

        $this->app->thenTheParameter_ShouldBe('one', 'two');
    }

    function testOverwriteMethod() {
        $this->app->givenTheMethodIs('POST');
        $this->app->givenTheTheRequestParameter_Is('method', 'somethingElse');

        $this->app->whenIRunTheWebApplication();

        $this->app->thenTheMethodShouldBe('somethingElse');
    }

    function testHeaders() {
        $this->app->givenRequestTheHeader_Is('HTTP_ACCEPT', '*/*');
        $this->app->givenRequestTheHeader_Is('HTTP_PRAGMA', null);

        $this->app->whenIRunTheWebApplication();

        $this->app->thenTheHeader_ShouldBe('Accept', '*/*');
        $this->app->thenThereShouldBeNoHeader('Pragma');
    }

    function testTargetWithTwoDots() {
        $this->app->givenTheMethodIs('GET');
        $this->app->givenTheRequestIs('one/two/three.min.txt');

        $this->app->whenIRunTheWebApplication();

        $this->app->thenTheTargetShouldBe('one/two/three.min');
        $this->app->thenTheFormatsShouldBe(array('txt'));
        $this->app->thenTheMethodShouldBe('get');
    }

    function testTargetWithTrailingSlash() {
        $this->app->givenTheMethodIs('GET');
        $this->app->givenTheRequestIs('one/two/three/');

        $this->app->whenIRunTheWebApplication();

        $this->app->thenTheTargetShouldBe('one/two/three');
        $this->app->thenTheMethodShouldBe('get');
    }

    function testThrownException() {
        $this->app->givenTheRequestIs('something.html');
        $this->app->givenTheTargetResourceThrowsTheException(new \Exception('General Exception'));

        $this->app->whenIRunTheWebApplication();

        $this->app->thenTheResponseShouldHaveTheStatus(Response::STATUS_SERVER_ERROR);
        $this->app->thenTheResponseBodyShouldContain(Response::STATUS_SERVER_ERROR);
        $this->app->thenTheResponseBodyShouldContain('General Exception');
        $this->app->thenTheResponseBodyShouldNotContain('$');
    }

    function testThrownExceptionWithNonHtmlFormat() {
        $this->app->givenTheRequestIs('something.txt');
        $this->app->givenTheTargetResourceThrowsTheException(new \Exception('General Exception'));

        $this->app->whenIRunTheWebApplication();

        $this->app->thenTheResponseShouldHaveTheStatus(Response::STATUS_SERVER_ERROR);
        $this->app->thenTheResponseBodyShouldBe('General Exception');
    }

    function testThrownHttpError() {
        $this->app->givenTheTargetResourceThrowsTheException(new HttpError(Response::STATUS_FORBIDDEN, 'Some bad thing'));

        $this->app->whenIRunTheWebApplication();

        $this->app->thenTheResponseShouldHaveTheStatus(Response::STATUS_FORBIDDEN);
        $this->app->thenTheResponseBodyShouldBe('Some bad thing');
    }

} 