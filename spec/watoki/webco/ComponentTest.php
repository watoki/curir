<?php
namespace spec\watoki\webco;

class ComponentTest extends Test {

    public function testGetMethod() {
    }

    public function testPostMethod() {
    }

    public function testActionParameter() {
    }

    public function testArguments() {
    }

    public function testDefaultArguments() {
    }

    public function testTemplate() {
    }

//    public function testComponentWithTemplate() {
//        $this->given->theFolder('templatetest');
//        $this->given->theModule_In('templatetest\TemplateModule', 'templatetest');
//        $this->given->theComponent_In('templatetest\IndexWithTemplate', 'templatetest', 'doGet', '{"test":"World"}');
//        $this->given->theFile_In_WithContent('indexWithTemplate.html', 'templatetest', 'Hello %test%');
//
//        $this->when->iRequest_From('indexWithTemplate.html', 'templatetest\TemplateModule');
//
//        $this->then->theResponseBodyShouldBe('Hello World');
//    }

}

class ComponentTest_Given extends Given {

    public function theComponent_In_WithTheMethod_ThatReturns($className, $folder, $method, $returnJson) {
        $this->theClass_In_Extending_WithTheBody($className, $folder, '\watoki\webco\Component', "
            public function $method () {
                return json_decode('$returnJson', true);
            }

            protected function doRender(\$model, \$template) {
                foreach (\$model as \$key => \$value) {
                    \$template = str_replace('%' . \$key . '%', \$value, \$template);
                }
                return \$template;
            }
        ");
    }
}