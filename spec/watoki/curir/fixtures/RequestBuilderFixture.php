<?php
namespace spec\watoki\curir\fixtures;

use watoki\curir\HttpError;
use watoki\curir\ParameterDecoder;
use watoki\curir\RequestBuilder;
use watoki\curir\WebRequest;
use watoki\scrut\ExceptionFixture;
use watoki\scrut\Fixture;

/**
 * @property ExceptionFixture try <-
 */
class RequestBuilderFixture extends Fixture {

    private $_SERVER = array();

    private $_REQUEST = array();

    /** @var RequestBuilder */
    private $builder;

    /** @var WebRequest */
    private $request;

    /** @var string */
    private $body = '';

    public function setUp() {
        parent::setUp();
        $this->builder = new RequestBuilder();
        $this->_REQUEST[RequestBuilder::DEFAULT_TARGET_KEY] = '';
    }

    public function givenNoTargetPathIsGiven() {
        unset($this->_REQUEST[RequestBuilder::DEFAULT_TARGET_KEY]);
    }

    public function givenTheHeader_Is($key, $value) {
        $this->_SERVER[$key] = $value;
    }

    public function givenTheQueryArgument_Is($key, $value) {
        $this->_REQUEST[$key] = $value;
    }

    public function givenTheMethodArgumentIs($value) {
        $this->givenTheQueryArgument_Is(RequestBuilder::DEFAULT_METHOD_KEY, $value);
    }

    public function givenTheTargetPathIs($string) {
        $this->givenTheQueryArgument_Is(RequestBuilder::DEFAULT_TARGET_KEY, $string);
    }

    public function givenTheBodyIs($string) {
        $this->body = $string;
    }

    public function given_IsRegisteredForTheContentType(ParameterDecoder $decoder, $contentType) {
        $this->builder->registerDecoder($contentType, $decoder);
    }

    public function whenIBuildTheRequest() {
        $body = $this->body;
        $this->request = $this->builder->build($this->_SERVER, $this->_REQUEST, function () use ($body) {
            return $body;
        });
    }

    public function whenITryToBuildTheRequest() {
        $this->try->tryTo(array($this, 'whenIBuildTheRequest'));
    }

    public function thenItsMethodShouldBe($string) {
        $this->spec->assertEquals($string, $this->request->getMethod());
    }

    public function thenItsTargetShouldBe($string) {
        $this->spec->assertEquals($string, $this->request->getTarget()->toString());
    }

    public function thenTheFormatsShouldBe($formats) {
        $this->spec->assertEquals((array) $formats, $this->request->getFormats()->toArray());
    }

    public function thenTheFormatsShouldBeEmpty() {
        $this->thenTheFormatsShouldBe(array());
    }

    public function thenAnErrorWithStatus_AndUserMessage_ShouldBeThrown($status, $message) {
        $this->try->thenA_ShouldBeThrown(HttpError::$CLASS);
        /** @var HttpError $error */
        $error = $this->try->getCaughtException();
        $this->spec->assertEquals($status, $error->getStatus());
        $this->spec->assertEquals($message, $error->getUserMessage());
    }

    public function thenItsArgument_ShouldBe($key, $value) {
        $this->spec->assertEquals($value, $this->request->getArguments()->get($key));
    }

    public function thenTheArgumentsShouldBeEmpty() {
        $this->spec->assertEmpty($this->request->getArguments()->toArray());
    }

    public function thenItsTheHeader_ShouldBe($key, $value) {
        $this->spec->assertEquals($value, $this->request->getHeaders()->get($key));
    }

    public function thenIsShouldHaveNoHeader($key) {
        $this->spec->assertFalse($this->request->getHeaders()->has($key));
    }

} 