<?php
namespace spec\watoki\webco;

use spec\watoki\webco\steps\Given;
use watoki\collections\Liste;
use watoki\webco\Request;

/**
 * @property CompositionTest_Given given
 */
class CompositionTest extends Test {

    protected function setUp() {
        parent::setUp();

        $this->given->theRequestMethodIs(Request::METHOD_GET);
        $this->given->theRequestResourceIs('super.html');
    }

    function testIncludePlain() {
        $this->given->theFolder_WithModule('snippet');
        $this->given->theSubComponent_In_WithTemplate('snippet\Sub', 'snippet', '%msg%!');
        $this->given->theSuperComponent_In_WithTheBody('snippet\Super', 'snippet', '
        function doGet() {
            return array(
                "sub" => new \watoki\webco\controller\sub\PlainSubComponent($this, Sub::$CLASS)
            );
        }');
        $this->given->theFile_In_WithContent('super.html', 'snippet', 'Hello %sub%');

        $this->when->iSendTheRequestTo('snippet\Module');

        $this->then->theResponseBodyShouldBe('Hello World!');
    }

    function testIncludeHtmlDocument() {
        $this->given->theFolder_WithModule('document');
        $this->given->theSubComponent_In_WithTemplate('document\Sub', 'document',
            '<html>
                <head><title>Sub Component</title></head>
                <body><b>%msg%</b></body>
            </html>');
        $this->given->theSuperComponent_In_WithTheBody('document\Super', 'document', '
        function doGet() {
            return array(
                "sub" => new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub::$CLASS)
            );
        }');
        // TODO add head element to template
        $this->given->theFile_In_WithContent('super.html', 'document', '<html><body>Hello %sub%</body></html>');

        $this->when->iSendTheRequestTo('document\Module');

        $this->then->theResponseBodyShouldBe('<html><head></head><body>Hello <b>World</b></body></html>');
    }

    function testAbsorbHeader() {
        $this->given->theFolder_WithModule('assets');
        $this->given->theSubComponent_In_WithTemplate('assets\Sub', 'assets',
            '<html>
                <head>
                    <link href="http://twitter.github.com/bootstrap/assets/css/bootstrap.css" rel="stylesheet">
                </head>
                <body><i>%msg%</i></body>
            </html>');
        $this->given->theSuperComponent_In_WithTheBody('assets\Super', 'assets', '
        function doGet() {
            return array(
                "sub" => new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub::$CLASS)
            );
        }');
        $this->given->theFile_In_WithContent('super.html', 'assets', '<html><body>Hello %sub%</body></html>');

        $this->when->iSendTheRequestTo('assets\Module');

        $this->then->theResponseBodyShouldBe(
            '<html>
                <head>
                    <link href="http://twitter.github.com/bootstrap/assets/css/bootstrap.css" rel="stylesheet">
                </head>
                <body>Hello <i>World</i></body>
            </html>');
    }

    function testRelativeUrls() {
        $this->given->theFolder_WithModule('relative');
        $this->given->theFolder_WithModule('relative/inner');
        $this->given->theSubComponent_In_WithTemplate('relative\inner\Sub', 'relative/inner',
            '<html>
                <head>
                    <link href="relative/path/file.css" rel="stylesheet">
                </head>
                <body>
                    <p>%msg%</p>
                    <img src="also/relative.png">
                    <img src="/not/relative.png">
                </body>
            </html>');
        $this->given->theSuperComponent_In_WithTheBody('relative\Super', 'relative', '
        function doGet() {
            return array(
                "sub" => new \watoki\webco\controller\sub\HtmlSubComponent($this, inner\Sub::$CLASS)
            );
        }');
        $this->given->theFile_In_WithContent('super.html', 'relative', '<html><body>Hello %sub%</body></html>');

        $this->when->iSendTheRequestTo('relative\Module');

        $this->then->theResponseBodyShouldBe(
            '<html>
                <head>
                    <link href="/base/inner/relative/path/file.css" rel="stylesheet">
                </head>
                <body>
                    Hello <p>World</p>
                    <img src="/base/inner/also/relative.png">
                    <img src="/not/relative.png">
                </body>
            </html>');
    }

    function testDeepLinkReplacement() {
        $this->given->theFolder_WithModule('deeplink');
        $this->given->theSubComponent_In_WithTemplate('deeplink\Sub', 'deeplink',
            '<html>
                <head></head>
                <body>
                    <a href="some/link.html?param1[map][map2]=val1&amp;param2=val2">%msg%</a>
                </body>
            </html>');
        $this->given->theSuperComponent_In_WithTheBody('deeplink\Super', 'deeplink', '
        function doGet() {
            return array(
                "sub" => new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub::$CLASS)
            );
        }');
        $this->given->theFile_In_WithContent('super.html', 'deeplink', '<html><body>Hello %sub%</body></html>');

        $this->when->iSendTheRequestTo('deeplink\Module');

        $this->then->theHtmlResponseBodyShouldBe(
            '<html>
                <head></head>
                <body>
                    Hello <a href="/base/super.html?.[sub][~]=/base/some/link.html&.[sub][param1][map][map2]=val1&.[sub][param2]=val2">World</a>
                </body>
            </html>');
    }

    function testSubTarget() {
        $this->given->theFolder_WithModule('subtarget');
        $this->given->theSubComponent_In_WithTemplate('subtarget\Not', 'subtarget', '<html><head></head><body>NOT</body></html>');

        $this->given->theFolder('subtarget/inner');
        $this->given->theSubComponent_In_WithTemplate('subtarget\inner\Sub', 'subtarget/inner',
            '<html><head></head><body>%msg%</body></html>');

        $this->given->theSuperComponent_In_WithTheBody('subtarget\Super', 'subtarget', '
        function doGet() {
            $sub = new \watoki\webco\controller\sub\HtmlSubComponent($this, Not::$CLASS);
            $sub->getState()->set(\watoki\webco\controller\SuperComponent::PARAMETER_TARGET, $this->getBaseRoute() . "inner/sub.html");
            return array(
                "sub" => $sub
            );
        }');
        $this->given->theFile_In_WithContent('super.html', 'subtarget', '<html><body>Hello <a href="super.html">%sub%</a></body></html>');

        $this->when->iSendTheRequestTo('subtarget\Module');

        $this->then->theHtmlResponseBodyShouldBe(
            '<html>
                <head></head>
                <body>
                    Hello <a href="super.html">World</a>
                </body>
            </html>');
    }

    function testReplaceFormFieldNames() {
        $this->given->theFolder_WithModule('formfields');
        $this->given->theSubComponent_In_WithTemplate('formfields\Sub', 'formfields',
            '<html>
                <head></head>
                <body>
                    <form action="sub.html" method="post">
                        <input name="field[1]">
                        <textarea name="field[2]"></textarea>
                        <select name="field[3]"></select>
                    </form>
                </body>
            </html>');
        $this->given->theSuperComponent_In_WithTheBody('formfields\Super', 'formfields', '
        function doGet() {
            return array(
                "sub" => new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub::$CLASS)
            );
        }');
        $this->given->theFile_In_WithContent('super.html', 'formfields', '<html><body>Hello  %sub%</body></html>');

        $this->when->iSendTheRequestTo('formfields\Module');

        $this->then->theHtmlResponseBodyShouldBe(
            '<html>
                <head></head>
                <body>
                    Hello
                    <form action="/base/super.html?.[.]=sub" method="post">
                        <input name=".[sub][field][1]">
                        <textarea name=".[sub][field][2]"></textarea>
                        <select name=".[sub][field][3]"></select>
                    </form>
                </body>
            </html>');
    }

    function testPrimaryAction() {
        $this->given->theFolder_WithModule('primaryactioncomp');
        $this->given->theSubComponent_In_WithTemplate('primaryactioncomp\Sub', 'primaryactioncomp',
            '<html>
                <head></head>
                <body>
                    <form action="sub.html" method="post"></form>
                    <a href="sub.html?action=myAction">%msg%</a>
                </body>
            </html>');
        $this->given->theSuperComponent_In_WithTheBody('primaryactioncomp\Super', 'primaryactioncomp', '
        function doGet() {
            return array(
                "sub" => new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub::$CLASS)
            );
        }');
        $this->given->theFile_In_WithContent('super.html', 'primaryactioncomp', '<html><body>Hello  %sub%</body></html>');

        $this->when->iSendTheRequestTo('primaryactioncomp\Module');

        $this->then->theHtmlResponseBodyShouldBe(
            '<html>
                <head></head>
                <body>
                    Hello
                    <form action="/base/super.html?.[.]=sub" method="post"></form>
                    <a href="/base/super.html?.[.]=sub&.[sub][action]=myAction">World</a>
                </body>
            </html>');
    }

    function testOmitPrimaryRequest() {
        $this->given->theFolder_WithModule('omitprimary');
        $this->given->theSubComponent_In_WithTemplate('omitprimary\Sub', 'omitprimary',
            '<html>
                <head></head>
                <body>
                    <form action="sub.html" method="get"></form>
                    <a href="sub.html">%msg%</a>
                </body>
            </html>');
        $this->given->theSuperComponent_In_WithTheBody('omitprimary\Super', 'omitprimary', '
        function doGet() {
            return array(
                "sub" => new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub::$CLASS)
            );
        }');
        $this->given->theFile_In_WithContent('super.html', 'omitprimary', '<html><body>Hello  %sub%</body></html>');

        $this->when->iSendTheRequestTo('omitprimary\Module');

        $this->then->theHtmlResponseBodyShouldBe(
            '<html>
                <head></head>
                <body>
                    Hello
                    <form action="/base/super.html" method="get"></form>
                    <a href="/base/super.html">World</a>
                </body>
            </html>');
    }

    function testDefaultRoute() {
        $this->given->theFolder_WithModule('defroute');
        $this->given->theSubComponent_In_WithTemplate('defroute\Sub', 'defroute',
            '<html>
                <head></head>
                <body>
                    <a href="sub.html?param1=val1">%msg%</a>
                </body>
            </html>');
        $this->given->theSuperComponent_In_WithTheBody('defroute\Super', 'defroute', '
        function doGet() {
            return array(
                "sub" => new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub::$CLASS)
            );
        }');
        $this->given->theFile_In_WithContent('super.html', 'defroute', '<html><body>Hello %sub%</body></html>');

        $this->when->iSendTheRequestTo('defroute\Module');

        $this->then->theHtmlResponseBodyShouldBe(
            '<html>
                <head></head>
                <body>
                    Hello <a href="/base/super.html?.[sub][param1]=val1">World</a>
                </body>
            </html>');
    }

    function testCollectState() {
        $this->given->theFolder_WithModule('colstate');
        $this->given->theSubComponent_In_WithTemplate('colstate\Sub1', 'colstate',
            '<html><head></head><body>Sub1:%msg%</body></html>');
        $this->given->theSubComponent_In_WithTemplate('colstate\Sub2', 'colstate',
            '<html><head></head><body><a href="sub2.html">Sub2</a>:%msg%</body></html>');
        $this->given->theSuperComponent_In_WithTheBody('colstate\Super', 'colstate', '
        function doGet() {
            $sub1 = new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub1::$CLASS);
            $sub1->getState()->set("param1", "val1");

            $sub2 = new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub2::$CLASS);
            return array(
                "sub1" => $sub1,
                "sub2" => $sub2
            );
        }');
        $this->given->theFile_In_WithContent('super.html', 'colstate', '<html><body>Hello %sub1%,  hello %sub2%</body></html>');

        $this->when->iSendTheRequestTo('colstate\Module');

        $this->then->theHtmlResponseBodyShouldBe(
            '<html><head></head><body>
                Hello Sub1:World,
                hello <a href="/base/super.html?.[sub1][param1]=val1">Sub2</a>:World
            </body></html>');
    }

    function testDefaultStateByConstructor() {
        $this->given->theFolder_WithModule('defbyconstr');
        $this->given->theSubComponent_In_WithTemplate('defbyconstr\Sub1', 'defbyconstr',
            '<html><head></head><body>Sub1:%msg%</body></html>');
        $this->given->theSubComponent_In_WithTemplate('defbyconstr\Sub2', 'defbyconstr',
            '<html><head></head><body><a href="sub2.html">Sub2</a>:%msg%</body></html>');
        $this->given->theSuperComponent_In_WithTheBody('defbyconstr\Super', 'defbyconstr', '
        function doGet() {
            $sub1 = new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub1::$CLASS,
                new \watoki\collections\Map(array("param1" => "val1", "param2" => "val2", "param3" => "val3")));
            $sub1->getState()->set("param1", "val1");
            $sub1->getState()->set("param2", "other");
            $sub1->getState()->set("param4", "new");

            $sub2 = new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub2::$CLASS);
            return array(
                "sub1" => $sub1,
                "sub2" => $sub2
            );
        }');
        $this->given->theFile_In_WithContent('super.html', 'defbyconstr', '<html><body>Hello %sub1%,  hello %sub2%</body></html>');

        $this->when->iSendTheRequestTo('defbyconstr\Module');

        $this->then->theHtmlResponseBodyShouldBe(
            '<html><head></head><body>
                Hello Sub1:World,
                hello <a href="/base/super.html?.[sub1][param2]=other&.[sub1][param4]=new">Sub2</a>:World
            </body></html>');
    }

    function testDefaultStateByAction() {
        $this->given->theFolder_WithModule('defaultargs');
        $this->given->theComponent_In_WithTheBody('defaultargs\Sub1', 'defaultargs', '
        function doGet($param1, $param2 = "default") {
            return array("msg" => $param1 . ":" . $param2);
        }');
        $this->given->theFile_In_WithContent('sub1.html', 'defaultargs', '<html><head></head><body>Sub1:%msg%</body></html>');

        $this->given->theSubComponent_In_WithTemplate('defaultargs\Sub2', 'defaultargs',
            '<html><head></head><body><a href="sub2.html">Sub2</a>:%msg%</body></html>');
        $this->given->theSuperComponent_In_WithTheBody('defaultargs\Super', 'defaultargs', '
        function doGet() {
            $sub1 = new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub1::$CLASS,
                new \watoki\collections\Map(array("param1" => "World")));
            $sub1->getState()->set("param2", "default");

            $sub2 = new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub2::$CLASS);
            return array(
                "sub1" => $sub1,
                "sub2" => $sub2
            );
        }');
        $this->given->theFile_In_WithContent('super.html', 'defaultargs', '<html><body>Hello %sub1%,  hello %sub2%</body></html>');

        $this->when->iSendTheRequestTo('defaultargs\Module');

        $this->then->theHtmlResponseBodyShouldBe(
            '<html><head></head><body>
                Hello Sub1:World:default,
                hello <a href="/base/super.html">Sub2</a>:World
            </body></html>');
    }

    // TODO We need a test with deeply nested SubComponents

}

class CompositionTest_Given extends steps\CompositionTestGiven {
}