<?php
namespace watoki\curir\router;
 
use watoki\collections\Liste;
use watoki\curir\Controller;
use watoki\curir\Path;
use watoki\curir\Request;
use watoki\curir\Router;

class FileRouter extends Router {

    public static $CLASS = __CLASS__;

    /**
     * @param Path $route
     * @return boolean
     */
    public function matches(Path $route) {
        return $this->resolveController($route) != null;
    }

    public function resolve(Request $request) {
        return $this->resolveController($request->getResource());
    }

    private function resolveController(Path $route) {

        $class = new \ReflectionClass($this->parent);
        while ($class) {
            $resolved = $this->resolveAlongTheRoute($route, $class);
            if ($resolved) {
                return $resolved;
            }
            $class = $class->getParentClass();
        }

        return null;
    }

    private function resolveAlongTheRoute(Path $route, \ReflectionClass $class) {
        $classNamespace = $class->getNamespaceName();

        $classPath = new Path(Liste::split('\\', $classNamespace));
        foreach ($route->getNodes() as $i => $module) {
            $classPath->getNodes()->append($module);
            $classPath->getNodes()->append($this->getModuleName($module));
            $className = $classPath->getNodes()->join('\\');

            if (class_exists($className)) {
                return $this->createController($className, new Path($route->getNodes()->splice(0, $i + 1)));
            }
            $classPath->getNodes()->pop();
        }

        $componentName = $this->getComponentName($classPath->getLeafName());
        $classPath->getNodes()->pop();
        $classPath->getNodes()->append($componentName);
        $className = $classPath->getNodes()->join('\\');

        if (class_exists($className)) {
            return $this->createController($className, $route);
        }

        return null;
    }

    private function getComponentName($leafName) {
        return ucfirst($leafName) . 'Component';
    }

    public static function stripControllerName($className) {
        if (substr($className, -9) == 'Component') {
            return substr($className, 0, -9);
        } else if (substr($className, -6) == 'Module') {
            return substr($className, 0, -6);
        }
        return $className;
    }

    private function getModuleName($nodeName) {
        return ucfirst($nodeName) . 'Module';
    }
}
