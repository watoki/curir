<?php
namespace watoki\webco\router;
 
use watoki\collections\Liste;
use watoki\webco\Controller;
use watoki\webco\Request;
use watoki\webco\Router;

class FileRouter extends Router {

    public static $CLASS = __CLASS__;

    /**
     * @var null|Liste
     */
    private $nextPath;

    /**
     * @param string $route
     * @return boolean
     */
    public function matches($route) {
        return $this->resolveClass(new Request('', $route)) != null;
    }

    public function resolve(Request $request) {
        return $this->createController($this->resolveClass($request), $this->nextPath->join('/'));
    }

    private function resolveClass(Request $request) {
        $classReflection = new \ReflectionClass($this->parent);
        $classNamespace = $classReflection->getNamespaceName();

        $i = 0;
        $currentNamespace = $classNamespace;
        foreach ($request->getResourcePath()->slice(0, -1) as $module) {
            $i++;

            $controllerClass = $currentNamespace . '\\' . $module . '\\' . $this->parent->makeControllerName($module);
            $currentNamespace .= '\\' . $module;

            if (class_exists($controllerClass)) {
                $this->nextPath = $request->getResourcePath()->splice(0, $i);
                $this->nextPath->append('');
                return $controllerClass;
            }
        }

        $controllerClass = $currentNamespace . '\\' . $this->parent->makeControllerName($request->getResourceName());
        if (class_exists($controllerClass)) {
            $this->nextPath = $request->getResourcePath();
            return $controllerClass;
        }

        return null;
    }
}
