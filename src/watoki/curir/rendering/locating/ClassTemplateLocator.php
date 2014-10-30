<?php
namespace watoki\curir\rendering\locating;

use watoki\stores\file\raw\RawFileStore;
use watoki\stores\file\SerializerRepository;

class ClassTemplateLocator implements TemplateLocator {

    /** @var string */
    private $class;

    /** @var \watoki\stores\file\raw\RawFileStore */
    private $store;

    /**
     * @param string|object $class Class reference or instance or class
     * @param null|\watoki\stores\file\raw\RawFileStore $store
     */
    public function __construct($class, RawFileStore $store = null) {
        $this->class = $class;
        $this->store = $store ? : new RawFileStore(new SerializerRepository(), $this->getDirectory());
    }

    public function find($format) {
        $templateFile = $this->getName() . '.' . $format;

        if (!$this->store->exists($templateFile)) {
            $class = is_object($this->class) ? get_class($this->class) : $this->class;
            throw new \Exception("Could not find template [$templateFile] for [$class]");
        }
        return $this->store->read($templateFile)->content;
    }

    protected function getName() {
        $class = new \ReflectionClass($this->class);
        return $class->getShortName();
    }

    private function getDirectory() {
        $class = new \ReflectionClass($this->class);
        return dirname($class->getFileName());
    }

} 