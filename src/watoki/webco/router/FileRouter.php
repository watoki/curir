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
        return $this->resolveController($route) != null;
    }

    public function resolve(Request $request) {
        return $this->resolveController($request->getResource());
    }

    private function resolveController(Path $route) {
        $classReflection = new \ReflectionClass($this->parent);
        $classNamespace = $classReflection->getNamespaceName();

        $classPath = new Path(Liste::split('\\', $classNamespace));
        foreach ($route->getNodes() as $i => $module) {
            $classPath->getNodes()->append($module);
            $classPath->getNodes()->append(ucfirst($module));
            $className = $classPath->getNodes()->join('\\');
            if (class_exists($className)) {
                return $this->createController($className, new Path($route->getNodes()->splice(0, $i + 1)));
            }
            $classPath->getNodes()->pop();
        }

        $componentName = ucfirst($classPath->getLeafName());
        $classPath->getNodes()->pop();
        $classPath->getNodes()->append($componentName);
        $className = $classPath->getNodes()->join('\\');
        if (class_exists($className)) {
            return $this->createController($className, $route);
        }

        return null;
    }
}
