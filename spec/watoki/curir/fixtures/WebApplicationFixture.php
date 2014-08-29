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

    private $globals = array();

    public function __construct(Specification $spec, Factory $factory) {
        parent::__construct($spec, $factory);

        $this->rootUrl = Url::parse('http://lacarte');

        $this->globals['_SERVER']['REQUEST_METHOD'] = 'GET';
        $this->globals['_SERVER']['HTTP_ACCEPT'] = '*/*';
        $this->globals['_REQUEST'] = array(
            '-' => ''
        );

        WebApplicationFixtureResource::$throwException = null;
    }

    public function givenTheRequestIs($string) {
        $this->globals['_REQUEST']['-'] = $string;
    }

    public function givenTheRequestBodyIs($string) {
        $this->body = $string;
    }

    public function givenTheRequestContentTypeIs($string) {
        $this->globals['_SERVER']['CONTENT_TYPE'] = $string;
    }

    public function givenTheMethodIs($string) {
        $this->globals['_SERVER']['REQUEST_METHOD'] = $string;
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
        $app->globals = $this->globals;
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
        $this->globals['_REQUEST'][$key] = $value;
    }

    public function thenTheParameter_ShouldBe($key, $value) {
        $this->spec->assertEquals($value, self::$request->getParameters()->get($key));
    }

    public function givenRequestTheHeader_Is($key, $value) {
        $this->globals['_SERVER'][$key] = $value;
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

    public $globals = array();

    protected function readBody() {
        return $this->body;
    }

    public function run() {
        return $this->getResponse($this->buildRequest(
            $this->globals['_REQUEST'], $this->globals['_SERVER']));
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