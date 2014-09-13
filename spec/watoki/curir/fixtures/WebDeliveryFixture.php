<?php
namespace spec\watoki\curir\fixtures;

use spec\watoki\deli\fixtures\TestDelivery;
use watoki\curir\error\ErrorResponse;
use watoki\curir\protocol\Url;
use watoki\curir\WebDelivery;
use watoki\curir\delivery\WebResponse;
use watoki\deli\Delivery;
use watoki\deli\router\NoneRouter;
use watoki\deli\target\CallbackTarget;
use watoki\deli\target\ObjectTarget;
use watoki\deli\target\RespondingTarget;
use watoki\scrut\Fixture;

/**
 * @property WebRequestBuilderFixture request <-
 */
class WebDeliveryFixture extends Fixture {

    /** @var TestDelivery */
    public $test;

    /** @var NoneRouter */
    public $router;

    public function setUp() {
        parent::setUp();
        Delivery::$errorReporting = 1;
    }

    public function givenTheTargetRespondsWith($callback) {
        $this->router = new NoneRouter(CallbackTarget::factory($callback));
    }

    public function givenTheTargetIsTheRespondingClass($fullClassName) {
        $object = $this->spec->factory->getInstance($fullClassName);
        $this->router = new NoneRouter(RespondingTarget::factory($this->spec->factory, $object));
    }

    public function givenTheTargetIsTheClass($fullClassName) {
        $object = $this->spec->factory->getInstance($fullClassName);
        $this->router = new NoneRouter(ObjectTarget::factory($this->spec->factory, $object));
    }

    public function whenIRunTheDelivery() {
        $request = $this->request->whenIBuildTheRequest();
        $this->test = new TestDelivery($request);
        $context = Url::fromString('http://example.com');
        $delivery = new WebDelivery($this->router, $context, $this->test, $this->test);
        $delivery->run();
    }

    public function thenTheResponseBodyShouldContain($string) {
        $this->spec->assertContains($string, $this->webResponse()->getBody());
    }

    public function thenTheResponseBodyShouldBe($string) {
        $this->spec->assertEquals($string, $this->webResponse()->getBody());
    }

    public function thenTheResponseBodyShouldNotContain($string) {
        $this->spec->assertNotContains($string, $this->webResponse()->getBody());
    }

    public function thenTheResponseStatusShouldBe($status) {
        $this->spec->assertEquals($status, $this->webResponse()->getStatus());
    }

    public function thenTheResponseHeader_ShouldBe($key, $value) {
        $this->spec->assertEquals($value, $this->webResponse()->getHeaders()->get($key));
    }

    public function thenTheResponseShouldBe($value) {
        $response = $this->test->response;
        if ($response instanceof ErrorResponse) {
            $this->spec->fail($response->getException());
        }
        $this->spec->assertEquals($value, $response);
    }

    /**
     * @return \watoki\curir\delivery\WebResponse|null
     */
    private function webResponse() {
        if ($this->test->response instanceof WebResponse) {
            return $this->test->response;
        } else {
            $this->spec->fail('Not a WebResponse: ' . var_export($this->test->response, true));
            return null;
        }
    }

} 