<?php
namespace spec\watoki\curir\fixtures;

use spec\watoki\stores\FileStoreFixture;
use watoki\scrut\Fixture;

/**
 * @property FileStoreFixture file <-
 */
class ClassesFixture extends Fixture {

    public function givenTheClass_In($fullClassName, $folder) {
        $this->givenTheClass_In_WithTheBody($fullClassName, $folder, '');
    }

    public function givenTheContainer_In_WithTheBody($fullClassName, $folder, $body) {
        $this->givenTheClass_Extending_In_WithTheBody($fullClassName, '\watoki\curir\Container', $folder, "
            protected function getDirectory() {
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