<?php
namespace watoki\curir;

use watoki\curir\delivery\WebRequest;
use watoki\curir\delivery\WebResponse;
use watoki\deli\Request;
use watoki\deli\Responding;
use watoki\deli\Router;
use watoki\deli\target\ObjectTarget;
use watoki\factory\Factory;

class Container extends Resource implements Responding {

    /** @var Router */
    protected $router;

    private $childRouter;

    /**
     * @param Factory $factory <-
     */
    function __construct(Factory $factory) {
        parent::__construct($factory);
        $this->router = $this->createRouter();
        $this->childRouter = $this->createRouter($this->getName());
    }

    private function createRouter($suffix = null) {
        $directory = $this->getDirectory();
        $class = new \ReflectionClass($this);
        $namespace = $class->getNamespaceName();

        if ($suffix) {
            $directory .= '/' . $suffix;
            $namespace .= '\\' . $suffix;
        }

        $router = new WebRouter($this->factory, $directory, $namespace);
        $router->setDefaultTarget(ObjectTarget::factory($this->factory, $this));
        return $router;
    }

    /**
     * @param Request|WebRequest $request
     * @return WebResponse
     */
    public function respond(Request $request) {
        if (!$request->getTarget()->isEmpty() && $request->getTarget()->first() == $this->getName()) {
            $request->getTarget()->shift();
            if (!$request->getTarget()->isEmpty()) {
                $request->getContext()->append($this->getName());
            }
            $target = $this->childRouter->route($request);
        } else {
            $target = $this->router->route($request);
        }
        return $target->respond();
    }

}