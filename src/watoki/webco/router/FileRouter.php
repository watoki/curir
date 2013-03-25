<?php
namespace watoki\webco\router;
 
use watoki\collections\Liste;
use watoki\webco\Controller;
use watoki\webco\Path;
use watoki\webco\Request;
use watoki\webco\Router;

class FileRouter extends Router {

    public static $CLASS = __CLASS__;

    /**
     * @var null|Path
     */
    private $nextPath;

    /**
     * @param Path $route
     * @return boolean
     */
    public function matches(Path $route) {
        return $this->resolveClass($route) != null;
    }

    public function resolve(Request $request) {
        return $this->createController($this->resolveClass($request->getResource()), $this->nextPath);
    }

    private function resolveClass(Path $route) {
        $classReflection = new \ReflectionClass($this->parent);
        $classNamespace = $classReflection->getNamespaceName();

        $i = 0;
        $currentNamespace = $classNamespace;
        foreach ($route as $module) {
            $i++;

            // TODO Somehow we need to find out if a module of component is targeted
            $moduleClass = $currentNamespace . '\\' . $module . '\\' . $this->parent->makeControllerName($module);
            if (class_exists($moduleClass)) {
                $this->nextPath = $route->splice(0, $i);
                return $moduleClass;
            }

            $componentClass = $currentNamespace . '\\' . $this->parent->makeControllerName($module);
            if (class_exists($componentClass)) {
                $this->nextPath = $route->splice(0, $i);
                return $componentClass;
            }

            $currentNamespace .= '\\' . $module;
        }

        return null;
    }
}
