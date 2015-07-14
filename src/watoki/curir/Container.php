<?php
namespace watoki\curir;

use watoki\curir\delivery\WebRequest;
use watoki\curir\delivery\WebResponse;
use watoki\curir\delivery\WebRouter;
use watoki\curir\error\HttpError;
use watoki\deli\Request;
use watoki\deli\Responding;
use watoki\deli\Router;
use watoki\deli\router\TargetNotFoundException;
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
        return $this->createRouterFor($this);
    }

    /**
     * @param object/string $class
     * @return WebRouter
     */
    protected function createRouterFor($class) {
        $class = new \ReflectionClass($class);
        $namespace = $class->getNamespaceName();
        $directory = dirname($class->getFileName());

        return new WebRouter($this->factory, $directory, $namespace);
    }

    /**
     * @param Request|WebRequest $request
     * @throws error\HttpError
     * @return WebResponse
     */
    public function respond(Request $request) {
        if ($this->isContainerTarget($request)) {
            return ObjectTarget::factory($this->factory, $this)->create($request)->respond();
        }

        try {
            return $this->router->route($request)->respond();
        } catch (TargetNotFoundException $e) {
            return $this->tryToRouteInParentClass($request, $e);
        }
    }

    private function tryToRouteInParentClass(Request $request, TargetNotFoundException $tnfe) {
        $parent = get_parent_class($this);
        while ($parent) {
            try {
                return $this->createRouterFor($parent)->route($request)->respond();
            } catch (TargetNotFoundException $e) {
            }
            $parent = get_parent_class($parent);
        }

        throw new HttpError(WebResponse::STATUS_NOT_FOUND, "Could not find [" . $request->getTarget()->toString()
            . "] in [" . $request->getContext()->toString() . "].", "", 0, $tnfe);
    }

    protected function isContainerTarget(Request $request) {
        return in_array($request->getTarget()->getElements(), [[], [''], ['index']]);
    }

}