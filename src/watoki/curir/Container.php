<?php
namespace watoki\curir;

use watoki\curir\delivery\WebRequest;
use watoki\deli\Request;
use watoki\deli\Responding;
use watoki\deli\Router;
use watoki\factory\Factory;

class Container implements Responding {

    /** @var Router */
    private $router;

    function __construct(Factory $factory) {
        $this->router = new WebRouter($factory, get_class($this), $this->getDirectory());
    }

    /**
     * @param Request|WebRequest $request
     * @return mixed
     */
    public function respond(Request $request) {
        return $this->router->route($request)->respond();
    }

    protected function getDirectory() {
        $class = new \ReflectionClass($this);
        return dirname($class->getFileName());
    }
}