<?php
namespace watoki\webco\router;
 
use watoki\collections\Liste;
use watoki\webco\Controller;
use watoki\webco\Router;

class FileRouter extends Router {

    public static $CLASS = __CLASS__;

    const DEFAULT_CONTROLLER = 'Index';

    /**
     * @param $route
     * @return Controller
     */
    public function route($route) {
        $path = Liste::split('/', $route);

        $classReflection = new \ReflectionClass($this->parent);
        $classNamespace = $classReflection->getNamespaceName();

        $i = 0;
        $currentNamespace = $classNamespace;
        foreach ($path->slice(0, -1) as $module) {
            $i++;

            $controllerClass = $currentNamespace . '\\' . $module . '\\' . $this->parent->makeControllerName($module);
            $currentNamespace .= '\\' . $module;

            if (class_exists($controllerClass)) {
                $nextRoute = $path->splice(0, $i);
                $nextRoute->append('');
                return $this->createController($controllerClass, $nextRoute->join('/'));
            }
        }

        $componentName = $path->last() ? : self::DEFAULT_CONTROLLER;
        $controllerClass = $currentNamespace . '\\' . $this->parent->makeControllerName($componentName);
        if (class_exists($controllerClass)) {
            return $this->createController($controllerClass, $path->join('/'));
        }

        return null;
    }
}
