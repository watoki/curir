<?php
namespace spec\watoki\curir\webApplication;

use spec\watoki\curir\fixtures\WebApplicationFixture;
use watoki\scrut\Specification;

/**
 * @property WebApplicationFixture app <-
 */
class RootNameTest extends Specification {

    function testCreateRootResourceWithUrlAsName() {
        $this->app->givenTheScriptNameIs('/test/index.php');

        $this->app->whenIRunTheWebApplication();

        $this->app->thenTheNameOfTheRootResourceShouldBe('test');
    }

} 