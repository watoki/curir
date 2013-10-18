<?php
namespace spec\watoki\curir\fixtures;

use watoki\factory\Factory;
use watoki\scrut\Fixture;
use watoki\scrut\Specification;

class FileFixture extends Fixture {

    public function __construct(Specification $spec, Factory $factory) {
        parent::__construct($spec, $factory);

        $this->tmp = __DIR__ . '/tmp/';
        @mkdir($this->tmp);

        $that = $this;
        $spec->undos[] = function () use ($that) {
            $that->clear();
        };
    }

    public function givenTheFile_WithTheContent($name, $content) {
        $fullPath = $this->getFullPathOf($name);
        @mkdir(dirname($fullPath), 0777, true);
        file_put_contents($fullPath, $content);

        $this->spec->undos[] = function () use ($fullPath) {
            @unlink($fullPath);
        };
    }

    public function clear($dir = null) {
        $dir = $dir ?: $this->tmp;

        foreach (glob($dir . '/*') as $file) {
            if (is_dir($file)) {
                $this->clear($file);
            } else {
                @unlink($file);
            }
        }
        @rmdir($dir);
    }

    /**
     * @param $name
     * @return string
     */
    public function getFullPathOf($name) {
        return $this->tmp . $name;
    }
}