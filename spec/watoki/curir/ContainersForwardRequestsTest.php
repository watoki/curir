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
            public function doThat() {
                return "Hello My";
            }
        ');
        $this->delivery->givenTheTargetIsTheRespondingClass('itself\MyResource');

        $this->request->givenTheTargetPathIs('my');
        $this->request->givenTheRequestMethodIs('that');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseBodyShouldBe('Hello My');
    }

    function testContainerRespondsToEmptyPath() {
        $this->class->givenTheContainer_In_WithTheBody('emptyPath\MyResource', 'some/folder', '
            public function doThat() {
                return "Hello empty path";
            }
        ');
        $this->delivery->givenTheTargetIsTheRespondingClass('emptyPath\MyResource');

        $this->request->givenTheTargetPathIs('');
        $this->request->givenTheRequestMethodIs('that');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseBodyShouldBe('Hello empty path');
    }

    function testChildResponds() {
        $this->class->givenTheContainer_In('name\space\MyResource', 'some/folder');
        $this->class->givenTheClass_In_WithTheBody('name\space\my\some\TargetResource', 'some/folder/my/some', '
            /** @param $request <- */
            public function doThis(\watoki\curir\delivery\WebRequest $request) {
                return "Hello " . $request->getContext() . " " . $request->getTarget();
            }
        ');
        $this->request->givenTheContextIs('/here');
        $this->delivery->givenTheTargetIsTheRespondingClass('name\space\MyResource');

        $this->request->givenTheTargetPathIs('my/some/target');
        $this->request->givenTheRequestMethodIs('this');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseShouldBe('Hello /here/my/some target');
    }

    function testSiblingResponds() {
        $this->class->givenTheContainer_In('family\space\MyResource', 'some/folder');
        $this->class->givenTheClass_In_WithTheBody('family\space\SiblingResource', 'some/folder', '
            /** @param $request <- */
            public function doThis(\watoki\curir\delivery\WebRequest $request) {
                return $request->getContext() . " " . $request->getTarget();
            }
        ');
        $this->request->givenTheContextIs('/context');
        $this->delivery->givenTheTargetIsTheRespondingClass('family\space\MyResource');

        $this->request->givenTheTargetPathIs('sibling');
        $this->request->givenTheRequestMethodIs('this');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseShouldBe('/context sibling');
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