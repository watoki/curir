<?php
namespace spec\watoki\webco;

/**
 * @property RoutingTest_When when
 * @property RoutingTest_Then then
 */
use watoki\factory\Factory;
use \watoki\webco\controller\Module;

class RoutingTest extends Test {

    function testRouteToSister() {
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
    }

}

class RoutingTest_When extends Step {

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
class RoutingTest_Then extends Step {

    public function itShouldReturnAnInstanceOf($class) {
        $this->test->assertInstanceOf($class, $this->test->when->found);
    }

    public function itShouldNotFindIt() {
        $this->test->assertNull($this->test->when->found);
    }
}