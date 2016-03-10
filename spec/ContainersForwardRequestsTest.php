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
                return "Hello " . $request->getContext() . ":" . $request->getTarget();
            }
        ');
        $this->delivery->givenTheTargetIsTheRespondingClass('itself\MyResource');

        $this->request->givenTheContextIs('foo');
        $this->request->givenTheTargetPathIs('');
        $this->request->givenTheRequestMethodIs('that');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseBodyShouldBe('Hello foo:');
    }

    function testChildResponds() {
        $this->class->givenTheContainer_In('name\space\IndexResource', 'some/folder');
        $this->class->givenTheClass_In_WithTheBody('name\space\some\TargetResource', 'some/folder/some', '
            /** @param $request <- */
            public function doThis(\watoki\curir\delivery\WebRequest $request) {
                return "Hello " . $request->getContext() . ":" . $request->getTarget();
            }
        ');
        $this->request->givenTheContextIs('/here');
        $this->delivery->givenTheTargetIsTheRespondingClass('name\space\IndexResource');

        $this->request->givenTheTargetPathIs('some/target');
        $this->request->givenTheRequestMethodIs('this');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseShouldBe('Hello /here/some:target');
    }

    function testNotExistingChild() {
        $this->request->givenTheContextIs('http://cur.ir');
        $this->class->givenTheContainer_In('childless\Container', 'some/folder');
        $this->delivery->givenTheTargetIsTheRespondingClass('childless\Container');
        $this->request->givenTheTargetPathIs('no/existing/child');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseStatusShouldBe(WebResponse::STATUS_NOT_FOUND);
        $this->delivery->thenTheResponseBodyShouldContain('Could not find [no/existing/child] in [http://cur.ir].');
    }

    function testExplicitRequestForIndex() {
        $this->class->givenTheContainer_In_WithTheBody('explicit\space\IndexResource', 'some/folder', '
            /** @param $request <- */
            public function doThis(\watoki\curir\delivery\WebRequest $request) {
                return "Hello " . $request->getContext() . ":" . $request->getTarget();
            }
        ');
        $this->request->givenTheContextIs('my/context');
        $this->delivery->givenTheTargetIsTheRespondingClass('explicit\space\IndexResource');

        $this->request->givenTheTargetPathIs('index');
        $this->request->givenTheRequestMethodIs('this');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseBodyShouldBe('Hello my/context:index');
    }

    function testImplicitRequestForIndex() {
        $this->class->givenTheContainer_In_WithTheBody('explicit\space\IndexResource', 'some/folder', '
            /** @param $request <- */
            public function doThis(\watoki\curir\delivery\WebRequest $request) {
                return "Hello " . $request->getContext() . ":" . $request->getTarget();
            }
        ');
        $this->request->givenTheContextIs('my/context');
        $this->delivery->givenTheTargetIsTheRespondingClass('explicit\space\IndexResource');

        $this->request->givenTheTargetPathIs('');
        $this->request->givenTheRequestMethodIs('this');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseBodyShouldBe('Hello my/context:');
    }

    function testForwardsToChildOfBaseClass(){
        $this->class->givenTheContainer_In_WithTheBody('extending\base\IndexResource', 'other/folder', '');
        $this->class->givenTheClass_In_WithTheBody('extending\base\ChildResource', 'other/folder', '
            /** @param $request <- */
            public function doThis(\watoki\curir\delivery\WebRequest $request) {
                return "Found " . $request->getTarget() . "@" . $request->getContext();
            }
        ');
        $this->class->givenTheClass_Extending_In_WithTheBody('extending\sub\IndexResource', '\extending\base\IndexResource', 'some/folder', '');

        $this->delivery->givenTheTargetIsTheRespondingClass('extending\sub\IndexResource');

        $this->request->givenTheTargetPathIs('child');
        $this->request->givenTheContextIs('cur.ir');
        $this->request->givenTheRequestMethodIs('this');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseShouldBe('Found child@cur.ir');
    }

}