<?php
namespace spec\watoki\curir\resources;
 
use spec\watoki\curir\fixtures\ResourceFixture;
use watoki\scrut\Specification;

/**
 * @property ResourceFixture resource <-
 */
class ContainerTest extends Specification {

    function testNotExistingChild() {
        $this->resource->givenTheContainer('Childless');
        $this->resource->givenTheRequestHasTheTarget('notexisting');

        $this->resource->whenITryToSendTheRequestToThatResource();
        $this->resource->thenTheRequestShouldFailWith('Resource [notexisting] not found in container [Childless]');
    }

    function testForwardToStaticChild() {
        $this->markTestIncomplete();
    }

    function testForwardToDynamicChild() {
        $this->markTestIncomplete();
    }

    function testForwardToInheritedChild() {
        $this->markTestIncomplete();
    }

    function testRespondsItself() {
        $this->markTestIncomplete();
    }

}
 