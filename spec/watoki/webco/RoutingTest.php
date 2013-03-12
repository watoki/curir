<?php
namespace spec\watoki\webco;

/**
 * @property RoutingTest_When when
 * @property RoutingTest_Then then
 */
use spec\watoki\webco\steps\Then;
use spec\watoki\webco\steps\When;
use watoki\factory\Factory;
use watoki\webco\Request;
use \watoki\webco\controller\Module;

class RoutingTest extends Test {

    function testDefaultComponent() {
        $this->given->theFolder('defaultcomp');
        $this->given->theFolder('defaultcomp/inner');
        $this->given->theClass_In_Extending_WithTheBody('defaultcomp\Module', 'defaultcomp', '\watoki\webco\controller\Module', '');
        $this->given->theClass_In_Extending_WithTheBody('defaultcomp\inner\Index', 'defaultcomp/inner', '\watoki\webco\controller\Component', '
            public function doGet() {return "hey";}
            public function doRender($model, $template) {}
        ');

        $this->given->theRequestMethodIs(Request::METHOD_GET);
        $this->given->theRequestResourceIs('inner/');
        $this->when->iSendTheRequestTo('defaultcomp\Module');

        $this->then->theResponseBodyShouldBe('"hey"');
    }

    function testRouteToSister() {
        $this->markTestIncomplete();
    }

    function testFindChild() {
        $this->given->theFolder('findchild');
        $this->given->theClass_In_Extending_WithTheBody('findchild\ParentModule', 'findchild', '\watoki\webco\controller\Module', '');
        $this->given->theClass_In_Extending_WithTheBody('findchild\ChildModule', 'findchild', '\watoki\webco\controller\Module', '');

        $this->when->iAsk_ToFind('findchild\ParentModule', 'findchild\ChildModule');

        $this->then->itShouldReturnAnInstanceOf('findchild\ChildModule');
    }

    function testFindGrandChild() {
        $this->given->theFolder('findgrand');
        $this->given->theFolder('findgrand/grand');
        $this->given->theClass_In_Extending_WithTheBody('findgrand\ParentModule', 'findgrand', '\watoki\webco\controller\Module', '');
        $this->given->theClass_In_Extending_WithTheBody('findgrand\grand\ChildModule', 'findgrand/grand', '\watoki\webco\controller\Module', '');

        $this->when->iAsk_ToFind('findgrand\ParentModule', 'findgrand\grand\ChildModule');

        $this->then->itShouldReturnAnInstanceOf('findgrand\grand\ChildModule');
    }

    function testNonExistingChild() {
        $this->given->theFolder('findnone');
        $this->given->theClass_In_Extending_WithTheBody('findnone\ParentModule', 'findnone', '\watoki\webco\controller\Module', '');

        $this->when->iAsk_ToFind('findnone\ParentModule', 'findnone\grand\ChildModule');

        $this->then->itShouldNotFindIt();
    }

    function testFindAdoptedChild() {
        $this->markTestIncomplete();
    }

}

class RoutingTest_When extends When {

    public $found;

    public function iAsk_ToFind($parentClass, $childClass) {
        $factory = new Factory();
        /** @var $parent Module */
        $parent = $factory->getInstance($parentClass, array('route' => ''));
        $this->found = $parent->findController($childClass);
    }
}

/**
 * @property RoutingTest test
 */
class RoutingTest_Then extends Then {

    public function itShouldReturnAnInstanceOf($class) {
        $this->test->assertInstanceOf($class, $this->test->when->found);
    }

    public function itShouldNotFindIt() {
        $this->test->assertNull($this->test->when->found);
    }
}