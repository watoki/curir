<?php
namespace watoki\curir\resource;

use watoki\curir\http\Url;
use watoki\curir\Resource;
use watoki\curir\serialization\InflaterRepository;
use watoki\factory\Factory;

class StaticContainer extends Container {

    public static $CLASS = __CLASS__;

    private $directory;

    private $namespace;

    public function __construct(Url $url, Resource $parent = null, $directory, $namespace, InflaterRepository $repository, Factory $factory) {
        parent::__construct($url, $parent, $repository, $factory);
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