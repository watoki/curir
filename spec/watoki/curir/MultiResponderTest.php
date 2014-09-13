<?php
namespace spec\watoki\curir;

use spec\watoki\curir\fixtures\ClassesFixture;
use watoki\collections\Liste;
use watoki\curir\error\HttpError;
use watoki\curir\responder\MultiResponder;
use watoki\deli\Path;
use watoki\scrut\ExceptionFixture;
use watoki\scrut\Specification;

/**
 * The `MultiResponder` can be used to respond with format-dependent strings without having to extend the `Presenter`.
 *
 * @property ClassesFixture class <-
 * @property ExceptionFixture try <-
 */
class MultiResponderTest extends Specification {

    public function background() {
        $this->class->givenTheClass_Extending_In_WithTheBody('MyResource', '\watoki\curir\Resource', 'folder', "
            public function getDirectory() {
                return 'folder';
            }");
    }

    function testFallBackToDefault() {
        $this->givenTheMultiResponderIWithTheDefaultBody('Hello World');
        $this->givenTheAcceptedFormatsAre(array('not', 'neither'));

        $this->whenICreateTheResponse();
        $this->thenTheResponseBodyShouldBe('Hello World');
    }

    function testNoDefaultBodySet() {
        $this->givenTheMultiResponderIWithTheDefaultBody(null);
        $this->givenTheBodyFor_Is('foo', 'Foo you');
        $this->givenTheBodyFor_Is('bar', 'Bar me');
        $this->givenTheAcceptedFormatsAre(array('not', 'neither'));

        $this->whenITryToCreateTheResponse();
        $this->try->thenA_ShouldBeThrown(HttpError::$CLASS);
        $this->try->thenTheException_ShouldBeThrown('Invalid accepted types for [MyResource]: [not, neither] not supported by [foo, bar]');
    }

    function testRenderFormat() {
        $this->givenTheMultiResponderIWithTheDefaultBody(null);
        $this->givenTheBodyFor_Is('foo', 'Foo you');
        $this->givenTheBodyFor_Is('bar', 'Bar me');
        $this->givenTheAcceptedFormatsAre(array('not', 'bar'));

        $this->whenITryToCreateTheResponse();
        $this->thenTheResponseBodyShouldBe('Bar me');
    }

    ######################### SET-UP #######################

    private $formats = array();

    /** @var \watoki\curir\delivery\WebResponse */
    private $response;

    /** @var MultiResponder */
    private $responder;

    private function givenTheMultiResponderIWithTheDefaultBody($defaultBody) {
        $this->responder = new MultiResponder($defaultBody);
    }

    private function givenTheBodyFor_Is($key, $value) {
        $this->responder->setBody($key, $value);
    }

    private function givenTheAcceptedFormatsAre($array) {
        $this->formats = $array;
    }

    public function whenICreateTheResponse() {
        $request = new \watoki\curir\delivery\WebRequest(\watoki\curir\protocol\Url::fromString('curir'), new Path(), null, null, new Liste($this->formats));
        $this->response = $this->responder->createResponse($request, $this->factory->getInstance('MyResource'), $this->factory);
    }

    private function whenITryToCreateTheResponse() {
        $this->try->tryTo(array($this, 'whenICreateTheResponse'));
    }

    private function thenTheResponseBodyShouldBe($str) {
        $this->assertEquals($str, $this->response->getBody());
    }

} 