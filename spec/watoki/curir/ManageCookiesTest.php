<?php
namespace spec\watoki\curir;

use watoki\curir\cookie\Cookie;
use watoki\curir\cookie\CookieStore;
use watoki\curir\cookie\SerializerRepository;
use watoki\scrut\ExceptionFixture;
use watoki\scrut\Specification;

/**
 * Cookies can be read and set using the `CookieStore`.
 *
 * @property ExceptionFixture try <-
*/
class ManageCookiesTest extends Specification {

    function testCreateWithStringPayload() {
        $cookie = new Cookie('foobar', new \DateTime('2001-01-01'), '/some/folder', 'my.do.main', true, true);
        $this->store->create($cookie, 'test');

        $this->thenTheCookieShouldHaveTheName('test');
        $this->thenTheCookieShouldHaveTheValue('foobar');
        $this->thenTheCookieShouldHaveTheExpireDate('2001-01-01 00:00:00');
        $this->thenTheCookieShouldHaveTheFolder('/some/folder');
        $this->thenTheCookieShouldHaveTheDomain('my.do.main');
        $this->thenTheCookieShouldBeSecure();
        $this->thenTheCookieShouldBeHttpOnly();
        $this->assertEquals('test', $this->store->getKey($cookie));
    }

    function testCreateWithArrayPayload() {
        $this->store->create(new Cookie(array('foo' => 'bar', 'baz' => array(73, 42))), 'test');
        $this->thenTheCookieShouldHaveTheValue('{"foo":"bar","baz":[73,42]}');
    }

    function testReadString() {
        $this->givenACookie_WithValue('foo', 'bar');

        $cookie = $this->store->read('foo');
        $this->assertEquals('bar', $cookie->payload);
        $this->assertEquals('foo', $this->store->getKey($cookie));
    }

    function testReadArray() {
        $this->givenACookie_WithValue('foo', '{"bar":"baz"}');

        $cookie = $this->store->read('foo');
        $this->assertEquals(array('bar' => 'baz'), $cookie->payload);
    }

    function testReadInvalidKey() {
        $this->try->tryTo(function () {
            $this->store->read('foo');
        });
        $this->try->thenTheException_ShouldBeThrown("Cookie with name [foo] does not exist");
    }

    function testUpdate() {
        $this->givenACookie_WithValue('foo', 'bar');
        $cookie = $this->store->read('foo');
        $cookie->payload = 'baz';
        $this->store->update($cookie);

        $this->thenTheCookieShouldHaveTheValue('baz');
    }

    function testDelete() {
        $this->givenACookie_WithValue('foo', 'bar');
        $this->store->delete($this->store->read('foo'));

        $this->thenTheCookieShouldHaveTheValue(null);
    }

    function testKeys() {
        $this->givenACookie_WithValue('one', 'uno');
        $this->givenACookie_WithValue('two', 'dos');
        $this->givenACookie_WithValue('three', 'tres');

        $this->assertEquals(array('one', 'two', 'three'), $this->store->keys());
    }

    ########################## SET-UP #######################

    /** @var CookieStore */
    private $store;

    private $source = array();

    private $args = array(
        'name', 'value', 'expire', 'folder', 'domain', 'secure', 'httpOnly'
    );

    protected function setUp() {
        parent::setUp();
        $this->store = new CookieStore(new SerializerRepository(), $this->source);
    }

    private function givenACookie_WithValue($name, $value) {
        $this->source[$name] = $value;
        $this->store = new CookieStore(new SerializerRepository(), $this->source);
    }

    private function thenTheCookieShouldHaveTheName($string) {
        $this->assertArg($string, 'name');
    }

    private function thenTheCookieShouldHaveTheValue($string) {
        $this->assertArg($string, 'value');
    }

    private function thenTheCookieShouldHaveTheExpireDate($string) {
        $this->assertArg(strtotime($string), 'expire');
    }

    private function thenTheCookieShouldHaveTheFolder($string) {
        $this->assertArg($string, 'folder');
    }

    private function thenTheCookieShouldHaveTheDomain($string) {
        $this->assertArg($string, 'domain');
    }

    private function thenTheCookieShouldBeSecure() {
        $this->assertArg(true, 'secure');
    }

    private function thenTheCookieShouldBeHttpOnly() {
        $this->assertArg(true, 'httpOnly');
    }

    private function assertArg($value, $arg) {
        $this->assertEquals($value, $this->get($arg));
    }

    private function get($string) {
        $cookies = array();
        $this->store->applyCookies(function () use (&$cookies) {
            $cookies[] = func_get_args();
        });
        $argIndex = array_flip($this->args);
        return $cookies[0][$argIndex[$string]];
    }

} 