<?php
namespace spec\watoki\curir;

use watoki\curir\cookie\Cookie;
use watoki\curir\cookie\CookieSerializerRegistry;
use watoki\curir\cookie\CookieStore;
use watoki\curir\WebDelivery;
use watoki\scrut\ExceptionFixture;
use watoki\scrut\Specification;

/**
 * Cookies can be managed with the `CookieStore` which acts like a key/value store for Cookies.
 *
 * @property ExceptionFixture try <-
 */
class ManageCookiesTest extends Specification {

    /**
     * The WebDelivery needs to be initialized in order to use the CookieStore
     */
    function testStoreIsInitializedAsSingleton() {
        $factory = WebDelivery::init();
        $store = $factory->getSingleton(CookieStore::$CLASS);
        $this->assertInstanceOf(CookieStore::$CLASS, $store);
    }

    function testCreateCookieWithOnlyPayload() {
        $this->givenACookieWithThePayload('Hello World');

        $this->whenICreateTheCookieAs('foo');

        $this->thenACookie_ShouldBeSet('foo');
        $this->thenTheCookie_ShouldHaveTheValue('foo',
                '{"payload":"Hello World","expire":null,"path":"\/","domain":null,"secure":null,"httpOnly":null}');
    }

    function testCreateCookieWithAllParameters() {
        $this->givenACookieWithThePayload('Hello All');
        $this->givenTheCookieHasTheExpireDate('2011-12-13 14:15');
        $this->givenTheCookieHasThePath('/some/path');
        $this->givenTheCookieHasTheDomain('foo.me');
        $this->givenTheCookieIsSecure();
        $this->givenTheCookieIsHttpOnly();

        $this->whenICreateTheCookieAs('bar');

        $this->thenTheCookie_ShouldHaveTheValue('bar',
                '{"payload":"Hello All","expire":"2011-12-13T14:15:00+00:00","path":"\/some\/path","domain":"foo.me","secure":true,"httpOnly":true}');
        $this->thenTheCookie_ShouldExpire('bar', "2011-12-13 14:15");
        $this->thenTheCookie_ShouldHaveThePath('bar', "/some/path");
        $this->thenTheCookie_ShouldHaveTheDomain('bar', "foo.me");
        $this->thenTheCookie_ShouldBeSecure('bar');
        $this->thenTheCookie_ShouldBeHttpOnly('bar');
    }

    function testReadCookieWithPayloadOnly() {
        $this->givenACookie_WithValue('foo', '{"payload":"Hello There"}');

        $this->whenIReadTheCookie('foo');

        $this->thenItShouldHaveThePayload('Hello There');
        $this->thenItShouldExpire(null);
        $this->thenItShouldHaveThePath(null);
        $this->thenItShouldHaveTheDomain(null);
        $this->thenItShouldNotBeSecure();
        $this->thenItShouldNotBeHttpOnly();
    }

    function testReadCookieWithAllParameters() {
        $this->givenACookie_WithValue('bar',
                '{"payload":"Hello All","expire":"2011-12-13T14:15:00+00:00","path":"\/some\/path","domain":"foo.me","secure":true,"httpOnly":true}');

        $this->whenIReadTheCookie('bar');

        $this->thenItShouldHaveThePayload('Hello All');
        $this->thenItShouldExpire("2011-12-13 14:15");
        $this->thenItShouldHaveThePath("/some/path");
        $this->thenItShouldHaveTheDomain("foo.me");
        $this->thenItShouldBeSecure();
        $this->thenItShouldBeHttpOnly();
    }

    function testReadCookieWithPlainValue() {
        $this->givenACookie_WithValue('bar', 'very plain');
        $this->whenIReadTheCookie('bar');
        $this->thenItShouldHaveThePayload('very plain');
    }

    function testUpdateCookie() {
        $this->givenACookie_WithValue('foo', '{"payload":"Hello There","path":"\/here"}');

        $this->whenIReadTheCookie('foo');
        $this->givenTheCookieHasThePath('/there');

        $this->whenIUpdateTheCookie();
        $this->thenTheCookie_ShouldHaveTheValue('foo',
                '{"payload":"Hello There","expire":null,"path":"\/there","domain":null,"secure":null,"httpOnly":null}');
    }

    function testDeleteCookie() {
        $this->givenACookie_WithValue('foo', '{"payload":"Delete me"}');
        $this->whenIReadTheCookie('foo');
        $this->whenIDeleteTheCookie('foo');

        $this->thenTheCookie_ShouldHaveTheValue('foo', '');
    }

    function testKeys() {
        $this->givenACookie_WithValue('foo', '{"payload":"foo"}');
        $this->givenACookieWithThePayload('bar');
        $this->whenICreateTheCookieAs('bar');
        $this->thenAllKeysShouldBe(array('foo', 'bar'));
    }

