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
        $this->given->theComponent_In_WithTheBody('snippet\Super', 'snippet', '
        function doGet() {
            $sub = new \watoki\webco\controller\sub\PlainSubComponent($this, Sub::$CLASS);
            return array(
                "sub" => $sub->render()
            );
        }');
        $this->given->theFile_In_WithContent('super.html', 'snippet', 'Hello %sub%');

        $this->when->iSendTheRequestTo('snippet\Module');

        $this->then->theResponseBodyShouldBe('Hello World!');
    }

    function testIncludeDocument() {
        $this->given->theFolder_WithModule('document');
        $this->given->theSubComponent_In_WithTemplate('document\Sub', 'document',
            '<html>
                <head><title>Sub Component</title></head>
                <body><b>%msg%</b></body>
            </html>');
        $this->given->theComponent_In_WithTheBody('document\Super', 'document', '
        function doGet() {
            $sub = new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub::$CLASS);
            return array(
                "sub" => $sub->render()
            );
        }');
        $this->given->theFile_In_WithContent('super.html', 'document', '<html><body>Hello %sub%</body></html>');

        $this->when->iSendTheRequestTo('document\Module');

        $this->then->theResponseBodyShouldBe('<html><body>Hello <b>World</b></body></html>');
    }

    function testAbsorbAssets() {
        $this->given->theFolder_WithModule('assets');
        $this->given->theSubComponent_In_WithTemplate('assets\Sub', 'assets',
            '<html>
                <head>
                    <link href="http://twitter.github.com/bootstrap/assets/css/bootstrap.css" rel="stylesheet">
                </head>
                <body><i>%msg%</i></body>
            </html>');
        $this->given->theComponent_In_WithTheBody('assets\Super', 'assets', '
        function doGet() {
            $this->sub = new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub::$CLASS);
            return array(
                "sub" => $this->sub->render()
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
        $this->given->theComponent_In_WithTheBody('relative\Super', 'relative', '
        function doGet() {
            $this->sub = new \watoki\webco\controller\sub\HtmlSubComponent($this, inner\Sub::$CLASS);
            return array(
                "sub" => $this->sub->render()
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
        $this->given->theComponent_In_WithTheBody('deeplink\Super', 'deeplink', '
        function doGet() {
            $this->sub = new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub::$CLASS);
            return array(
                "sub" => $this->sub->render()
            );
        }');
        $this->given->theFile_In_WithContent('super.html', 'deeplink', '<html><body>Hello %sub%</body></html>');

        $this->when->iSendTheRequestTo('deeplink\Module');

        $this->then->theHtmlResponseBodyShouldBe(
            '<html>
                <head></head>
                <body>
                    Hello <a href="/base/super.html?.[sub][.]=/base/some/link.html&.[sub][param1][map][map2]=val1&.[sub][param2]=val2">World</a>
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
        $this->given->theComponent_In_WithTheBody('defroute\Super', 'defroute', '
        function doGet() {
            $this->sub = new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub::$CLASS);
            return array(
                "sub" => $this->sub->render()
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

    }

    function testDefaultStateByConstructor() {
//        $this->given->theFolder_WithModule('defaultbyc');
//        $this->given->theFolder_WithModule('defaultbyc');
//        $this->given->theComponent_In_WithTheBody('defaultbyc\Sub', 'defaultbyc', '
//        function doGet($arg1) {
//            return array("msg" => $arg1);
//        }');
//        $this->given->theFile_In_WithContent('sub.html', 'defaultbyc',
//            '<html><head></head><body><a href="my/link.html">%msg%</a></body></html>');
//
//        $this->given->theComponent_In_WithTheBody('defaultbyc\Super', 'defaultbyc', '
//        function doGet() {
//            $this->sub = new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub::$CLASS, array("World"));
//            return array(
//                "sub" => $this->sub->render()
//            );
//        }');
//        $this->given->theFile_In_WithContent('super.html', 'defaultbyc', '<html><body>Hello %sub%</body></html>');
//
//        $this->when->iSendTheRequestTo('defaultbyc\Module');
//
//        $this->then->theHtmlResponseBodyShouldBe(
//            '<html>
//                <head></head>
//                <body>
//                    Hello <a href="/base/super.html?.[sub][.]=/base/my/link.html&.[sub][param1][map][map2]=val1&.[sub][param2]=val2">World</a>
//                </body>
//            </html>');
    }

    function testDefaultStateByAction() {
        $this->given->theFolder_WithModule('defaultargs');
        $this->given->theComponent_In_WithTheBody('defaultargs\Sub', 'defaultargs', '
        function doGet($arg1, $arg2 = "default") {
            return array("msg" => $arg1 . $arg2);
        }');
        $this->given->theFile_In_WithContent('sub.html', 'defaultargs', '<html><head></head><body>%msg%</body></html>');
    }

    function testDeepLinkTarget() {
    }

}

class CompositionTest_Given extends Given {

    public function theFolder_WithModule($folder) {
        $this->theFolder($folder);
        $this->theModule_In($folder . '\Module', $folder);
    }

    public function theSubComponent_In_WithTemplate($className, $folder, $template) {
        $shortClassName = Liste::split('\\', $className)->pop();
        $this->theComponent_In_WithTheBody($className, $folder, '
        function doGet() {
            return array("msg" => "World");
        }');
        $this->theFile_In_WithContent(lcfirst($shortClassName) . '.html', $folder, $template);
    }

    public function theComponent_In_WithTheBody($className, $folder, $body) {
        $this->theClass_In_Extending_WithTheBody($className, $folder, '\watoki\webco\controller\Component', "
            protected function doRender(\$model, \$template) {
                foreach (\$model as \$key => \$value) {
                    \$template = str_replace('%' . \$key . '%', \$value, \$template);
                }
                return \$template;
            }

            $body
        ");
    }

    public function theModule_In($moduleClass, $folder) {
        $this->theClass_In_Extending_WithTheBody($moduleClass, $folder, '\watoki\webco\controller\Module', '');
    }
}