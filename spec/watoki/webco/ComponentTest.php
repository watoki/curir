<?php
namespace spec\watoki\webco;

use spec\watoki\webco\steps\Given;
use watoki\webco\Request;

/**
 * @property ComponentTest_Given given
 */
class ComponentTest extends Test {

    public function testGetMethod() {
        $this->given->theFolder('gettest');
        $this->given->theRequestMethodIs(Request::METHOD_GET);
        $this->given->theComponent_In_WithTheMethod_ThatReturns('gettest\Component', 'gettest', 'doGet', '"found"');

        $this->when->iSendTheRequestTo('gettest\Component');

        $this->then->theResponseBodyShouldBe('found');
    }

    public function testPostMethod() {
        $this->given->theFolder('posttest');
        $this->given->theRequestMethodIs(Request::METHOD_POST);
        $this->given->theComponent_In_WithTheMethod_ThatReturns('posttest\Component', 'posttest', 'doPost', '"You have got mail."');

        $this->when->iSendTheRequestTo('posttest\Component');

        $this->then->theResponseBodyShouldBe('You have got mail.');
    }

    public function testActionParameter() {
        $this->given->theFolder('actiontest');
        $this->given->theRequestParameter_WithValue('action', 'myAction');
        $this->given->theComponent_In_WithTheMethod_ThatReturns('actiontest\Component', 'actiontest',
            'doMyAction', '"And action!"');

        $this->when->iSendTheRequestTo('actiontest\Component');

        $this->then->theResponseBodyShouldBe('And action!');
    }

    public function testArguments() {
        $this->given->theFolder('parametertest');
        $this->given->theRequestMethodIs(Request::METHOD_GET);
        $this->given->theComponent_In_WithTheMethod_WithParameters_ThatReturns('parametertest\Component', 'parametertest',
            'doGet', '$arg1, $arg2', '"$arg1 $arg2"');
        $this->given->theRequestParameter_WithValue('arg1', 'hello');
        $this->given->theRequestParameter_WithValue('arg2', 'world');

        $this->when->iSendTheRequestTo('parametertest\Component');

        $this->then->theResponseBodyShouldBe('hello world');
    }

    public function testDefaultArguments() {
        $this->given->theFolder('defarg');
        $this->given->theRequestMethodIs(Request::METHOD_GET);
        $this->given->theComponent_In_WithTheMethod_WithParameters_ThatReturns('defarg\Component', 'defarg',
            'doGet', '$arg1, $arg2 = "default"', '"$arg1 $arg2"');
        $this->given->theRequestParameter_WithValue('arg1', 'hello');

        $this->when->iTryToSendTheRequestTo('defarg\Component');

        $this->then->theResponseBodyShouldBe('hello default');
    }

    public function testMissingParameter() {
        $this->given->theFolder('missingparam');
        $this->given->theRequestMethodIs(Request::METHOD_GET);
        $this->given->theComponent_In_WithTheMethod_WithParameters_ThatReturns('missingparam\Component', 'missingparam',
            'doGet', '$arg1, $arg2 = "default"', '{}');

        $this->when->iTryToSendTheRequestTo('missingparam\Component');

        $this->then->anExceptionContaining_ShouldBeThrown('arg1');
    }

    public function testComponentWithTemplate() {
        $this->given->theFolder('templatetest');
        $this->given->theComponent_In_WithTheMethod_ThatReturns('templatetest\Template', 'templatetest', 'doGet', '{"test":"World"}');
        $this->given->theFile_In_WithContent('template.html', 'templatetest', 'Hello %test%');

        $this->given->theRequestMethodIs(Request::METHOD_GET);
        $this->when->iSendTheRequestTo('templatetest\Template');

        $this->then->theResponseBodyShouldBe('Hello World');
    }

}

class ComponentTest_Given extends Given {

    public function theComponent_In_WithTheMethod_ThatReturns($className, $folder, $method, $returnJson) {
        $this->theComponent_In_WithTheMethod_WithParameters_ThatReturns($className, $folder, $method, '', $returnJson);
    }

    public function theComponent_In_WithTheMethod_WithParameters_ThatReturns($className, $folder, $method, $params, $returnJson) {
        $returnJson = str_replace('"', '\"', $returnJson);

        $this->theClass_In_Extending_WithTheBody($className, $folder, '\watoki\webco\controller\Component', "
            public function $method ($params) {
                return json_decode(\"$returnJson\", true);
            }

            protected function doRender(\$template, \$model) {
                foreach (\$model as \$key => \$value) {
                    \$template = str_replace('%' . \$key . '%', \$value, \$template);
                }
                return \$template;
            }
        ");
    }
}