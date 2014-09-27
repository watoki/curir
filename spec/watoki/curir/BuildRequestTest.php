<?php
namespace spec\watoki\curir;

use spec\watoki\curir\fixtures\WebRequestBuilderFixture;
use watoki\curir\delivery\WebRequest;
use watoki\scrut\Specification;

/**
 * A WebRequest is built from the global variables $_REQUEST and $_SERVER
 *
 * @property WebRequestBuilderFixture request <-
*/
class BuildRequestTest extends Specification {

    function testMethodInHeader() {
        $this->request->givenTheRequestMethodIs('get');

        $this->request->whenIBuildTheRequest();
        $this->request->thenItsMethodShouldBe('get');
    }

    function testMethodInArguments() {
        $this->request->givenTheRequestMethodIs('GET');
        $this->request->givenTheQueryArgument_Is('do', 'this');

        $this->request->whenIBuildTheRequest();
        $this->request->thenItsMethodShouldBe('this');
        $this->request->thenTheArgumentsShouldBeEmpty();
    }

    function testTargetWithExtension() {
        $this->request->givenTheTargetPathIs('some/foo/bar.txt');

        $this->request->whenIBuildTheRequest();
        $this->request->thenItsTargetShouldBe('some/foo/bar');
        $this->request->thenTheFormatsShouldBe('txt');
    }

    function testWithoutExtension() {
        $this->request->givenTheTargetPathIs('some/foo/bar');

        $this->request->whenIBuildTheRequest();
        $this->request->thenItsTargetShouldBe('some/foo/bar');
        $this->request->thenTheFormatsShouldBeEmpty();
    }

    function testFormatInHeader() {
        $this->request->givenTheHeader_Is(WebRequest::HEADER_ACCEPT,
            'text/html,application/xhtml+xml,application/xml,application/json;q=0.9,*/*;q=0.8');
        $this->request->givenTheTargetPathIs('some/target.json');

        $this->request->whenIBuildTheRequest();
        $this->request->thenTheFormatsShouldBe(array('json', 'htm', 'html', 'shtml', 'xhtml', 'xml'));
    }

    function testParameters() {
        $this->request->givenTheQueryArgument_Is('one', 'two');

        $this->request->whenIBuildTheRequest();
        $this->request->thenItsArgument_ShouldBe('one', 'two');
    }

    function testHeaders() {
        $this->request->givenTheHeader_Is(WebRequest::HEADER_ACCEPT, '*/*');

        $this->request->whenIBuildTheRequest();
        $this->request->thenItsTheHeader_ShouldBe(WebRequest::HEADER_ACCEPT, '*/*');
        $this->request->thenIsShouldHaveNoHeader(WebRequest::HEADER_PRAGMA);
    }

    function testEdgeCaseTargetWithTwoDots() {
        $this->request->givenTheTargetPathIs('one/two/three.four.five');

        $this->request->whenIBuildTheRequest();
        $this->request->thenItsTargetShouldBe('one/two/three.four');
        $this->request->thenTheFormatsShouldBe(array('five'));
    }

    function testEdgeCaseTargetWithTrailingSlash() {
        $this->request->givenTheTargetPathIs('one/two/three/');

        $this->request->whenIBuildTheRequest();
        $this->request->thenItsTargetShouldBe('one/two/three');
    }

} 