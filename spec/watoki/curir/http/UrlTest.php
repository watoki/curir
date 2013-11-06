<?php
namespace spec\watoki\curir\http;

use watoki\curir\http\Path;
use watoki\curir\http\Url;
use watoki\scrut\Specification;

class UrlTest extends Specification {

    function testParseAbsolute() {
        $string = 'http://example.com:8080/my/path.html?key1=value1&key2=value2#here';
        $url = Url::parse($string);

        $this->assertEquals('http', $url->getScheme());
        $this->assertEquals('example.com', $url->getHost());
        $this->assertEquals(8080, $url->getPort());
        $this->assertEquals(new Path(array('', 'my', 'path.html')), $url->getPath());
        $this->assertEquals('/my/path.html', $url->getPath()->toString());
        $this->assertEquals('value1', $url->getParameters()->get('key1'));
        $this->assertEquals('value2', $url->getParameters()->get('key2'));
        $this->assertEquals('here', $url->getFragment());
        $this->assertEquals($string, $url->toString());
    }

    function testParseSameScheme() {
        $string = '//example.com:8080/my/path.html';
        $url = Url::parse($string);

        $this->assertNull($url->getScheme());
        $this->assertEquals('example.com', $url->getHost());
        $this->assertEquals(8080, $url->getPort());
        $this->assertEquals($string, $url->toString());
    }

    function testParseNoPath() {
        $string = 'http://example.com';
        $url = Url::parse($string);

        $this->assertEquals(new Path(array('')), $url->getPath());
        $this->assertEquals($string, $url->toString());
    }

    function testTrailingSlash() {
        $string = 'http://example.com';
        $url = Url::parse($string . '/');

        $this->assertEquals(new Path(array('')), $url->getPath());
        $this->assertEquals($string, $url->toString());
    }

    function testTrailingSlashWithPath() {
        $string = 'http://example.com/my/path';
        $url = Url::parse($string . '/');

        $this->assertEquals($string, $url->toString());
    }

    function testSameHost() {
        $string = '/my/path';
        $url = Url::parse($string);

        $this->assertNull($url->getScheme());
        $this->assertNull($url->getHost());
        $this->assertEquals(new Path(array('', 'my', 'path')), $url->getPath());
        $this->assertEquals($string, $url->toString());
    }

    function testRelative() {
        $string = 'my/relative/path';
        $url = Url::parse($string);

        $this->assertEquals(new Path(array('my', 'relative', 'path')), $url->getPath());
        $this->assertEquals($string, $url->toString());
    }

    function testWithoutHostPrefix() {
        $url = Url::parse('example.com/my/path');

        $this->assertNull($url->getHost());
        $this->assertEquals(new Path(array('example.com', 'my', 'path')), $url->getPath());
        $this->assertEquals('example.com/my/path', $url->toString());
    }

    function testHostAndRelativePath() {
        $url = new Url('http', 'example.com', 80, Path::parse('my/relative/path'));
        $this->assertEquals('my/relative/path', $url->toString());
    }

    function testHostAndAbsolutePath() {
        $url = new Url('http', 'example.com', 80, Path::parse('/my/absolute/path'));
        $this->assertEquals('http://example.com:80/my/absolute/path', $url->toString());
    }

} 