<?php
namespace watoki\webco;
 
use watoki\collections\Liste;
use watoki\factory\Factory;
use watoki\webco\controller\Module;

abstract class Router {

    public static $CLASS = __CLASS__;

    /**
     * @var Factory
     */
    protected $factory;

    /**
     * @var Module
     */
    protected $parent;

    /**
     * @param Request $request
     * @return Controller
     */
    abstract public function resolve(Request $request);

    /**
     * @param string $route
     * @return boolean
     */
    abstract public function matches($route);

    public function inject(Factory $factory, Module $parent) {
        $this->parent = $parent;
        $this->factory = $factory;
    }

    /**
     * @param $controllerClass
     * @param string $nextRoute
     * @param array $additionalConstructorArguments
     * @return Controller
     */
    protected function createController($controllerClass, $nextRoute, $additionalConstructorArguments = array()) {
        $args = array_merge(array(
            'route' => $this->parent->getRoute() . $nextRoute,
            'parent' => $this->parent
        ), $additionalConstructorArguments);
        return $this->factory->getInstance($controllerClass, $args);
    }

}
