<?php
namespace watoki\curir\resource;

class StaticContainer extends Container {

    public static $CLASS = __CLASS__;

    private $directory;

    private $namespace;

    public function __construct($name, Container $parent = null, $directory, $namespace) {
        parent::__construct($name, $parent);
        $this->namespace = $namespace;
        $this->directory = $directory;
    }

    public function getResourceDirectory() {
        return dirname($this->directory);
    }

    public function getResourceName() {
        return basename($this->directory);
    }

    protected function getResourceNamespace() {
        return $this->namespace;
    }

} 