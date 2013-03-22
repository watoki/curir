<?php
namespace spec\watoki\webco;

use spec\watoki\webco\steps\CompositionTestGiven;
use spec\watoki\webco\steps\Given;
use spec\watoki\webco\steps\Then;
use watoki\collections\Map;
use watoki\webco\Request;
use watoki\webco\Response;

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

        $this->given->theSuperComponent_In_WithTheBody('restoresubs\Super', 'restoresubs', '
        function doGet() {
            return array(
                "sub" => new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub::$CLASS)
            );
        }');
        $this->given->theFile_In_WithContent('super.html', 'restoresubs', '<html><body>Hello %sub%</body></html>');

        $this->given->theRequestParameterHasTheState(new Map(array(
            'sub' => new Map(array(
                'param1' => 'All',
                'param2' => 'World'
            ))
        )));
        $this->when->iSendTheRequestTo('restoresubs\Module');

        $this->then->theHtmlResponseBodyShouldBe('<html><head></head><body>Hello All:World</body></html>');
    }

    function testPrimaryActionFirst() {
        $this->given->theFolder_WithModule('primaryaction');
        $this->given->theComponent_In_WithTheBody('primaryaction\Sub', 'primaryaction', '
        public static $executed = "NOT";
        function doMyAction($param1, $param2) {
            self::$executed = "First";
            return array("msg" => $param1 . " " . $param2);
        }');
        $this->given->theFile_In_WithContent('sub.html', 'primaryaction', '<html><head></head><body>%msg%</body></html>');

        $this->given->theSuperComponent_In_WithTheBody('primaryaction\Super', 'primaryaction', '
        function doGet($param) {
            return array(
                "msg" => Sub::$executed . ":" . $param,
                "sub" => new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub::$CLASS)
            );
        }');
        $this->given->theFile_In_WithContent('super.html', 'primaryaction', '<html><body>%msg% %sub%</body></html>');

        $this->given->theRequestParameter_WithValue('param', 'Greetings');
        $this->given->thePrimaryRequestIsFor('sub');
        $this->given->theRequestParameterHasTheState(new Map(array(
            'sub' => new Map(array(
                '~' => '/base/Sub',
                'action' => 'myAction',
                'param1' => 'my',
                'param2' => 'Friends'
            ))
        )));
        $this->when->iSendTheRequestTo('primaryaction\Module');

        $this->then->theHtmlResponseBodyShouldBe('<html><body>First:Greetings my Friends</body></html>');
    }

    function testPrimaryActionOnlyOnce() {
        $this->given->theFolder_WithModule('primaryfirst');
        $this->given->theComponent_In_WithTheBody('primaryfirst\Sub', 'primaryfirst', '
        public static $called = 0;
        function doGet($param1, $param2) {
            self::$called++;
            return array("msg" => $param1 . " " . $param2 . " " . self::$called);
        }');
        $this->given->theFile_In_WithContent('sub.html', 'primaryfirst', '<html><head></head><body>%msg%</body></html>');

        $this->given->theSuperComponent_In_WithTheBody('primaryfirst\Super', 'primaryfirst', '
        function doGet($param) {
            return array(
                "msg" => $param,
                "sub" => new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub::$CLASS)
            );
        }');
        $this->given->theFile_In_WithContent('super.html', 'primaryfirst', '<html><body>%msg% %sub%</body></html>');

        $this->given->theRequestParameter_WithValue('param', 'Super');
        $this->given->thePrimaryRequestIsFor('sub');
        $this->given->theRequestParameterHasTheState(new Map(array(
            'sub' => new Map(array(
                '~' => '/base/Sub',
                'param1' => 'hello',
                'param2' => 'world'
            ))
        )));
        $this->when->iSendTheRequestTo('primaryfirst\Module');

        $this->then->theHtmlResponseBodyShouldBe('<html><body>Super hello world 1</body></html>');
    }

    function testPrimaryRequestWithState() {
        $this->markTestIncomplete();
    }

    function testSubTarget() {
        $this->given->theFolder_WithModule('subtargetrequest');
        $this->given->theSubComponent_In_WithTemplate('subtargetrequest\Sub1', 'subtargetrequest',
            '<html><head></head><body>%msg% of Sub1</body></html>');
        $this->given->theSubComponent_In_WithTemplate('subtargetrequest\Sub2', 'subtargetrequest',
            '<html><head></head><body>%msg% of Sub2</body></html>');#


        $this->given->theSuperComponent_In_WithTheBody('subtargetrequest\Super', 'subtargetrequest', '
        function doGet() {
            return array(
                "msg" => "Hello",
                "sub" => new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub1::$CLASS),
            );
        }');
        $this->given->theFile_In_WithContent('super.html', 'subtargetrequest', '<html><head></head><body>%msg% %sub%</body></html>');

        $this->given->theRequestParameterHasTheState(new Map(array(
            'sub' => new Map(array(
                '~' => '/base/subtargetrequest/sub2'
            ))
        )));

        $this->given->theModuleRouteIs('/base/subtargetrequest/');
        $this->when->iSendTheRequestTo('subtargetrequest\Module');

        $this->then->theHtmlResponseBodyShouldBe('<html><head></head><body>Hello World of Sub2</body></html>');
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

        $this->given->theSuperComponent_In_WithTheBody('subredirect\Super', 'subredirect', '
        function doGet() {
            return array(
                "sub1" => new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub::$CLASS),
                "sub2" => new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub2::$CLASS),
            );
        }');

        $this->given->theRequestParameter_WithValue('param', 'Super');
        $this->given->theRequestParameterHasTheState(new Map(array(
            'sub1' => new Map(array(
                'param1' => 'hello'
            ))
        )));
        $this->when->iSendTheRequestTo('subredirect\Module');

        $this->then->theUrlDecodedResponseHeader_ShouldBe(Response::HEADER_LOCATION,
            '/base/super.html?param=Super&.[sub1][param][1]=a&.[sub1][param][2]=b&.[sub1][~]=/base/somewhere/else&.[sub2][param]=x&.[sub2][~]=/base/not/here#foo');
    }

    function testPrimaryRedirect() {
        $this->given->theFolder_WithModule('primaryredirect');
        $this->given->theComponent_In_WithTheBody('primaryredirect\Sub', 'primaryredirect', '
        function doGet() {
            return $this->redirect(new \watoki\webco\Url("somewhere/else?param[1]=a&param[2]=b#bar"));
        }');
        $this->given->theComponent_In_WithTheBody('primaryredirect\Sub2', 'primaryredirect', '
        function doGet() {
            throw new \Exception("Should not be called");
        }');

        $this->given->theSuperComponent_In_WithTheBody('primaryredirect\Super', 'primaryredirect', '
        function doGet() {
            return array(
                "sub1" => new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub::$CLASS),
                "sub2" => new \watoki\webco\controller\sub\HtmlSubComponent($this, Sub2::$CLASS),
            );
        }');

        $this->given->theRequestParameter_WithValue('param', 'Super');
        $this->given->thePrimaryRequestIsFor('sub1');
        $this->given->theRequestParameterHasTheState(new Map(array(
            'sub1' => new Map(array(
                '~' => '/base/Sub',
                'param1' => 'hello'
            ))
        )));
        $this->when->iSendTheRequestTo('primaryredirect\Module');

        $this->then->theUrlDecodedResponseHeader_ShouldBe(Response::HEADER_LOCATION,
            '/base/super.html?param=Super&.[sub1][param][1]=a&.[sub1][param][2]=b&.[sub1][~]=/base/somewhere/else#bar');
    }

}

class CompositeRequestTest_Given extends CompositionTestGiven {

    public function theRequestParameterHasTheState($param) {
        $this->theRequestParameter_WithValue('.', $param);
    }

    public function thePrimaryRequestIsFor($subName) {
        $this->theRequestParameter_WithValue('!', $subName);
    }
}

class CompositeRequestTest_Then extends Then {

    public function theUrlDecodedResponseHeader_ShouldBe($header, $value) {
        $this->test->assertEquals($value, urldecode($this->test->when->response->getHeaders()->get($header)));
    }

}