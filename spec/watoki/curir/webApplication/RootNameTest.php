<?php
namespace spec\watoki\curir\webApplication;

use spec\watoki\curir\fixtures\WebApplicationFixture;
use watoki\scrut\Specification;

/**
 * @property WebApplicationFixture app <-
 */
class RootNameTest extends Specification {

    function testInFolder() {
        $this->app->givenTheRootUrlIs('http://example.com/test');
        $this->app->whenIRunTheWebApplication();
        $this->app->thenTheUrlOfTheRootResourceShouldBe('http://example.com/test');
        $this->app->thenTheUrlOfTheRootResourceShouldBeAbsolute();
    }

    function testInRoot() {
        $this->app->givenTheRootUrlIs('http://example.com');
        $this->app->whenIRunTheWebApplication();
        $this->app->thenTheUrlOfTheRootResourceShouldBe('http://example.com');
    }

} 