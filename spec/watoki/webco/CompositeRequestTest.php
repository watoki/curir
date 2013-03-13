<?php
namespace spec\watoki\webco;

use spec\watoki\webco\steps\Then;
use watoki\collections\Map;
use watoki\webco\Request;
use watoki\webco\Response;

require_once 'CompositionTest.php';

/**
 * @property CompositeRequestTest_Given given
 * @property CompositeRequestTest_Then then
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

    function testSubTarget() {
        $this->given->theFolder_WithModule('subtarget');
        $this->given->theSubComponent_In_WithTemplate('subtarget\Sub1', 'subtarget',
            '<html><head></head><body>%msg% of Sub1</body></html>');
        $this->given->theSubComponent_In_WithTemplate('subtarget\Sub2', 'subtarget',
            '<html><head></head><body>%msg% of Sub2</body></html>');#


        $this->given->theComponent_In_WithTheBody('subtarget\Super', 'subtarget', '
        function __construct(\watoki\factory\Factory $factory, $route, \watoki\webco\controller\Module $parent = null) {
            parent::__construct($factory, $route, $parent);
            $this->sub = new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub1::$CLASS);
        }

        function doGet() {
            return array(
                "msg" => "Hello",
                "subling" => $this->sub->render(),
            );
        }');
        $this->given->theFile_In_WithContent('super.html', 'subtarget', '<html><head></head><body>%msg% %subling%</body></html>');

        $this->given->theRequestParameterHasTheState(new Map(array('sub' => new Map(array(
            '~' => '/base/subtarget/sub2'
        )))));

        $this->given->theModuleRouteIs('/base/subtarget/');
        $this->when->iSendTheRequestTo('subtarget\Module');

        $this->then->theHtmlResponseBodyShouldBe('<html><head></head>Hello World of Sub2</html>');
    }

    function testSubRedirect() {
        $this->given->theFolder_WithModule('subredirect');
        $this->given->theComponent_In_WithTheBody('subredirect\Sub', 'subredirect', '
        function doGet() {
            return $this->redirect(new \watoki\webco\Url("somewhere/else?param[1]=a&param[2]=b#bar"));
        }');
        $this->given->theComponent_In_WithTheBody('subredirect\Sub2', 'subredirect', '
        function doGet() {
            return $this->redirect(new \watoki\webco\Url("not/here?param=x#foo"));
        }');

        $this->given->theComponent_In_WithTheBody('subredirect\Super', 'subredirect', '
        function __construct(\watoki\factory\Factory $factory, $route, \watoki\webco\controller\Module $parent = null) {
            parent::__construct($factory, $route, $parent);
            $this->sub = new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub::$CLASS);
            $this->sub2 = new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub2::$CLASS);
        }

        function doGet() {
            return array(
                "subling1" => $this->sub->render(),
                "subling2" => $this->sub2->render(),
            );
        }');

        $this->given->theRequestParameter_WithValue('param', 'Super');
        $this->given->theRequestParameterHasTheState(new Map(array(
            'sub' => new Map(array(
                'param1' => 'hello'
            ))
        )));
        $this->when->iSendTheRequestTo('subredirect\Module');

        $this->then->theUrlDecodedResponseHeader_ShouldBe(Response::HEADER_LOCATION,
            '/base/super.html?param=Super&.[sub][param][1]=a&.[sub][param][2]=b&.[sub][~]=/base/somewhere/else&.[sub2][param]=x&.[sub2][~]=/base/not/here#foo');
    }

    function testPrimaryRedirect() {
        $this->given->theFolder_WithModule('primaryredirect');
        $this->given->theComponent_In_WithTheBody('primaryredirect\Sub', 'primaryredirect', '
        function doGet() {
            return $this->redirect(new \watoki\webco\Url("somewhere/else?param[1]=a&param[2]=b#bar"));
        }');
        $this->given->theComponent_In_WithTheBody('primaryredirect\Sub2', 'primaryredirect', '
        function doGet() {
            throw new \Exception("Shuold not be called");
        }');

        $this->given->theComponent_In_WithTheBody('primaryredirect\Super', 'primaryredirect', '
        function __construct(\watoki\factory\Factory $factory, $route, \watoki\webco\controller\Module $parent = null) {
            parent::__construct($factory, $route, $parent);
            $this->sub = new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub::$CLASS);
            $this->sub2 = new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub2::$CLASS);
        }

        function doGet() {
            return array(
                "subling1" => $this->sub->render(),
                "subling2" => $this->sub2->render(),
            );
        }');

        $this->given->theRequestParameter_WithValue('param', 'Super');
        $this->given->theRequestParameterHasTheState(new Map(array(
            '.' => 'sub',
            'sub' => new Map(array(
                'param1' => 'hello'
            ))
        )));
        $this->when->iSendTheRequestTo('primaryredirect\Module');

        $this->then->theUrlDecodedResponseHeader_ShouldBe(Response::HEADER_LOCATION,
            '/base/super.html?param=Super&.[sub][param][1]=a&.[sub][param][2]=b&.[sub][~]=/base/somewhere/else#bar');
    }

}

class CompositeRequestTest_Given extends CompositionTest_Given {

    public function theRequestParameterHasTheState($param) {
        $this->theRequestParameter_WithValue('.', $param);
    }
}

class CompositeRequestTest_Then extends Then {

    public function theUrlDecodedResponseHeader_ShouldBe($header, $value) {
        $this->test->assertEquals($value, urldecode($this->test->when->response->getHeaders()->get($header)));
    }

}