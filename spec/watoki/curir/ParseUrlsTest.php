<?php
namespace spec\watoki\curir;

use watoki\collections\Liste;
use watoki\collections\Map;
use watoki\curir\protocol\Url;
use watoki\deli\Path;
use watoki\scrut\Specification;

/**
 * The class Url contains structured information about a URL and can be created from a string.
 */
class ParseUrlsTest extends Specification {

    function testCompleteAbsoluteUrl() {
        $url = $this->parseAndCheck('http://example.com:8080/my/path.html?key1=value1&key2=value2#here');

        $this->assertEquals('http', $url->getScheme());
        $this->assertEquals('example.com', $url->getHost());
        $this->assertEquals(8080, $url->getPort());
        $this->assertEquals('/my/path.html', $url->getPath()->toString());
        $this->assertEquals('value1', $url->getParameters()->get('key1'));
        $this->assertEquals('value2', $url->getParameters()->get('key2'));
        $this->assertEquals('here', $url->getFragment());
    }

    function testArrayParameters() {
        $url = $this->parseAndCheck('some/where?over[the]=rainbow&over[my]=body');
        $this->assertEquals(array(
            'the' => 'rainbow',
            'my' => 'body'
        ), $url->getParameters()->get('over')->toArray());
    }

    function testListParameter() {
        $url = Url::fromString('some/url')->withParameters(new Map([
            'one' => new Liste(['uno', 'un'])
        ]));

        $this->assertEquals('some/url?one[0]=uno&one[1]=un', $url->toString());
    }

    function testObjectParameters() {
        $url = Url::fromString('some/url')->withParameters(new Map([
            'one' => new \StdClass()
        ]));

        $this->assertEquals('some/url', $url->toString());
    }

    function testOverSizedParameters() {
        $url = Url::fromString('some/url')->withParameters(new Map([
            'one' => str_repeat('a', Url::MAX_PARAM_LENGTH + 1)
        ]));

        $this->assertEquals('some/url', $url->toString());
    }

    function testSameScheme() {
        $url = $this->parseAndCheck('//example.com:8080/my/path.html');

        $this->assertNull($url->getScheme());
        $this->assertEquals('example.com', $url->getHost());
        $this->assertEquals(8080, $url->getPort());
    }

    function testNoPath() {
        $url = $this->parseAndCheck('http://example.com');
        $this->assertEquals(array(''), $url->getPath()->getElements());
    }

    function testTrailingSlash() {
        $url = Url::fromString('http://example.com/');
        $this->assertEquals(new Path(array('', '')), $url->getPath());
        $this->assertEquals('http://example.com/', $url->toString());
    }

    function testTrailingSlashWithPath() {
        $url = Url::fromString('http://example.com/my/path/');
        $this->assertEquals('http://example.com/my/path/', $url->toString());
        $this->assertEquals('/my/path/', $url->getPath()->toString());
    }

    function testSameHost() {
        $url = $this->parseAndCheck('/my/path');

        $this->assertNull($url->getScheme());
        $this->assertNull($url->getHost());
        $this->assertEquals(array('', 'my', 'path'), $url->getPath()->getElements());
    }

    function testRelativePath() {
        $url = $this->parseAndCheck('my/relative/path');
        $this->assertEquals(array('my', 'relative', 'path'), $url->getPath()->getElements());
    }

    function testConsolidatePath() {
        $url = Url::fromString('http://example.com/some/./../foo/./a/b/../../path/bar/../me');
        $this->assertEquals('http://example.com/foo/path/me', $url->toString());
    }

    function testConsolidateRelativePath() {
        $this->assertEquals('bar', Url::fromString('foo/../bar')->toString());
        $this->assertEquals('bar', Url::fromString('./bar')->toString());
    }

    function testWithoutScheme() {
        $url = $this->parseAndCheck('example.com/my/path');

        $this->assertNull($url->getHost());
        $this->assertEquals(array('example.com', 'my', 'path'), $url->getPath()->getElements());
        $this->assertEquals(null, $url->getHost());
    }

    function testParseEmptyString() {
        $url = Url::fromString('');

        $this->assertEquals(array(), $url->getPath()->getElements());
    }

    function testParseParameters() {
        $url = Url::fromString('?foo=bar');

        $this->assertEquals(array(), $url->getPath()->getElements());
    }

    function testHostAndRelativePath() {
        $url = new Url('http', 'example.com', 80, Path::fromString('my/relative/path'));
        $this->assertEquals('my/relative/path', $url->toString());
    }

    function testHostAndAbsolutePath() {
        $url = new Url('http', 'example.com', 80, Path::fromString('/my/absolute/path'));
        $this->assertEquals('http://example.com:80/my/absolute/path', $url->toString());
    }

    function testAppendToPath() {
        $url = Url::fromString('http://test.domain/foo')->appended('bar');
        $this->assertEquals('http://test.domain/foo/bar', $url->toString());
    }

    function testChangeToAbsolutePath() {
        $url = Url::fromString('http://test.domain/foo')->with('', 'bar', 'bas');
        $this->assertEquals('http://test.domain/bar/bas', $url->toString());
    }

    function testDoNotChangeToRelativePath() {
        $url = Url::fromString('http://test.domain/foo')->with('bar', 'bas');
        $this->assertEquals('http://test.domain/bar/bas', $url->toString());
    }

    private function parseAndCheck($string) {
        $url = Url::fromString($string);
        $this->assertEquals($string, $url->toString());
        return $url;
    }

} 