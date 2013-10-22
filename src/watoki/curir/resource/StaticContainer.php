<?php
namespace watoki\curir\resource;

use watoki\curir\http\Url;
use watoki\curir\serialization\InflaterRepository;
use watoki\factory\Factory;

class StaticContainer extends Container {

    public static $CLASS = __CLASS__;

    private $namespace;

    public function __construct($namespace, $directory, $name, Url $url, Container $parent = null,
                                InflaterRepository $repository, Factory $factory) {
        parent::__construct($directory, $name, $url, $parent, $repository, $factory);
        $this->namespace = $namespace;
    }

    protected function getNamespace() {
        return $this->namespace;
    }

} 