<?php
namespace spec\watoki\curir;

use spec\watoki\curir\fixtures\WebDeliveryFixture;
use spec\watoki\curir\fixtures\WebRequestBuilderFixture;
use watoki\curir\delivery\WebRequest;
use watoki\curir\delivery\WebResponse;
use watoki\scrut\Specification;

/**
 * This specification describes the basic behaviour of the `WebDelivery`.
 *
 * @property WebDeliveryFixture delivery <-
 * @property WebRequestBuilderFixture request <-
 */
class BasicDeliveryTest extends Specification {

    function testDeliverToTarget() {
        $this->delivery->givenTheTargetRespondsWith(function (WebRequest $r) {
            return 'Hello ' . $r->getArguments()->get('name');
        });
        $this->request->givenTheQueryArgument_Is('name', 'Moe');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseShouldBe('Hello Moe');
    }

    function testTrailingSlashes() {
        $this->delivery->givenTheTargetRespondsWith(function (WebRequest $request) {
            return 'Arrived at ' . $request->getTarget()->toString();
        });
        $this->request->givenTheTargetPathIs('some/path/with/slash/');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseShouldBe('Arrived at some/path/with/slash/index');
    }

    function testDoNotRedirectEmptyTarget() {
        $this->delivery->givenTheTargetRespondsWith(function () {
            return 'Should arrive here';
        });
        $this->request->givenTheTargetPathIs('');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseShouldBe('Should arrive here');
    }

} 