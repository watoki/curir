<?php
namespace spec\watoki\webco\steps;

use spec\watoki\webco\Step;
use spec\watoki\webco\Test;
use watoki\webco\Path;

class Given extends Step {

    public $requestMethod;

    public $requestResource;

    public $requestParams = array();

    public $requestHeaders = array();

    public $moduleRoute;

    function __construct(Test $test) {
        parent::__construct($test);

        $this->moduleRoute = Path::parse('/base/');

        $testFolder = Test::$folder . '/tmp';
        $this->cleanDir($testFolder);
        $this->makeFolder($testFolder);

        $that = $this;
        $this->test->undos[] = function () use ($that, $testFolder) {
            $that->cleanDir($testFolder);
        };

        spl_autoload_register(function ($className) use ($testFolder) {
            $classFile = $testFolder . '/' . str_replace('\\', '/', $className) . '.php';
            if (file_exists($classFile)) {
                require_once $classFile;
            }
        });
    }

    public function theFolder($folder) {
        $fullFolder = Test::$folder . '/tmp/' . $folder;

        $this->makeFolder($fullFolder);
    }

    /**
     * @param $fullFolder
     */
    public function makeFolder($fullFolder) {
        mkdir($fullFolder);
    }

    public function theRequestMethodIs($method) {
        $this->requestMethod = $method;
    }

    public function theRequestResourceIs($resource) {
        $this->requestResource = Path::parse($resource);
    }

    public function theRequestParameter_WithValue($key, $value) {
        $this->requestParams[$key] = $value;
    }

    public function theFile_In_WithContent($fileName, $folder, $content) {
        $file = Test::$folder . '/tmp/' . $folder . '/' . $fileName;
        file_put_contents($file, $content);
    }

    public function cleanDir($folder) {
        if (!file_exists($folder)) {
            return true;
        }

        do {
            $items = glob(rtrim($folder, '/') . '/' . '*');
            foreach ($items as $item) {
                is_dir($item) ? $this->cleanDir($item) : unlink($item);
            }
        } while ($items);

        return rmdir($folder);
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
        $this->moduleRoute = Path::parse($route);
    }

}
