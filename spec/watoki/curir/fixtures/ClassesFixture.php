<?php
namespace spec\watoki\curir\fixtures;

use watoki\curir\Container;
use watoki\scrut\Fixture;
use watoki\stores\file\raw\RawFileStore;

/**
 * @property \spec\watoki\stores\fixtures\FileStoreFixture file <-
 */
class ClassesFixture extends Fixture {

    public function givenTheClass_In($fullClassName, $folder) {
        $this->givenTheClass_In_WithTheBody($fullClassName, $folder, '');
    }

    public function givenTheContainer_In_WithTheBody($fullClassName, $folder, $body) {
        $this->givenTheClass_Extending_In_WithTheBody($fullClassName, '\spec\watoki\curir\fixtures\TestContainerStub', $folder, "
            function getMockFolder() {
                return '$folder';
            }
            $body
        ");
    }

    public function givenTheContainer_In($fullClassName, $folder) {
        $this->givenTheContainer_In_WithTheBody($fullClassName, $folder, '');
    }

    public function givenTheClass_In_WithTheBody($fullClassName, $folder, $body) {
        $this->givenTheClass_Extending_In_WithTheBody($fullClassName, null, $folder, $body);
    }

    public function givenTheClass_Extending_In_WithTheBody($fullClassName, $superClass, $folder, $body) {
        if (class_exists($fullClassName)) {
            return;
        }

        $nameParts = explode('\\', trim($fullClassName, '\\'));
        $className = array_pop($nameParts);
        $namespace = implode('\\', $nameParts);
        $file = $folder . '/' . $className . '.php';

        $extends = $superClass ? 'extends ' . $superClass : '';
        $namespaceString = $namespace ? "namespace $namespace;" : '';

        $code = "$namespaceString class $className $extends {
            $body
        }";
        eval($code);
        if (!class_exists($fullClassName)) {
            $this->spec->fail("Could not eval\n\n" . $code);
        }
        $this->file->givenAFile_WithContent($file, '<?php ' . $code);
    }

}

abstract class TestContainerStub extends Container {

    protected function createRouterFor($class) {
        $router = parent::createRouterFor($class);

        $reflection = new \ReflectionClass($router);
        $store = $reflection->getProperty('store');
        $store->setAccessible(true);
        $store->setValue($router, $this->factory->getInstance(RawFileStore::$CLASS, array(
            "rootDirectory" => $this->getMockFolder()
        )));

        return $router;
    }

    abstract protected function getMockFolder();

}