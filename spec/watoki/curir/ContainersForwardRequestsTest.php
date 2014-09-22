<?php
namespace spec\watoki\curir;

use spec\watoki\curir\fixtures\ClassesFixture;
use spec\watoki\curir\fixtures\WebDeliveryFixture;
use spec\watoki\curir\fixtures\WebRequestBuilderFixture;
use watoki\curir\delivery\WebResponse;
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
        $this->class->givenTheContainer_In('name\space\MyResource', 'some/folder');
        $this->class->givenTheClass_In_WithTheBody('name\space\my\some\TargetResource', 'some/folder/my/some', '
            /**
             * @param $request <-
             */
            public function doThis(\watoki\curir\delivery\WebRequest $request) {
                return "Hello World " . $request->getContext();
            }
        ');
        $this->request->givenTheContextIs('http://example.com/here');
        $this->delivery->givenTheTargetIsTheRespondingClass('name\space\MyResource');

        $this->request->givenTheTargetPathIs('some/target');
        $this->request->givenTheMethodArgumentIs('this');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseShouldBe('Hello World http://example.com/here/some/target');
    }

    function testRespondsItself() {
        $this->class->givenTheContainer_In_WithTheBody('itself\MyContainer', 'some/folder', '
            public function doThat() {
                return new \watoki\curir\responder\MultiResponder("Hello myself");
            }
        ');
        $this->delivery->givenTheTargetIsTheRespondingClass('itself\MyContainer');

        $this->request->givenTheTargetPathIs('');
        $this->request->givenTheMethodArgumentIs('that');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseBodyShouldBe('Hello myself');
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