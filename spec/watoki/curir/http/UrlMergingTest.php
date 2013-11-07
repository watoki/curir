<?php
namespace spec\watoki\curir\http;
 
use watoki\collections\Map;
use watoki\curir\http\Path;
use watoki\curir\http\Url;
use watoki\scrut\Specification;

class UrlMergingTest extends Specification {

    function testMergeHost() {
        $url = new Url('http', 'example.com', null, new Path());
        $url->merge(new Url(null, 'localhost', 80, new Path()));

        $this->assertEquals('//localhost:80', $url->toString());
    }

    function testDontMergeSchemeOrPort() {
        $url = new Url('http', 'example.com', null, new Path());
        $url->merge(new Url('http', null, 80, new Path()));

        $this->assertEquals('http://example.com', $url->toString());
    }

    function testMergeAbsolutePath() {
        $url = new Url('http', 'example.com', null, Path::parse('/some/path/here'));
        $url->merge(new Url(null, null, null, Path::parse('/another/path/here')));

        $this->assertEquals('http://example.com/another/path/here', $url->toString());
    }

    function testMergeRelativePath() {
        $url = new Url('http', 'example.com', null, Path::parse('/some/path/here'));
        $url->merge(new Url(null, null, null, Path::parse('and/here/and/there')));

        $this->assertEquals('http://example.com/some/path/here/and/here/and/there', $url->toString());
    }

    function testMergeParameters() {
        $url = Url::parse('http://example.com/some/path?foo=bar&me=you');
        $url->merge(new Url(null, null, null, new Path(), new Map(array('he' => 'ho', 'foo' => 'fizz'))));

        $this->assertEquals('http://example.com/some/path?foo=fizz&me=you&he=ho', $url->toString());
    }

    function testMergeFragment() {
        $url = Url::parse('http://example.com/some/path?foo=bar#here');
        $url->merge(Url::parse('there/you#go'));

        $this->assertEquals('http://example.com/some/path/there/you?foo=bar#go', $url->toString());
    }
}
 