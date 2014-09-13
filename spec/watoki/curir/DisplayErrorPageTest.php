<?php
namespace spec\watoki\curir;

use spec\watoki\curir\fixtures\WebDeliveryFixture;
use spec\watoki\curir\fixtures\WebRequestBuilderFixture;
use watoki\curir\delivery\WebRequest;
use watoki\curir\delivery\WebResponse;
use watoki\curir\error\HttpError;
use watoki\scrut\Specification;

/**
 * The WebDelivery is the starting point for web applications. It builds the Request, routes it to a Resource,
 * delivers the Response of the Resource, and handles Exceptions.
 *
 * @property WebRequestBuilderFixture request <-
 * @property WebDeliveryFixture delivery <-
 */
class DisplayErrorPageTest extends Specification {

    protected function background() {
        $this->request->givenTheTargetPathIs('some.html');
    }

    function testNoExceptionThrown() {
        $this->request->givenTheQueryArgument_Is('name', 'World');
        $this->delivery->givenTheTargetRespondsWith(function (WebRequest $r) {
            return new WebResponse('Hello ' . $r->getArguments()->get('name'));
        });

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseBodyShouldContain('Hello World');
    }

    function testExceptionThrown() {
        $this->givenTheTargetThrows(new \Exception('Something went wrong'));

        $this->delivery->whenIRunTheDelivery();

        $this->delivery->thenTheResponseStatusShouldBe(WebResponse::STATUS_SERVER_ERROR);
        $this->delivery->thenTheResponseBodyShouldContain(WebResponse::STATUS_SERVER_ERROR);
        $this->delivery->thenTheResponseBodyShouldContain('Exception: Something went wrong');
        $this->delivery->thenTheResponseBodyShouldNotContain('$');
    }

    function testNonHtmlFormat() {
        $this->request->givenTheTargetPathIs('some.json');
        $this->givenTheTargetThrows(new \RuntimeException('Something went wrong'));

        $this->delivery->whenIRunTheDelivery();

        $this->delivery->thenTheResponseStatusShouldBe(WebResponse::STATUS_SERVER_ERROR);
        $this->delivery->thenTheResponseBodyShouldBe('RuntimeException: Something went wrong');
    }

    function testHttpErrorThrown() {
        $this->givenTheTargetThrows(new HttpError(WebResponse::STATUS_FORBIDDEN, 'Some bad thing'));

        $this->delivery->whenIRunTheDelivery();

        $this->delivery->thenTheResponseStatusShouldBe(WebResponse::STATUS_FORBIDDEN);
        $this->delivery->thenTheResponseBodyShouldContain(WebResponse::STATUS_FORBIDDEN);
        $this->delivery->thenTheResponseBodyShouldContain('Some bad thing');
    }

    ######################### SET-UP ############################

    private function givenTheTargetThrows($exception) {
        $this->delivery->givenTheTargetRespondsWith(function () use ($exception) {
            throw $exception;
        });
    }

} 