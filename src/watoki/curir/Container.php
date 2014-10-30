<?php
namespace watoki\curir;

use watoki\curir\delivery\WebRequest;
use watoki\curir\delivery\WebResponse;
use watoki\curir\delivery\WebRouter;
use watoki\deli\Request;
use watoki\deli\Responding;
use watoki\deli\Router;
use watoki\deli\target\ObjectTarget;
use watoki\factory\Factory;

class Container extends Resource implements Responding {

    /** @var Router */
    private $router;

    /**
     * @param Factory $factory <-
     */
    function __construct(Factory $factory) {
        parent::__construct($factory);
        $this->router = $this->createRouter();
    }

    /**
     * @return Router
     */
    protected function createRouter() {
        $class = new \ReflectionClass($this);
        $namespace = $class->getNamespaceName();
        $directory = dirname($class->getFileName());

        $router = new WebRouter($this->factory, $directory, $namespace);
        $router->setUseFirstIndex(false);
        $router->setDefaultTarget(ObjectTarget::factory($this->factory, $this));

        return $router;
    }

    /**
     * @param Request|WebRequest $request
     * @return WebResponse
     */
    public function respond(Request $request) {
        return $this->router->route($request)->respond();
    }

}