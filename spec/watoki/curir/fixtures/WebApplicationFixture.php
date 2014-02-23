<?php
namespace spec\watoki\curir\fixtures;

use watoki\curir\http\Request;
use watoki\curir\http\Response;
use watoki\curir\http\Url;
use watoki\curir\Resource;
use watoki\curir\WebApplication;
use watoki\factory\Factory;
use watoki\scrut\Fixture;
use watoki\scrut\Specification;

class WebApplicationFixture extends Fixture {

    /** @var WebApplicationFixtureResource */
    static $root;

    /** @var \watoki\curir\http\Request */
    static $request;

    /** @var Url */
    private $rootUrl;

    private $body;

    /** @var Response|null */
    private $response;

    public function __construct(Specification $spec, Factory $factory) {
        parent::__construct($spec, $factory);

        $this->rootUrl = Url::parse('http://lacarte');
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_REQUEST = array(
            '-' => ''
        );

        WebApplicationFixtureResource::$throwException = null;
    }

    public function givenTheRequestIs($string) {
        $_REQUEST['-'] = $string;
    }

    public function givenTheRequestBodyIs($string) {
        $this->body = $string;
    }

    public function givenTheRequestContentTypeIs($string) {
        $_SERVER['CONTENT_TYPE'] = $string;
    }

    public function givenTheMethodIs($string) {
        $_SERVER['REQUEST_METHOD'] = $string;
    }

    public function givenTheRootUrlIs($string) {
        $this->rootUrl = Url::parse($string);
    }

    public function givenTheTargetResourceThrowsTheException($exception) {
        WebApplicationFixtureResource::$throwException = $exception;
    }

    public function whenIRunTheWebApplication() {
        $app = new WebApplicationFixtureWebApplication(new WebApplicationFixtureResource($this->rootUrl));
        $app->body = $this->body;
        $this->response = $app->run();
    }

    public function thenTheUrlOfTheRootResourceShouldBe($string) {
        $this->spec->assertEquals($string, self::$root->getUrl()->toString());
    }

    public function thenTheUrlOfTheRootResourceShouldBeAbsolute() {
        $this->spec->assertTrue(self::$root->getUrl()->getPath()->isAbsolute());
    }

    public function thenTheTargetShouldBe($string) {
        $this->spec->assertEquals($string, self::$request->getTarget()->toString());
    }

    public function thenTheFormatsShouldBe($array) {
        $this->spec->assertEquals($array, self::$request->getFormats());
    }

    public function thenTheMethodShouldBe($string) {
        $this->spec->assertEquals($string, self::$request->getMethod());
    }

    public function givenTheTheRequestParameter_Is($key, $value) {
        $_REQUEST[$key] = $value;
    }

    public function thenTheParameter_ShouldBe($key, $value) {
        $this->spec->assertEquals($value, self::$request->getParameters()->get($key));
    }

    public function givenRequestTheHeader_Is($key, $value) {
        $_SERVER[$key] = $value;
    }

    public function thenTheHeader_ShouldBe($key, $value) {
        $this->spec->assertTrue(self::$request->getHeaders()->has($key));
        $this->spec->assertEquals($value, self::$request->getHeaders()->get($key));
    }

    public function thenThereShouldBeNoHeader($key) {
        $this->spec->assertFalse(self::$request->getHeaders()->has($key));
    }

    public function thenTheParametersShouldBeEmpty() {
        $this->spec->assertTrue(self::$request->getParameters()->isEmpty());
    }

    public function thenTheResponseShouldHaveTheStatus($status) {
        $this->spec->assertEquals($status, $this->response->getStatus());
    }

    public function thenTheResponseBodyShouldContain($string) {
        $this->spec->assertContains($string, $this->response->getBody());
    }

    public function thenTheResponseBodyShouldBe($string) {
        $this->spec->assertEquals($string, $this->response->getBody());
    }

    public function thenTheResponseBodyShouldNotContain($string) {
        $this->spec->assertNotContains($string, $this->response->getBody());
    }

}

class WebApplicationFixtureWebApplication extends WebApplication {

    public $body = '';

    protected function readBody() {
        return $this->body;
    }

    public function run() {
        return $this->getResponse($this->buildRequest());
    }

}

class WebApplicationFixtureResource extends Resource {

    static $CLASS = __CLASS__;

    static $throwException;

    public function __construct(Url $url, Resource $parent = null) {
        parent::__construct($url, $parent);
        WebApplicationFixture::$root = $this;
    }

    public function respond(Request $request) {
        WebApplicationFixture::$request = $request;
        if (self::$throwException) {
            throw self::$throwException;
        }
        return new Response();
    }
}