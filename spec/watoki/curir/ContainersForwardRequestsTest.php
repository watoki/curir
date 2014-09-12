<?php
namespace spec\watoki\curir;

use spec\watoki\curir\fixtures\WebDeliveryFixture;
use spec\watoki\curir\fixtures\WebRequestBuilderFixture;
use spec\watoki\stores\FileStoreFixture;
use watoki\curir\error\ErrorResponse;
use watoki\curir\WebResponse;
use watoki\deli\router\NoneRouter;
use watoki\deli\target\RespondingTarget;
use watoki\scrut\Specification;

/**
 * Containers are Resources that contain other Resources. Requests are routed to its children.
 *
 * @property WebRequestBuilderFixture request <-
 * @property WebDeliveryFixture delivery <-
 * @property FileStoreFixture file <-
 */
class ContainersForwardRequestsTest extends Specification {

    function testChildResponds() {
        $this->givenTheContainer_In('name\space\MyContainer', 'some/folder');
        $this->givenTheClass_In_WithTheBody('name\space\some\TargetResource', 'some/folder/some', '
            public function doThis() {
                return "Hello World";
            }
        ');
        $this->givenTheTargetIsTheClass('name\space\MyContainer');

        $this->request->givenTheTargetPathIs('some/target');
        $this->request->givenTheMethodArgumentIs('this');

        $this->delivery->whenIRunTheDelivery();
        $this->thenTheResponseShouldBe('Hello World');
    }

    function testRespondsItself() {
        $this->givenTheContainer_In_WithTheBody('itself\MyContainer', 'some/folder', '
            public function doThat() {
                return "Hello myself";
            }
        ');
        $this->givenTheTargetIsTheClass('itself\MyContainer');

        $this->request->givenTheTargetPathIs('');
        $this->request->givenTheMethodArgumentIs('that');

        $this->delivery->whenIRunTheDelivery();
        $this->thenTheResponseShouldBe('Hello myself');
    }

    function testNotExistingChild() {
        $this->request->givenTheContextIs('http://cur.ir');
        $this->givenTheContainer_In('childless\Container', 'some/folder');
        $this->givenTheTargetIsTheClass('childless\Container');
        $this->request->givenTheTargetPathIs('no/existing/child');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseStatusShouldBe(WebResponse::STATUS_NOT_FOUND);
        $this->delivery->thenTheResponseBodyShouldContain('The resource [no/existing/child] does not exist in [http://cur.ir]');
    }

    ######################### SET-UP ##########################

    public function givenTheTargetIsTheClass($fullClassName) {
        $object = $this->factory->getInstance($fullClassName);
        $this->delivery->router = new NoneRouter(RespondingTarget::factory($this->factory, $object));
    }

    public function givenTheClass_In($fullClassName, $folder) {
        $this->givenTheClass_In_WithTheBody($fullClassName, $folder, '');
    }

    private function givenTheContainer_In_WithTheBody($fullClassName, $folder, $body) {
        $this->givenTheClass_Extending_In_WithTheBody($fullClassName, '\watoki\curir\Container', $folder, "
            protected function getDirectory() {
                return '$folder';
            }
            $body
        ");
    }

    private function givenTheContainer_In($fullClassName, $folder) {
        $this->givenTheContainer_In_WithTheBody($fullClassName, $folder, '');
    }

    private function givenTheClass_In_WithTheBody($fullClassName, $folder, $body) {
        $this->givenTheClass_Extending_In_WithTheBody($fullClassName, null, $folder, $body);
    }

    private function givenTheClass_Extending_In_WithTheBody($fullClassName, $superClass, $folder, $body) {
        $nameParts = explode('\\', trim($fullClassName, '\\'));
        $className = array_pop($nameParts);
        $namespace = implode('\\', $nameParts);
        $file = $folder . '/' . $className . '.php';

        $extends = $superClass ? 'extends ' . $superClass : '';

        $code = "namespace $namespace; class $className $extends {
            $body
        }";
        eval($code);
        $this->file->givenAFile_WithContent($file, '<?php ' . $code);
    }

    public function thenTheResponseShouldBe($value) {
        $response = $this->delivery->test->response;
        if ($response instanceof ErrorResponse) {
            $this->fail($response->getException());
        }
        $this->assertEquals($value, $response);
    }

} 