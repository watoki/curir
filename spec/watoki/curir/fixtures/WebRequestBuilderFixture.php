<?php
namespace spec\watoki\curir\fixtures;

use spec\watoki\curir\stubs\TestWebEnvironmentStub;
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

    /** @var WebRequest */
    public $request;

    /** @var array|\watoki\curir\protocol\ParameterDecoder[] */
    private $decoders = array();

    /** @var TestWebEnvironmentStub */
    private $environment;

    public function setUp() {
        parent::setUp();
        $this->environment = new TestWebEnvironmentStub();
    }

    public function givenTheContextIs($string) {
        $this->environment->context = Url::fromString($string);
    }

    public function givenTheRequestMethodIs($string) {
        $this->environment->method = $string;
    }

    public function givenTheHeader_Is($key, $value) {
        $this->environment->headers->set($key, $value);
    }

    public function givenTheQueryArgument_Is($key, $value) {
        $this->environment->arguments->set($key, $value);
    }

    public function givenTheFile_Is($key, $value) {
        $this->environment->files->set($key, $value);
    }

    public function givenTheTargetPathIs($string) {
        $this->environment->target = Path::fromString($string);
    }

    public function givenTheBodyIs($string) {
        $this->environment->body = $string;
    }

    public function given_IsRegisteredForTheContentType(ParameterDecoder $decoder, $contentType) {
        $this->decoders[$contentType] = $decoder;
    }

    public function whenIBuildTheRequest() {
        $builder = new WebRequestBuilder($this->environment);

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