<?php
namespace spec\watoki\curir;

use spec\watoki\curir\fixtures\WebRequestBuilderFixture;
use spec\watoki\deli\fixtures\TestDelivery;
use watoki\curir\error\HttpError;
use watoki\curir\Url;
use watoki\curir\WebDelivery;
use watoki\curir\WebRequest;
use watoki\curir\WebResponse;
use watoki\deli\router\NoneRouter;
use watoki\deli\target\CallbackTarget;
use watoki\scrut\Specification;

/**
 * The WebDelivery is the starting point for web applications. It builds the Request, routes it to a Resource,
 * delivers the Response of the Resource, and handles Exceptions.
 *
 * @property WebRequestBuilderFixture request <-
 */
class DisplayErrorPageTest extends Specification {

    protected function background() {
        $this->givenTheContextIs('http://cur.ir');
        $this->request->givenTheTargetPathIs('some.html');
    }

    function testNoExceptionThrown() {
        $this->request->givenTheQueryArgument_Is('name', 'World');
        $this->givenTheTargetRespondsWith(function (WebRequest $r) {
            return new WebResponse('Hello ' . $r->getArguments()->get('name'));
        });

        $this->whenIRunTheDelivery();
        $this->thenTheResponseBodyShouldContain('Hello World');
    }

    function testExceptionThrown() {
        $this->givenTheTargetThrows(new \Exception('Something went wrong'));

        $this->whenIRunTheDelivery();

        $this->thenTheResponseStatusShouldBe(WebResponse::STATUS_SERVER_ERROR);
        $this->thenTheResponseBodyShouldContain(WebResponse::STATUS_SERVER_ERROR);
        $this->thenTheResponseBodyShouldContain('Exception: Something went wrong');
        $this->thenTheResponseBodyShouldNotContain('$');
    }

    function testNonHtmlFormat() {
        $this->request->givenTheTargetPathIs('some.json');
        $this->givenTheTargetThrows(new \RuntimeException('Something went wrong'));

        $this->whenIRunTheDelivery();

        $this->thenTheResponseStatusShouldBe(WebResponse::STATUS_SERVER_ERROR);
        $this->thenTheResponseBodyShouldBe('RuntimeException: Something went wrong');
    }

    function testHttpErrorThrown() {
        $this->givenTheTargetThrows(new HttpError(WebResponse::STATUS_FORBIDDEN, 'Some bad thing'));

        $this->whenIRunTheDelivery();

        $this->thenTheResponseStatusShouldBe(WebResponse::STATUS_FORBIDDEN);
        $this->thenTheResponseBodyShouldContain(WebResponse::STATUS_FORBIDDEN);
        $this->thenTheResponseBodyShouldContain('Some bad thing');
    }

    ######################### SET-UP ############################

    /** @var TestDelivery */
    private $test;

    /** @var NoneRouter */
    private $router;

    /** @var Url */
    private $context;

    private function givenTheTargetRespondsWith($callback) {
        $this->router = new NoneRouter(CallbackTarget::factory($callback));
    }

    private function givenTheTargetThrows($exception) {
        $this->givenTheTargetRespondsWith(function () use ($exception) {
            throw $exception;
        });
    }

    private function givenTheContextIs($string) {
        $this->context = Url::fromString($string);
    }

    private function whenIRunTheDelivery() {
        $request = $this->request->whenIBuildTheRequest();
        $this->test = new TestDelivery($request);
        $delivery = new WebDelivery($this->router, $this->context, $this->test, $this->test);
        $delivery->run();
    }

    private function thenTheResponseBodyShouldContain($string) {
        $this->assertContains($string, $this->webResponse()->getBody());
    }

    private function thenTheResponseBodyShouldBe($string) {
        $this->assertEquals($string, $this->webResponse()->getBody());
    }

    private function thenTheResponseBodyShouldNotContain($string) {
        $this->assertNotContains($string, $this->webResponse()->getBody());
    }

    private function thenTheResponseStatusShouldBe($status) {
        $this->assertEquals($status, $this->webResponse()->getStatus());
    }

    /**
     * @return WebResponse|null
     */
    private function webResponse() {
        if ($this->test->response instanceof WebResponse) {
            return $this->test->response;
        } else {
            $this->fail('Not a WebResponse: ' . var_export($this->test->response, true));
            return null;
        }
    }

} 