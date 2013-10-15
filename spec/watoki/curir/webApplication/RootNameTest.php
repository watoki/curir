<?php
namespace spec\watoki\curir\webApplication;

use spec\watoki\curir\fixtures\WebApplicationFixture;
use watoki\scrut\Specification;

/**
 * @property WebApplicationFixture app <-
 */
class RootNameTest extends Specification {

    function testCreateRootResourceWithUrlAsName() {
        $this->app->givenTheSchemeIs('http');
        $this->app->givenTheHostIs('localhost');
        $this->app->givenThePortIs(80);
        $this->app->givenTheScriptNameIs('/test/index.php');

        $this->app->whenIRunTheWebApplication();

        $this->app->thenTheNameOfTheRootResourceShouldBe('http://localhost/test');
    }

    function testUrlWithoutSchemeAndDifferentPort() {
        $this->app->givenTheSchemeIs(null);
        $this->app->givenTheHostIs('example.com');
        $this->app->givenThePortIs(8080);
        $this->app->givenTheScriptNameIs('/test/index.php');

        $this->app->whenIRunTheWebApplication();

        $this->app->thenTheNameOfTheRootResourceShouldBe('//example.com:8080/test');
    }

} 