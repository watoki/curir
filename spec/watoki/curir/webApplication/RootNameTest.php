<?php
namespace spec\watoki\curir\webApplication;

use spec\watoki\curir\fixtures\WebApplicationFixture;
use watoki\scrut\Specification;

/**
 * @property WebApplicationFixture app <-
 */
class RootNameTest extends Specification {

    function testRewrittenUrl() {
        $this->app->givenTheRequestUriIs("/test/path/to/resource.html");
        $this->app->givenTheScriptNameIs('/test/index.php');

        $this->app->whenIRunTheWebApplicationUnderTheUrl('http://example.com');

        $this->app->thenTheUrlOfTheRootResourceShouldBe('http://example.com/test');
    }

} 