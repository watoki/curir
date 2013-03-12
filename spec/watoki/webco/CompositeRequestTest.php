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

        $this->given->theRequestParameterHasTheState(new Map(array(
            'sub' => new Map(array(
                'param1' => 'All',
                'param2' => 'World'
            ))
        )));
        $this->when->iSendTheRequestTo('restoresubs\Module');

        $this->then->theHtmlResponseBodyShouldBe('<html><head></head><body>Hello All:World</body></html>');
    }

    function testPrimaryAction() {
        $this->given->theFolder_WithModule('primaryaction');
        $this->given->theComponent_In_WithTheBody('primaryaction\Sub', 'primaryaction', '
        function doMyAction($param1, $param2) {
            return array("msg" => $param1 . " " . $param2);
        }');
        $this->given->theFile_In_WithContent('sub.html', 'primaryaction', '<html><head></head><body>%msg%</body></html>');

        $this->given->theComponent_In_WithTheBody('primaryaction\Super', 'primaryaction', '
        function __construct(\watoki\factory\Factory $factory, $route, \watoki\webco\controller\Module $parent = null) {
            parent::__construct($factory, $route, $parent);
            $this->sub = new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub::$CLASS);
        }

        function doGet($param) {
            return array(
                "msg" => $param,
                "subling" => $this->sub->render()
            );
        }');
        $this->given->theFile_In_WithContent('super.html', 'primaryaction', '<html><head></head><body>%msg% %subling%</body></html>');

        $this->given->theRequestParameter_WithValue('param', 'Greetings');
        $this->given->theRequestParameterHasTheState(new Map(array(
            '.' => 'sub',
            'sub' => new Map(array(
                'action' => 'myAction',
                'param1' => 'my',
                'param2' => 'Friends'
            ))
        )));
        $this->when->iSendTheRequestTo('primaryaction\Module');

        $this->then->theHtmlResponseBodyShouldBe('<html><head></head><body>Greetings my Friends</body></html>');
    }

    function testPrimaryActionFirst() {
        $this->given->theFolder_WithModule('primaryfirst');
        $this->given->theComponent_In_WithTheBody('primaryfirst\Sub', 'primaryfirst', '
        public $called = 0;
        function doGet($param1, $param2) {
            $this->called++;
            global $something;
            $something = "First";
            return array("msg" => $param1 . " " . $param2 . " " . $this->called);
        }');
        $this->given->theFile_In_WithContent('sub.html', 'primaryfirst', '<html><head></head><body>%msg%</body></html>');

        $this->given->theComponent_In_WithTheBody('primaryfirst\Super', 'primaryfirst', '
        function __construct(\watoki\factory\Factory $factory, $route, \watoki\webco\controller\Module $parent = null) {
            parent::__construct($factory, $route, $parent);
            $this->sub = new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub::$CLASS);
        }

        function doGet($param) {
            global $something;
            return array(
                "msg" => $param . ":"  . $something . ":",
                "subling" => $this->sub->render()
            );
        }');
        $this->given->theFile_In_WithContent('super.html', 'primaryfirst', '<html><head></head><body>%msg% %subling%</body></html>');

        $this->given->theRequestParameter_WithValue('param', 'Last');
        $this->given->theRequestParameterHasTheState(new Map(array(
            '.' => 'sub',
            'sub' => new Map(array(
                'param1' => 'hello',
                'param2' => 'world'
            ))
        )));
        $this->when->iSendTheRequestTo('primaryfirst\Module');

        $this->then->theHtmlResponseBodyShouldBe('<html><head></head><body>Last:First: hello world 1</body></html>');
    }

    function testDeepRedirect() {
        $this->markTestIncomplete();
    }

}

class CompositeRequestTest_Given extends CompositionTest_Given {

    public function theRequestParameterHasTheState($param) {
        $this->theRequestParameter_WithValue('.', $param);
    }
}