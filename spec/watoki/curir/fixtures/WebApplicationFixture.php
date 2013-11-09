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

    public function __construct(Specification $spec, Factory $factory) {
        parent::__construct($spec, $factory);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_REQUEST = array(
            '-' => ''
        );
    }

    public function givenTheRequestIs($string) {
        $_REQUEST['-'] = $string;
    }

    public function givenTheMethodIs($string) {
        $_SERVER['REQUEST_METHOD'] = $string;
    }

    public function givenTheRootUrlIs($string) {
        $this->rootUrl = Url::parse($string);
    }

    public function whenIRunTheWebApplication() {
        $app = new WebApplication(WebApplicationFixtureResource::$CLASS, $this->rootUrl);
        $app->run();
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

    public function thenTheFormatShouldBe($string) {
        $this->spec->assertEquals($string, self::$request->getFormat());
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

}

class WebApplicationFixtureResource extends Resource {

    static $CLASS = __CLASS__;

    public function __construct(Url $url, Resource $parent = null) {
        parent::__construct($url, $parent);
        WebApplicationFixture::$root = $this;
    }

    /**
     * @param \watoki\curir\http\Request $request
     * @return \watoki\curir\http\Response
     */
    public function respond(Request $request) {
        WebApplicationFixture::$request = $request;
        return new Response();
    }
}