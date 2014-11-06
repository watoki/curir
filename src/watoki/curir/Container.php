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
     * @throws error\HttpError
     * @return WebResponse
     */
    public function respond(Request $request) {
        try {
            return $this->router->route($request)->respond();
        } catch (TargetNotFoundException $e) {
            throw new HttpError(WebResponse::STATUS_NOT_FOUND, "Could not find [" . $request->getTarget()->toString()
                . "] in [" . $request->getContext()->toString() . "].", "", 0, $e);
        }
    }

}