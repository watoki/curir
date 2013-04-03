<?php
namespace spec\watoki\curir;
 
use watoki\collections\Liste;
use watoki\collections\Map;
use watoki\curir\Path;
use watoki\curir\Url;

class UrlTest extends Test {

    function testParsing() {
        $url = Url::parse('some/path/file.html?a[b][c]=1&b=2&a[b][0]=3');

        $this->assertEquals(1, $url->getParameters()->get('a')->get('b')->get('c'));
        $this->assertEquals(3, $url->getParameters()->get('a')->get('b')->get('0'));
        $this->assertEquals(2, $url->getParameters()->get('b'));
    }

    function testMapParameters() {
        $url = new Url(new Path(new Liste(array('test.html'))), Map::toCollections(array(
            'a' => array(
                'b' => 1,
                'c' => array(
                    'd' => 2
                )
            ),
            'b' => array(
                'c' => 3,
                'd' => 4
            )
        )));

        $this->assertEquals('test.html?a[b]=1&a[c][d]=2&b[c]=3&b[d]=4', urldecode($url->toString()));
    }

}
