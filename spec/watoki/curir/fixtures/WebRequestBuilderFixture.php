<?php
namespace spec\watoki\curir\fixtures;

use watoki\curir\error\HttpError;
use watoki\curir\protocol\ParameterDecoder;
use watoki\curir\protocol\Url;
use watoki\curir\delivery\WebRequestBuilder;
use watoki\curir\delivery\WebRequest;
use watoki\deli\Path;
use watoki\scrut\ExceptionFixture;
use watoki\scrut\Fixture;

/**
 * @property ExceptionFixture try <-
 */
class WebRequestBuilderFixture extends Fixture {

    private $_SERVER = array();

    private $_REQUEST = array();

    /** @var WebRequest */
    private $request;

    /** @var string */
    private $body = '';

    /** @var array|\watoki\curir\protocol\ParameterDecoder[] */
    private $decoders = array();

    /** @var \watoki\curir\protocol\Url */
    public $context;

    public function setUp() {
        parent::setUp();
        $this->_REQUEST[WebRequestBuilder::DEFAULT_TARGET_KEY] = '';
        $this->context = Url::fromString('http://example.org');
    }

    public function givenTheContextIs($string) {
        $this->context = Url::fromString($string);
    }

    public function givenNoTargetPathIsGiven() {
        unset($this->_REQUEST[WebRequestBuilder::DEFAULT_TARGET_KEY]);
    }

    public function givenTheHeader_Is($key, $value) {
        $this->_SERVER[$key] = $value;
    }

    public function givenTheQueryArgument_Is($key, $value) {
        $this->_REQUEST[$key] = $value;
    }

    public function givenTheMethodArgumentIs($value) {
        $this->givenTheQueryArgument_Is(WebRequestBuilder::DEFAULT_METHOD_KEY, $value);
    }

    public function givenTheTargetPathIs($string) {
        $this->givenTheQueryArgument_Is(WebRequestBuilder::DEFAULT_TARGET_KEY, $string);
    }

    public function givenTheBodyIs($string) {
        $this->body = $string;
    }

    public function given_IsRegisteredForTheContentType(ParameterDecoder $decoder, $contentType) {
        $this->decoders[$contentType] = $decoder;
    }

    public function whenIBuildTheRequest() {
        $body = $this->body;
        $builder = new WebRequestBuilder($this->_SERVER, $this->_REQUEST, function () use ($body) {
            return $body;
        }, $this->context);
        foreach ($this->decoders as $contentType => $decoder) {
            $builder->registerDecoder($contentType, $decoder);
        }
        $this->request = $builder->build(new Path());
        return $this->request;
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
        /** @var \watoki\curir\error\HttpError $error */
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