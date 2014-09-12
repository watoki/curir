<?php
namespace spec\watoki\curir\fixtures;

use spec\watoki\deli\fixtures\TestDelivery;
use watoki\curir\Url;
use watoki\curir\WebDelivery;
use watoki\curir\WebResponse;
use watoki\deli\router\NoneRouter;
use watoki\deli\target\CallbackTarget;
use watoki\scrut\Fixture;

/**
 * @property WebRequestBuilderFixture request <-
 */
class WebDeliveryFixture extends Fixture {

    /** @var TestDelivery */
    public $test;

    /** @var NoneRouter */
    public $router;

    public function givenTheTargetRespondsWith($callback) {
        $this->router = new NoneRouter(CallbackTarget::factory($callback));
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

    /**
     * @return WebResponse|null
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