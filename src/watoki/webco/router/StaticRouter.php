<?php
namespace watoki\webco\router;
 
use watoki\collections\Liste;
use watoki\webco\Request;
use watoki\webco\Router;

class StaticRouter extends Router {

    public static $CLASS = __CLASS__;

    private $route;

    private $controllerClass;

    function __construct($route, $controllerClass) {
        $this->route = $route;
        $this->controllerClass = $controllerClass;
    }

    /**
     * @param string $route
     * @return boolean
     */
    public function matches($route) {
        return $route == $this->route;
    }

    public function resolve(Request $request) {
        $request->getResourcePath()->splice(0, Liste::split('/', $this->route)->count() - 1);
        return $this->createController($this->controllerClass, $this->route);
    }

    public function getControllerClass() {
        return $this->controllerClass;
    }

    public function getRoute() {
        return $this->route;
    }
}
