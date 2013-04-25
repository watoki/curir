<?php
namespace watoki\curir\controller;

use watoki\collections\Liste;
use watoki\collections\Map;
use watoki\factory\Factory;
use watoki\curir\Controller;
use watoki\curir\MimeTypes;
use watoki\curir\Path;
use watoki\curir\Request;
use watoki\curir\Response;
use watoki\curir\Router;
use watoki\curir\router\FileRouter;
use watoki\curir\router\StaticRouter;

abstract class Module extends Controller {

    public static $CLASS = __CLASS__;

    /**
     * @var Map Runtime cache for resolved controllers
     */
    public $resolved;

    /**
     * @var Liste|Router[]
     */
    private $routers;

    function __construct(Factory $factory, Path $route, Module $parent = null) {
        parent::__construct($factory, $route, $parent);
        $this->resolved = new Map();
    }

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
        $this->cutAbsoluteBase($request->getResource());
        $controller = $this->resolveController($request);
        if ($controller) {
            return $controller->respond($request);
        }

        if ($request->getResource()) {
            $file = $this->resolveFile($request);
            if ($file) {
                return $this->createFileResponse($file, $request->getResource()->getLeafExtension());
            }
        }

        throw new \Exception('Could not resolve request [' . $request->getResource()->toString() . '] in [' . get_class($this) . ']');
    }

    protected function createFileResponse($file, $extension) {
        $response = $this->getResponse();
        $mimeType = MimeTypes::getType($extension);
        if ($mimeType) {
            $response->getHeaders()->set(Response::HEADER_CONTENT_TYPE, $mimeType);
        }

        $response->setBody(file_get_contents($file));
        return $response;
    }

    protected function resolveController(Request $request) {
        $resource = $request->getResource();

        for ($i = $resource->getNodes()->count(); $i > 0; $i--) {
            $route = new Path($resource->getNodes()->slice(0, $i));
            foreach ($this->getRouters() as $router) {
                if ($router->matches($route)) {
                    return $router->resolve($request);
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

    private function resolveFile(Request $request) {
        $class = new \ReflectionClass($this);
        while ($class) {
            $file = dirname($class->getFileName()) . '/' . $request->getResource()->toString();
            if (file_exists($file) && is_file($file) && $request->getResource()->getLeafExtension() != 'php') {
                return $file;
            }
            $class = $class->getParentClass();
        }
        return null;
    }

    /**
     * @param Path $route
     * @return Controller
     */
    public function resolve(Path $route) {
        if (!$this->resolved->has($route->toString())) {
            $route = $route->copy();
            $this->cutAbsoluteBase($route);
            $this->resolved->set($route->toString(), $this->resolveController(new Request('', $route)));
        }
        return $this->resolved->get($route->toString());
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
     * @return null|\watoki\curir\Controller
     */
    private function findInRouters($controllerClass) {
        foreach ($this->getRouters() as $router) {
            if (!$router instanceof StaticRouter) {
                continue;
            }

            $controller = $router->resolve(new Request('', $router->getRoute()->copy()));

            if ($router->getControllerClass() == $controllerClass) {
                return $controller;
            }

            if ($controller instanceof Module) {
                $foundChild = $controller->findController($controllerClass);
                if ($foundChild) {
                    return $foundChild;
                }
            }
        }
        return null;
    }

    /**
     * @param $controllerClass
     * @return null|\watoki\curir\Controller
     */
    private function findInFolders($controllerClass) {
        $commonNamespace = $this->findCommonNamespace($controllerClass, get_class($this));
        if ($commonNamespace) {
            $strippedClass = FileRouter::stripControllerName($controllerClass);
            $path = new Path(Liste::split('\\', substr($strippedClass, strlen($commonNamespace) + 1)));
            $request = new Request('', $path);
            try {
                return $this->resolveController($request);
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

    private function cutAbsoluteBase(Path $path) {
        $route = $this->getRoute();
        if ($path->getNodes()->slice(0, $route->getNodes()->count()) == $route->getNodes()) {
            $path->getNodes()->splice(0, $route->getNodes()->count());
        }
   }

}
