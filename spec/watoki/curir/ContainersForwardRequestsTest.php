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

    function testContainerRespondsItself() {
        $this->class->givenTheContainer_In_WithTheBody('itself\MyResource', 'some/folder', '
            /** @param $request <- */
            public function doThat(\watoki\curir\delivery\WebRequest $request) {
                return "Hello " . $request->getContext();
            }
        ');
        $this->delivery->givenTheTargetIsTheRespondingClass('itself\MyResource');

        $this->request->givenTheContextIs('foo');
        $this->request->givenTheTargetPathIs('');
        $this->request->givenTheRequestMethodIs('that');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseBodyShouldBe('Hello foo');
    }

    function testChildResponds() {
        $this->class->givenTheContainer_In('name\space\IndexResource', 'some/folder');
        $this->class->givenTheClass_In_WithTheBody('name\space\some\TargetResource', 'some/folder/some', '
            /** @param $request <- */
            public function doThis(\watoki\curir\delivery\WebRequest $request) {
                return "Hello " . $request->getContext();
            }
        ');
        $this->request->givenTheContextIs('/here');
        $this->delivery->givenTheTargetIsTheRespondingClass('name\space\IndexResource');

        $this->request->givenTheTargetPathIs('some/target');
        $this->request->givenTheRequestMethodIs('this');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseShouldBe('Hello /here/some/target');
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