<?php
namespace spec\watoki\webco\steps;
 
use spec\watoki\webco\Step;
use spec\watoki\webco\Test;

class Given extends Step {

    public $requestMethod;

    public $requestResource;

    public $requestParams = array();

    public $requestHeaders = array();

    public $moduleRoute = '/base/';

    function __construct(Test $test) {
        parent::__construct($test);

        $this->makeFolder(Test::$folder . '/test');

        spl_autoload_register(function ($className) {
            $classFile = Test::$folder . '/test/' . str_replace('\\', '/', $className) . '.php';
            if (file_exists($classFile)) {
                require_once $classFile;
            }
        });
    }

    public function theFolder($folder) {
        $fullFolder = Test::$folder . '/test/' . $folder;

        $this->makeFolder($fullFolder);
    }

    /**
     * @param $fullFolder
     */
    public function makeFolder($fullFolder) {
        $this->cleanDir($fullFolder);

        mkdir($fullFolder);

        $that = $this;
        $this->test->undos[] = function () use ($that, $fullFolder) {
            $that->cleanDir($fullFolder);
        };
    }

    public function theRequestMethodIs($method) {
        $this->requestMethod = $method;
    }

    public function theRequestResourceIs($resource) {
        $this->requestResource = $resource;
    }

    public function theRequestParameter_WithValue($key, $value) {
        $this->requestParams[$key] = $value;
    }

    public function theFile_In_WithContent($fileName, $folder, $content) {
        $file = Test::$folder . '/test/' . $folder . '/' . $fileName;
        file_put_contents($file, $content);

        $test = $this->test;
        $this->test->undos[] = function () use ($file, $test) {
            if (!unlink($file)) {
                $test->fail('Could not delete ' . $file);
            }
        };
    }

    public function cleanDir($folder) {
        if (!file_exists($folder)) {
            return;
        }

        foreach(glob(rtrim($folder, '/') . '/' . '*') as $item) {
            is_dir($item) ? $this->cleanDir($item) : unlink($item);
        }
        rmdir($folder);
    }

    public function theClass_In_Extending_WithTheBody($className, $folder, $baseClass, $body) {
        $classPath = explode('\\', $className);
        $shortName = array_pop($classPath);
        $namespace = implode('\\', $classPath);
        $classFile = $shortName . '.php';

        $classDef = "<?php
            namespace $namespace;

            class $shortName extends $baseClass {
                static \$CLASS = __CLASS__;
            $body
            }
        ";

        $this->theFile_In_WithContent($classFile, $folder, $classDef);
    }

    public function theModuleRouteIs($route) {
        $this->moduleRoute = $route;
    }

}
