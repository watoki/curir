<?php
namespace watoki\curir\rendering\locating;

use watoki\stores\Store;
use watoki\stores\stores\FlatFileStore;

class ClassTemplateLocator implements TemplateLocator {

    /** @var string */
    private $class;

    /** @var Store */
    private $store;

    /**
     * @param string|object $class Class reference or instance or class
     * @param null|Store $store
     */
    public function __construct($class, Store $store = null) {
        $this->class = $class;
        $this->store = $store ? : new FlatFileStore($this->getDirectory($class));
    }

    public function find($format) {
        $class = $this->class;
        $store = $this->store;

        $tried = array();
        while ($class) {
            $templateFile = $this->getName($class) . '.' . $format;
            $tried[] = $templateFile;

            if ($store->has($templateFile)) {
                return $store->read($templateFile);
            }

            $class = get_parent_class($class);
            if (!$class || !class_exists($class)) {
                break;
            }
            $store = new FlatFileStore($this->getDirectory($class));
        }

        $class = is_object($this->class) ? get_class($this->class) : $this->class;
        throw new \Exception("Could not find template of format [$format] for [$class]. " .
            "Searched for " . json_encode($tried));
    }

    protected function getName($class) {
        $reflection = new \ReflectionClass($class);
        return $reflection->getShortName();
    }

    private function getDirectory($class) {
        $reflection = new \ReflectionClass($class);
        return dirname($reflection->getFileName());
    }

} 