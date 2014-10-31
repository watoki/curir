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
        $this->store = $store ? : new RawFileStore(new SerializerRepository(), $this->getDirectory($class));
    }

    public function find($format) {
        $class = $this->class;

        while ($class) {
            $templateFile = $this->getName($class) . '.' . $format;

            if ($this->store->exists($templateFile)) {
                return $this->store->read($templateFile)->content;
            }

            $class = get_parent_class($class);
        }

        $class = is_object($this->class) ? get_class($this->class) : $this->class;
        throw new \Exception("Could not find template of format [$format] for [$class]");
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