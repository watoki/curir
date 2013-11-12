<?php
namespace watoki\curir\resource;

use watoki\curir\http\Url;
use watoki\curir\Resource;

class StaticContainer extends Container {

    public static $CLASS = __CLASS__;

    private $directory;

    private $namespace;

    public function __construct(Url $url, Resource $parent = null, $directory, $namespace) {
        parent::__construct($url, $parent);
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