<?php
namespace spec\watoki\curir;

use watoki\curir\rendering\locating\ClassTemplateLocator;
use watoki\scrut\Specification;

/**
 * The ClassTemplateLocator looks for a file in the same folder as the class with the name of the class.
 *
 * If not found, the same is tried for parent classes.
 *
 * @property \watoki\scrut\ExceptionFixture try <-
 */
class LocateTemplatesTest extends Specification {

    function testTemplateDoesNotExist() {
        $this->givenTheFile_Containing('some/folder/WithoutTemplate.php',
            '<?php namespace template\space; class WithoutTemplate {}');

        $this->whenITryToLocateTheTemplateOf('template\space\WithoutTemplate');
        $this->try->thenTheException_ShouldBeThrown('Could not find template of format [foo] ' .
            'for [template\space\WithoutTemplate]. Searched for ["WithoutTemplate.foo"]');
    }

    function testTemplateAtChildClass() {
        $this->givenTheFile_Containing('some/folder/ClassWithTemplate.php',
            '<?php namespace template\space; class ClassWithTemplate {}');
        $this->givenTheFile_Containing('some/folder/ClassWithTemplate.foo', 'Hello World');

        $this->whenILocateTheTemplateOf('template\space\ClassWithTemplate');
        $this->thenItShouldFindTheTemplate('Hello World');
    }

    function testTemplateAtParentClass() {
        $this->givenTheFile_Containing('some/folder/ParentClass.php',
            '<?php namespace template\space; class ParentClass {}');
        $this->givenTheFile_Containing('some/folder/ChildClass.php',
            '<?php namespace template\space; class ChildClass extends ParentClass {}');
        $this->givenTheFile_Containing('some/folder/ParentClass.foo', 'Hello Parent');

        $this->whenILocateTheTemplateOf('template\space\ChildClass');
        $this->thenItShouldFindTheTemplate('Hello Parent');
    }

    function testTemplateAtParentClassInDifferentFolder() {
        $this->givenTheFile_Containing('some/folder/ParentClass.php',
            '<?php namespace template\here; class ParentClass {}');
        $this->givenTheFile_Containing('other/folder/ChildClass.php',
            '<?php namespace template\here; class ChildClass extends ParentClass {}');
        $this->givenTheFile_Containing('some/folder/ParentClass.foo', 'Hello Parent');

        $this->whenILocateTheTemplateOf('template\here\ChildClass');
        $this->thenItShouldFindTheTemplate('Hello Parent');
    }

    ########################################################################################################

    private $tmp;

    private $found;

    protected function setUp() {
        parent::setUp();
        $this->tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR;

        if (file_exists($this->tmp)) {
            $this->clear(substr($this->tmp, 0, -1));
        }
    }

    private function clear($dir) {
        foreach (glob($dir . '/*') as $file) {
            if (is_dir($file)) {
                $this->clear($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dir);
    }

    private function givenTheFile_Containing($file, $content) {
        $path = $this->tmp . $file;
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, $content);
        require($path);
    }

    private function whenITryToLocateTheTemplateOf($class) {
        $this->try->tryTo(function () use ($class) {
            $this->whenILocateTheTemplateOf($class);
        });
    }

    private function whenILocateTheTemplateOf($class) {
        $locator = new ClassTemplateLocator($class);
        $this->found = $locator->find('foo');
    }

    private function thenItShouldFindTheTemplate($string) {
        $this->assertEquals($string, $this->found);
    }

} 