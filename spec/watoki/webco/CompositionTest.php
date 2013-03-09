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
            $sub = new \watoki\webco\controller\sub\PlainSubComponent("sub", $this->getRoot(), Sub::$CLASS);
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
            $sub = new \watoki\webco\controller\sub\HtmlSubComponent("sub", $this->getRoot(), Sub::$CLASS);
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
            $this->sub = new \watoki\webco\controller\sub\HtmlSubComponent("sub", $this->getRoot(), Sub::$CLASS);
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
    }

    function testDeepLinkReplacement() {
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