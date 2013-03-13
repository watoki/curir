<?php
namespace watoki\webco\controller;

use watoki\collections\Liste;
use watoki\webco\Controller;
use watoki\webco\MimeTypes;
use watoki\webco\Request;
use watoki\webco\Response;
use watoki\webco\Router;
use watoki\webco\router\FileRouter;
use watoki\webco\router\StaticRouter;

abstract class Module extends Controller {

    public static $CLASS = __CLASS__;

    /**
     * @var Liste|Router[]
     */
    private $routers;

    /**
     * @return \watoki\collections\Liste|Router[]
     */
    protected function createRouters() {
        return new Liste();
    }

    /**
     * @param Request $request
     * @throws \Exception
     * @return Response
     */
    public function respond(Request $request) {
        $path = $this->resourceToControllerPath($request);
        $controller = $this->resolveController($path);
        if ($controller) {
            $request->setResourcePath($path);
            return $controller->respond($request);
        }

        if ($request->getResource()) {
            $file = $this->getDirectory() . '/' . $request->getResource();
            if (file_exists($file) && is_file($file) && $request->getResourceExtension() != 'php') {
                return $this->createFileResponse($request);
            }
        }

        throw new \Exception('Could not resolve request [' . $request->getResource() . '] in [' . get_class($this) . ']');
    }

    /**
     * @param \watoki\webco\Request $request
     * @return \watoki\collections\Liste
     */
    private function resourceToControllerPath(Request $request) {
        if ($request->getResourcePath()->isEmpty()) {
            return new Liste();
        }
        $path = $request->getResourcePath()->slice(0, -1);
        $path->append($this->makeControllerName($request->getResourceName()));
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
        for ($i = count($path); $i > 0; $i--) {
            foreach ($this->getRouters() as $router) {
                $route = $path->slice(0, $i)->join('/');
                $controller = $router->route($route);
                if ($controller) {
                    $path->splice(0, Liste::split('/', $controller->getRoute())->count()
                            - Liste::split('/', $this->route)->count());
                    return $controller;
                }
            }
        }
        return null;
    }

    private function getRouters() {
        if (!$this->routers) {
            $this->routers = $this->createRouters();
            $this->routers->append(new FileRouter());

            foreach ($this->routers as $router) {
                $router->inject($this->factory, $this);
            }
        }
        return $this->routers;
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
        return $this->findInRouters($controllerClass) ?: $this->findInFolders($controllerClass);
    }

    /**
     * @param $controllerClass
     * @return null|\watoki\webco\Controller
     */
    private function findInRouters($controllerClass) {
        foreach ($this->getRouters() as $router) {
            if (!$router instanceof StaticRouter) {
                continue;
            }

            if ($router->getControllerClass() == $controllerClass) {
                return $router->route($router->getRoute());
            }
        }
        return null;
    }

    /**
     * @param $controllerClass
     * @return null|\watoki\webco\Controller
     */
    private function findInFolders($controllerClass) {
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

    public function makeControllerName($name) {
        return ucfirst($name);
    }

    protected function getBaseRoute() {
        return $this->route;
    }

}
