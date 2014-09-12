<?php
namespace spec\watoki\curir;

use spec\watoki\curir\fixtures\ClassesFixture;
use spec\watoki\curir\fixtures\WebDeliveryFixture;
use spec\watoki\curir\fixtures\WebRequestBuilderFixture;
use watoki\curir\WebResponse;
use watoki\scrut\Specification;

/**
 * Containers are Resources that contain other Resources. Requests are routed to its children.
 *
 * @property WebRequestBuilderFixture request <-
 * @property WebDeliveryFixture delivery <-
 * @property ClassesFixture class <-
 */
class ContainersForwardRequestsTest extends Specification {

    function testChildResponds() {
        $this->class->givenTheContainer_In('name\space\MyContainer', 'some/folder');
        $this->class->givenTheClass_In_WithTheBody('name\space\some\TargetResource', 'some/folder/some', '
            public function doThis() {
                return "Hello World";
            }
        ');
        $this->delivery->givenTheTargetIsTheRespondingClass('name\space\MyContainer');

        $this->request->givenTheTargetPathIs('some/target');
        $this->request->givenTheMethodArgumentIs('this');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseShouldBe('Hello World');
    }

    function testRespondsItself() {
        $this->class->givenTheContainer_In_WithTheBody('itself\MyContainer', 'some/folder', '
            public function doThat() {
                return "Hello myself";
            }
        ');
        $this->delivery->givenTheTargetIsTheRespondingClass('itself\MyContainer');

        $this->request->givenTheTargetPathIs('');
        $this->request->givenTheMethodArgumentIs('that');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseShouldBe('Hello myself');
    }

    function testNotExistingChild() {
        $this->request->givenTheContextIs('http://cur.ir');
        $this->class->givenTheContainer_In('childless\Container', 'some/folder');
        $this->delivery->givenTheTargetIsTheRespondingClass('childless\Container');
        $this->request->givenTheTargetPathIs('no/existing/child');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseStatusShouldBe(WebResponse::STATUS_NOT_FOUND);
        $this->delivery->thenTheResponseBodyShouldContain('The resource [no/existing/child] does not exist in [http://cur.ir]');
    }

}