<?php
namespace spec\watoki\curir;

use watoki\curir\HttpError;
use watoki\curir\RequestBuilder;
use watoki\curir\WebRequest;
use watoki\curir\WebResponse;
use watoki\scrut\ExceptionFixture;
use watoki\scrut\Specification;

/**
 * A WebRequest is built from the global variables $_REQUEST and $_SERVER
 *
 * @property ExceptionFixture try <-
*/
class BuildRequestTest extends Specification {

    function testMethodInHeader() {
        $this->givenTheHeader_Is('REQUEST_METHOD', 'GET');
        $this->whenIBuildTheRequest();
        $this->thenItsMethodShouldBe('get');
    }

    function testMethodInArguments() {
        $this->givenTheHeader_Is('REQUEST_METHOD', 'GET');
        $this->givenTheMethodArgumentIs('this');
        $this->whenIBuildTheRequest();
        $this->thenItsMethodShouldBe('this');
    }

    function testTargetWithExtension() {
        $this->givenTheTargetPathIs('some/foo/bar.txt');

        $this->whenIBuildTheRequest();

        $this->thenItsTargetShouldBe('some/foo/bar');
        $this->thenTheFormatsShouldBe('txt');
    }

    function testNoTargetPath() {
        $this->givenNoTargetPathIsGiven();
        $this->whenITryToBuildTheRequest();
        $this->thenAnErrorWithStatus_AndUserMessage_ShouldBeThrown(WebResponse::STATUS_BAD_REQUEST, "No target given.");
        $this->try->thenTheException_ShouldBeThrown('Request parameter $_REQUEST["-"] not set');
    }

    function testWithoutExtension() {
        $this->givenTheTargetPathIs('some/foo/bar');

        $this->whenIBuildTheRequest();

        $this->thenItsTargetShouldBe('some/foo/bar');
        $this->thenTheFormatsShouldBeEmpty();
    }

    function testFormatInHeader() {
        $this->givenTheHeader_Is('HTTP_ACCEPT',
            'text/html,application/xhtml+xml,application/xml,application/json;q=0.9,*/*;q=0.8');
        $this->givenTheTargetPathIs('some/target.json');

        $this->whenIBuildTheRequest();

        $this->thenTheFormatsShouldBe(array('json', 'htm', 'html', 'shtml', 'xhtml', 'xml'));
    }

    function testParameters() {
        $this->givenTheQueryArgument_Is('one', 'two');
        $this->whenIBuildTheRequest();
        $this->thenItsArgument_ShouldBe('one', 'two');
    }

    function testHeaders() {
        $this->givenTheHeader_Is('HTTP_ACCEPT', '*/*');
        $this->givenTheHeader_Is('HTTP_PRAGMA', null);

        $this->whenIBuildTheRequest();

        $this->thenItsTheHeader_ShouldBe('Accept', '*/*');
        $this->thenIsShouldHaveNoHeader('Pragma');
    }

    function testEdgeCaseTargetWithTwoDots() {
        $this->givenTheTargetPathIs('one/two/three.four.five');

        $this->whenIBuildTheRequest();

        $this->thenItsTargetShouldBe('one/two/three.four');
        $this->thenTheFormatsShouldBe(array('five'));
    }

    function testEdgeCaseTargetWithTrailingSlash() {
        $this->givenTheTargetPathIs('one/two/three/');

        $this->whenIBuildTheRequest();

        $this->thenItsTargetShouldBe('one/two/three');
    }

    ########################## SET-UP ###########################

    private $_SERVER = array();

    private $_REQUEST = array();

    /** @var RequestBuilder */
    private $builder;

    /** @var WebRequest */
    private $request;

    protected function setUp() {
        parent::setUp();
        $this->builder = new RequestBuilder();
        $this->_REQUEST[RequestBuilder::DEFAULT_TARGET_KEY] = '';
    }

    private function givenNoTargetPathIsGiven() {
        unset($this->_REQUEST[RequestBuilder::DEFAULT_TARGET_KEY]);
    }

    private function givenTheHeader_Is($key, $value) {
        $this->_SERVER[$key] = $value;
    }

    private function givenTheQueryArgument_Is($key, $value) {
        $this->_REQUEST[$key] = $value;
    }

    private function givenTheMethodArgumentIs($value) {
        $this->givenTheQueryArgument_Is(RequestBuilder::DEFAULT_METHOD_KEY, $value);
    }

    private function givenTheTargetPathIs($string) {
        $this->givenTheQueryArgument_Is(RequestBuilder::DEFAULT_TARGET_KEY, $string);
    }

    public function whenIBuildTheRequest() {
        $this->request = $this->builder->build($this->_SERVER, $this->_REQUEST);
    }

    private function whenITryToBuildTheRequest() {
        $this->try->tryTo(array($this, 'whenIBuildTheRequest'));
    }

    private function thenItsMethodShouldBe($string) {
        $this->assertEquals($string, $this->request->getMethod());
    }

    private function thenItsTargetShouldBe($string) {
        $this->assertEquals($string, $this->request->getTarget()->toString());
    }

    private function thenTheFormatsShouldBe($formats) {
        $this->assertEquals((array) $formats, $this->request->getFormats()->toArray());
    }

    private function thenTheFormatsShouldBeEmpty() {
        $this->thenTheFormatsShouldBe(array());
    }

    private function thenAnErrorWithStatus_AndUserMessage_ShouldBeThrown($status, $message) {
        $this->try->thenA_ShouldBeThrown(HttpError::$CLASS);
        /** @var HttpError $error */
        $error = $this->try->getCaughtException();
        $this->assertEquals($status, $error->getStatus());
        $this->assertEquals($message, $error->getUserMessage());
    }

    private function thenItsArgument_ShouldBe($key, $value) {
        $this->assertEquals($value, $this->request->getArguments()->get($key));
    }

    private function thenItsTheHeader_ShouldBe($key, $value) {
        $this->assertEquals($value, $this->request->getHeaders()->get($key));
    }

    private function thenIsShouldHaveNoHeader($key) {
        $this->assertFalse($this->request->getHeaders()->has($key));
    }

} 