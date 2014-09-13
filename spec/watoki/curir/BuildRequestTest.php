<?php
namespace spec\watoki\curir;

use spec\watoki\curir\fixtures\WebRequestBuilderFixture;
use watoki\scrut\Specification;

/**
 * A WebRequest is built from the global variables $_REQUEST and $_SERVER
 *
 * @property WebRequestBuilderFixture request <-
*/
class BuildRequestTest extends Specification {

    function testMethodInHeader() {
        $this->request->givenTheHeader_Is('REQUEST_METHOD', 'GET');

        $this->request->whenIBuildTheRequest();
        $this->request->thenItsMethodShouldBe('get');
    }

    function testMethodInArguments() {
        $this->request->givenTheHeader_Is('REQUEST_METHOD', 'GET');
        $this->request->givenTheMethodArgumentIs('this');

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

    function testNoTargetPath() {
        $this->request->givenNoTargetPathIsGiven();

        $this->request->whenITryToBuildTheRequest();
        $this->request->thenAnErrorWithStatus_AndUserMessage_ShouldBeThrown(\watoki\curir\delivery\WebResponse::STATUS_BAD_REQUEST, "No target given.");
        $this->request->try->thenTheException_ShouldBeThrown('Request parameter $_REQUEST["-"] not set');
    }

    function testWithoutExtension() {
        $this->request->givenTheTargetPathIs('some/foo/bar');

        $this->request->whenIBuildTheRequest();
        $this->request->thenItsTargetShouldBe('some/foo/bar');
        $this->request->thenTheFormatsShouldBeEmpty();
    }

    function testFormatInHeader() {
        $this->request->givenTheHeader_Is('HTTP_ACCEPT',
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
        $this->request->givenTheHeader_Is('HTTP_ACCEPT', '*/*');
        $this->request->givenTheHeader_Is('HTTP_PRAGMA', null);

        $this->request->whenIBuildTheRequest();
        $this->request->thenItsTheHeader_ShouldBe('Accept', '*/*');
        $this->request->thenIsShouldHaveNoHeader('Pragma');
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