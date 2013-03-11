<?php
namespace spec\watoki\webco;

use watoki\collections\Map;
use watoki\webco\Request;

require_once 'CompositionTest.php';

/**
 * @property CompositeRequestTest_Given given
 */
class CompositeRequestTest extends Test {

    protected function setUp() {
        parent::setUp();

        $this->given->theRequestMethodIs(Request::METHOD_GET);
        $this->given->theRequestResourceIs('super.html');
    }

    function testRestoreSubComponents() {
        $this->given->theFolder_WithModule('restoresubs');
        $this->given->theComponent_In_WithTheBody('restoresubs\Sub', 'restoresubs', '
        function doGet($param1, $param2) {
            return array("msg" => $param1 . ":" . $param2);
        }');
        $this->given->theFile_In_WithContent('sub.html', 'restoresubs', '<html><head></head><body>%msg%</body></html>');

        $this->given->theComponent_In_WithTheBody('restoresubs\Super', 'restoresubs', '
        function __construct(\watoki\factory\Factory $factory, $route, \watoki\webco\controller\Module $parent = null) {
            parent::__construct($factory, $route, $parent);
            $this->sub = new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub::$CLASS);
        }

        function doGet() {
            return array(
                "subling" => $this->sub->render()
            );
        }');
        $this->given->theFile_In_WithContent('super.html', 'restoresubs', '<html><body>Hello %subling%</body></html>');

        $this->given->theRequestParameter_WithValue('.', new Map(array(
            'sub' => new Map(array(
                'param1' => 'All',
                'param2' => 'World'
            ))
        )));
        $this->when->iSendTheRequestTo('restoresubs\Module');

        $this->then->theHtmlResponseBodyShouldBe('<html><head></head><body>Hello All:World</body></html>');
    }

    function testPrimaryAction() {
        $this->markTestIncomplete();
    }

    function testPrimaryActionFirst() {
        $this->markTestIncomplete();
    }

    function testDeepRedirect() {
        $this->markTestIncomplete();
    }

}

class CompositeRequestTest_Given extends CompositionTest_Given {

}