<?php
namespace spec\watoki\curir\steps;

use watoki\collections\Liste;
use watoki\curir\router\FileRouter;

class CompositionTestGiven extends Given {

    public function theFolder_WithModule($folder) {
        $this->theFolder($folder);
        $this->theModule_In($folder . '\Module', $folder);
    }

    public function theComponent_In_WithTemplate($className, $folder, $template) {
        $shortClassName = Liste::split('\\', FileRouter::stripControllerName($className))->pop();
        $this->theComponent_In_WithTheBody($className, $folder, '
        function doGet() {
            return array("msg" => "World");
        }');
        $this->theFile_In_WithContent(lcfirst($shortClassName) . '.test', $folder, $template);
    }

    public function theComponent_In_WithTheBody($className, $folder, $body) {
        $this->theClass_In_Extending_WithTheBody($className, $folder, '\watoki\curir\controller\Component', "
            protected function getFormat() {
                \$this->rendererFactory->setRenderer('test', 'TestRenderer');
                return parent::getFormat();
            }

            protected function getDefaultFormat() {
                return 'test';
            }

            $body
        ");
    }

    public function theSuperComponent_In_WithTheBody($className, $folder, $body) {
        $this->theClass_In_Extending_WithTheBody($className, $folder, '\watoki\curir\composition\SuperComponent', "
            protected function getFormat() {
                \$this->rendererFactory->setRenderer('test', 'TestRenderer');
                return parent::getFormat();
            }

            protected function getDefaultFormat() {
                return 'test';
            }

            $body
        ");
    }

    public function theModule_In($moduleClass, $folder) {
        $this->theClass_In_Extending_WithTheBody($moduleClass, $folder, '\watoki\curir\controller\Module', '');
    }

}
