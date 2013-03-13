<?php
namespace watoki\webco\router;
 
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

    public function route($route) {
        if ($route != $this->route) {
            return null;
        }

        return $this->createController($this->controllerClass, $route . '/');
    }
}
