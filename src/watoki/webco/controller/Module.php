<?php
namespace watoki\webco\controller;

use watoki\collections\Liste;
use watoki\collections\Map;
use watoki\factory\Factory;
use watoki\webco\Controller;
use watoki\webco\MimeTypes;
use watoki\webco\Request;
use watoki\webco\Response;
use watoki\webco\Url;

abstract class Module extends Controller {

    public static $CLASS = __CLASS__;

    /**
     * @param Request $request
     * @throws \Exception
     * @return Response
     */
    public function respond(Request $request) {
        $controller = $this->resolveController($this->resourceToControllerPath($request));
        if ($controller) {
            return $controller->respond($request);
        }

        if ($request->getResource()) {
            $file = $this->getDirectory() . '/' . $request->getResource();
            if (file_exists($file) && is_file($file) && $request->getResourceExtension() != 'php') {
                return $this->createFileResponse($request);
            }
        }

        throw new \Exception('Could not resolve request [' . $request->getResource()
                . '] in [' . get_class($this) . ']');
    }

    /**
     * @param \watoki\webco\Request $request
     * @return \watoki\collections\Liste
     */
    private function resourceToControllerPath(Request $request) {
        $path = $request->getResourcePath()->slice(0, -1);
        $path->append($request->getResourceName());
        return $path;
    }

    /**
     * @param Request $request
     * @return Response
     */
    protected function createFileResponse(Request $request) {
        $response = $this->getResponse();
        $mimeType = MimeTypes::getType($request->getResourceExtension());
        if ($mimeType) {
            $response->getHeaders()->set(Response::HEADER_CONTENT_TYPE, $mimeType);
        }

        $response->setBody(file_get_contents($this->getDirectory() . '/' . $request->getResource()));
        return $response;
    }

    /**
     * @param \watoki\collections\Liste $path
     * @return Controller|null
     */
    protected function resolveController(Liste $path) {
        $classReflection = new \ReflectionClass($this);
        $classNamespace = $classReflection->getNamespaceName();

        $i = 0;
        $currentNamespace = $classNamespace;
        foreach ($path->slice(0, -1) as $module) {
            $i++;

            $controllerClass = $currentNamespace . '\\' . $module . '\\' . $this->makeControllerName($module);
            $currentNamespace .= '\\' . $module;

            if (class_exists($controllerClass)) {
                $nextRoute = $path->splice(0, $i);
                $nextRoute->append('');
                return $this->createController($controllerClass, $nextRoute);
            }
        }

        $controllerClass = $currentNamespace . '\\' . $this->makeControllerName($path->last());
        if (class_exists($controllerClass)) {
            $nextRoute = $path->copy();
            $path->clear();
            return $this->createController($controllerClass, $nextRoute);
        }

        return null;
    }

    /**
     * @param $controllerClass
     * @param $nextRoute
     * @return mixed
     */
    private function createController($controllerClass, Liste $nextRoute) {
        return $this->factory->getInstance($controllerClass, array(
            'route' => $this->route . $nextRoute->join('/'),
            'parent' => $this
        ));
    }

    /**
     * @param $route
     * @return Controller
     */
    public function resolve($route) {
        $len = strlen($this->route);
        if (substr($route, 0, $len) == $this->route) {
            $route = substr($route, $len);
        }
        return $this->resolveController(Liste::split('/', $route));
    }

    /**
     * Searches all static routes for given Controller
     *
     * @param string $controllerClass
     * @return Controller|null
     */
    public function findController($controllerClass) {
        $commonNamespace = $this->findCommonNamespace($controllerClass, get_class($this));
        if ($commonNamespace) {
            $path = Liste::split('\\', substr($controllerClass, strlen($commonNamespace) + 1));
            try {
                return $this->resolveController($path);
            } catch (\Exception $e) {
            }
        }

        return null;
    }

    private function findCommonNamespace($class1, $class2) {
        $namespace1 = explode('\\', $class1);
        $namespace2 = explode('\\', $class2);

        $common = '';
        for ($i = 1; $i <= count($namespace1); $i++) {
            $nextCommon1 = implode('\\', array_slice($namespace1, 0, $i));
            $nextCommon2 = implode('\\', array_slice($namespace2, 0, $i));
            if ($nextCommon2 != $nextCommon1) {
                break;
            }
            $common = $nextCommon1;
        }
        return $common;
    }

    protected function makeControllerName($name) {
        return ucfirst($name);
    }

    protected function getBaseRoute() {
        return $this->route;
    }

}