    function testReadNonExistingCookie() {
        $this->whenITryToReadTheCookie('foo');
        $this->try->thenTheException_ShouldBeThrown("Cookie with name [foo] does not exist");
    }

    function testCreateWithoutAKey() {
        $this->givenACookieWithThePayload('foo', 'no key');
        $this->whenITryToCreateTheCookieWithoutAKey();
        $this->try->thenNoExceptionShouldBeThrown();
    }

    ###################################################################################

    /** @var Cookie */
    private $cookie;

    private $setCookies = array();

    private $source = array();

    /** @var CookieStore */
    private $store;

    private function givenACookieWithThePayload($payload) {
        $this->cookie = new Cookie($payload);
    }

    private function givenTheCookieHasTheExpireDate($when) {
        $this->cookie->expire = new \DateTime($when);
    }

    private function givenTheCookieHasThePath($string) {
        $this->cookie->path = $string;
    }

    private function givenTheCookieHasTheDomain($string) {
        $this->cookie->domain = $string;
    }

    private function givenTheCookieIsSecure() {
        $this->cookie->secure = true;
    }

    private function givenTheCookieIsHttpOnly() {
        $this->cookie->httpOnly = true;
    }

    private function whenICreateTheCookieAs($key) {
        date_default_timezone_set('UTC');
        $this->store = new CookieStore(new CookieSerializerRegistry(), $this->source);
        $this->store->create($this->cookie, $key);
        $this->apply($this->store);
    }

    private function whenITryToCreateTheCookieWithoutAKey() {
        $this->try->tryTo(function () {
            $this->whenICreateTheCookieAs(null);
        });
    }

    private function thenACookie_ShouldBeSet($name) {
        $this->assertArrayHasKey($name, $this->setCookies);
    }

    private function thenTheCookie_ShouldHaveTheValue($name, $payload) {
        $this->assertEquals($payload, $this->setCookies[$name][1]);
    }

    private function thenTheCookie_ShouldExpire($name, $when) {
        $this->assertEquals(strtotime($when), $this->setCookies[$name][2]);
    }

    private function thenTheCookie_ShouldHaveThePath($name, $path) {
        $this->assertEquals($path, $this->setCookies[$name][3]);
    }

    private function thenTheCookie_ShouldHaveTheDomain($name, $domain) {
        $this->assertEquals($domain, $this->setCookies[$name][4]);
    }

    private function thenTheCookie_ShouldBeSecure($name) {
        $this->assertEquals(true, $this->setCookies[$name][5]);
    }

    private function thenTheCookie_ShouldBeHttpOnly($name) {
        $this->assertEquals(true, $this->setCookies[$name][6]);
    }

    private function givenACookie_WithValue($key, $value) {
        $this->source[$key] = $value;
    }

    private function whenIReadTheCookie($key) {
        $this->store = new CookieStore(new CookieSerializerRegistry(), $this->source);
        $this->cookie = $this->store->read($key);
    }

    private function whenITryToReadTheCookie($key) {
        $this->try->tryTo(function () use ($key) {
            $this->whenIReadTheCookie($key);
        });
    }

    private function whenIDeleteTheCookie($name) {
        $this->store->delete($name);
        $this->apply($this->store);
    }

    private function whenIUpdateTheCookie() {
        $this->store->update($this->cookie);
        $this->apply($this->store);
    }

    private function thenItShouldHaveThePayload($payload) {
        $this->assertEquals($payload, $this->cookie->payload);
    }

    private function thenItShouldExpire($when) {
        if (!$when) {
            $this->assertNull($this->cookie->expire);
        } else {
            $this->assertEquals(strtotime($when), $this->cookie->expire->getTimestamp());
        }
    }

    private function thenItShouldHaveThePath($path) {
        $this->assertEquals($path, $this->cookie->path);
    }

    private function thenItShouldHaveTheDomain($domain) {
        $this->assertEquals($domain, $this->cookie->domain);
    }

    private function thenItShouldNotBeSecure() {
        $this->assertNull($this->cookie->secure);
    }

    private function thenItShouldNotBeHttpOnly() {
        $this->assertNull($this->cookie->httpOnly);
    }

    private function thenItShouldBeSecure() {
        $this->assertTrue($this->cookie->secure);
    }

    private function thenItShouldBeHttpOnly() {
        $this->assertTrue($this->cookie->httpOnly);
    }

    private function apply(CookieStore $store) {
        $store->applyCookies(function ($name) {
            $this->setCookies[$name] = func_get_args();
        });
    }

    private function thenAllKeysShouldBe($array) {
        $this->assertEquals($array, $this->store->keys());
    }

} 