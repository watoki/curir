<?php
namespace spec\watoki\webco;

use spec\watoki\webco\steps\Given;
use watoki\webco\Request;

/**
 * @property CompositionTest_Given given
 */
class CompositionTest extends Test {

    function testIncludeSnippet() {
        $this->given->theFolder('snippet');
        $this->given->theClass_In_Extending_WithTheBody('snippet\Module', 'snippet', '\watoki\webco\Module', '');
        $this->given->theComponent_In_WithTheBody('snippet\Sub', 'snippet', '
        function doGet() {
            return array("msg" => "World");
        }');
        $this->given->theFile_In_WithContent('sub.html', 'snippet', '%msg%!');
        $this->given->theComponent_In_WithTheBody('snippet\Super', 'snippet', '
        function doGet() {
            $sub = new \watoki\webco\SubComponent($this->getRoot(),  Sub::$CLASS);
            return array(
                "sub" => $sub->render()
            );
        }');
        $this->given->theFile_In_WithContent('super.html', 'snippet', 'Hello %sub%');

        $this->given->theRequestMethodIs(Request::METHOD_GET);
        $this->given->theRequestResourceIs('super.html');
        $this->when->iSendTheRequestTo('snippet\Module');

        $this->then->theResponseBodyShouldBe('Hello World!');
    }

    function testIncludeDocument() {
    }

    function testAbsorbAssets() {
    }

    function testRelativeUrls() {
    }

    function testDeepLinkReplacement() {
    }

    function testDeepLinkTarget() {
    }

    function testDeepLinkHandling() {
    }

    function testPrimaryAction() {
    }

    function testPrimaryActionFirst() {
    }

    function testDeepRedirect() {
    }

}

class CompositionTest_Given extends Given {

    public function theComponent_In_WithTheBody($className, $folder, $body) {
        $this->theClass_In_Extending_WithTheBody($className, $folder, '\watoki\webco\Component', "
            protected function doRender(\$model, \$template) {
                foreach (\$model as \$key => \$value) {
                    \$template = str_replace('%' . \$key . '%', \$value, \$template);
                }
                return \$template;
            }

            $body
        ");
    }
}