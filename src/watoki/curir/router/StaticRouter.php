<?php
namespace watoki\curir\router;
 
use watoki\collections\Liste;
use watoki\curir\Path;
use watoki\curir\Request;
use watoki\curir\Router;

class StaticRouter extends Router {

    public static $CLASS = __CLASS__;

    private $route;

    private $controllerClass;

    function __construct(Path $route, $controllerClass) {
        $this->route = $route;
        $this->controllerClass = $controllerClass;
    }

    /**
     * @param Path $route
     * @return boolean
     */
    public function matches(Path $route) {
        return $route == $this->route;
    }

    public function resolve(Request $request) {
        $request->getResource()->getNodes()->splice(0, $this->route->getNodes()->count());
        return $this->createController($this->controllerClass, $this->route);
    }

    public function getControllerClass() {
        return $this->controllerClass;
    }

    public function getRoute() {
        return $this->route;
    }
}
