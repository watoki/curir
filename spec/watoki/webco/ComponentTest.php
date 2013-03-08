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

}

class ComponentTest_Given extends Given {

    public function theComponent_In_WithTheMethod_ThatReturns($className, $folder, $method, $returnJson) {
        $classPath = explode('\\', $className);
        $shortName = array_pop($classPath);
        $namespace = implode('\\', $classPath);
        $classFile = $shortName . '.php';

        $classDef = "<?php namespace $namespace; class $shortName extends \\watoki\\webco\\Component {
            public function $method () {
                return json_decode('$returnJson', true);
            }

            protected function doRender(\$model, \$template) {
                foreach (\$model as \$key => \$value) {
                    \$template = str_replace('%' . \$key . '%', \$value, \$template);
                }
                return \$template;
            }
        }";

        $this->theFile_In_WithContent($classFile, $folder, $classDef);
    }
}