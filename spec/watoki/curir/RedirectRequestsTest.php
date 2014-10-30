<?php
namespace spec\watoki\curir;

use spec\watoki\curir\fixtures\ClassesFixture;
use spec\watoki\curir\fixtures\WebDeliveryFixture;
use spec\watoki\curir\fixtures\WebRequestBuilderFixture;
use watoki\curir\delivery\WebResponse;
use watoki\scrut\Specification;

/**
 * The WebResponse of a Resource is created by a Responder, which might render it using a template or set different headers.
 *
 * @property ClassesFixture class <-
 * @property WebRequestBuilderFixture request <-
 * @property WebDeliveryFixture delivery <-
 */
class RedirectRequestsTest extends Specification {

    function testRedirectToAbsoluteUrl() {
        $this->givenTheTargetResource_In_WithTheBody('RedirectAbsolute', 'folder', '
            public function doThis() {
                return \watoki\curir\responder\Redirecter::fromString("http://example.com");
            }
        ');
        $this->request->givenTheRequestMethodIs('this');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseBodyShouldBe('');
        $this->delivery->thenTheResponseStatusShouldBe(WebResponse::STATUS_SEE_OTHER);
        $this->delivery->thenTheResponseHeader_ShouldBe(WebResponse::HEADER_LOCATION, 'http://example.com');
    }

    function testRedirectToRelativeUrl() {
        $this->givenTheTargetResource_In_WithTheBody('RedirectRelative', 'folder', '
            public function doThis() {
                return \watoki\curir\responder\Redirecter::fromString("../relative/path?with=query#andFragmet");
            }
        ');
        $this->request->givenTheRequestMethodIs('this');
        $this->request->givenTheContextIs('http://some.host/some/path');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseHeader_ShouldBe(WebResponse::HEADER_LOCATION,
                'http://some.host/some/relative/path?with=query#andFragmet');
    }

    function testRedirectToSameUrl() {
        $this->givenTheTargetResource_In_WithTheBody('RedirectSameResource', 'folder', '
            public function doThis() {
                return \watoki\curir\responder\Redirecter::fromString("");
            }
        ');
        $this->request->givenTheTargetPathIs('redirectSame');
        $this->request->givenTheRequestMethodIs('this');
        $this->request->givenTheContextIs('http://some.host/some/path');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseHeader_ShouldBe(WebResponse::HEADER_LOCATION, 'http://some.host/some/path/redirectSame');
    }

    function testRedirectWithParametersOnly() {
        $this->givenTheTargetResource_In_WithTheBody('OnlyParamsResource', 'folder', '
            public function doThis() {
                return \watoki\curir\responder\Redirecter::fromString("?foo=bar");
            }
        ');
        $this->request->givenTheTargetPathIs('onlyParams');
        $this->request->givenTheRequestMethodIs('this');
        $this->request->givenTheContextIs('http://some.host/some/path');

        $this->delivery->whenIRunTheDelivery();
        $this->delivery->thenTheResponseHeader_ShouldBe(WebResponse::HEADER_LOCATION, 'http://some.host/some/path/onlyParams?foo=bar');
    }

    private function givenTheTargetResource_In_WithTheBody($fullName, $folder, $body) {
        $this->class->givenTheClass_Extending_In_WithTheBody($fullName, '\watoki\curir\Resource', $folder, "" . $body);
        $this->delivery->givenTheTargetIsTheClass($fullName);
    }

} 