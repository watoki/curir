<?php
namespace watoki\webco;

use watoki\collections\Liste;
use watoki\factory\Factory;

abstract class Module extends Controller {

    public static $CLASS = __CLASS__;

    /**
     * @var \watoki\factory\Factory
     */
    protected $factory;

    function __construct(Factory $factory, $route) {
        $this->factory = $factory;
        $this->route = $route;
    }

    /**
     * @param Request $request
     * @throws \Exception
     * @return Response
     */
    public function respond(Request $request) {
        $controller = $this->findController($request);
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
     * @param Request $request
     * @return Controller|null
     */
    protected function findController(Request $request) {
        $classReflection = new \ReflectionClass($this);
        $classNamespace = $classReflection->getNamespaceName();

        $i = 0;
        $currentNamespace = $classNamespace;
        foreach ($request->getResourcePath()->slice(0, -1) as $module) {
            $i++;

            $controllerClass = $currentNamespace . '\\' . $module . '\\' . $this->makeControllerName($module);
            $currentNamespace .= '\\' . $module;

            if (class_exists($controllerClass)) {
                $nextRoute = $request->getResourcePath()->slice(0, $i);
                $request->setResourcePath($request->getResourcePath()->slice($i));
                return $this->factory->getInstance($controllerClass, array('route' => $this->route . $nextRoute->join('/') . '/'));
            }
        }

        $name = $request->getResourceName() ?: 'index';
        $controllerClass = $currentNamespace . '\\' . $this->makeControllerName($name);
        if (class_exists($controllerClass)) {
            $nextRoute = $request->getResourcePath()->slice(0, -1);
            $request->setResourcePath(new Liste());
            return $this->factory->getInstance($controllerClass,
                array('route' => $this->route . $nextRoute->join('/') . '/'));
        }

        return null;
    }

    protected function makeControllerName($name) {
        return ucfirst($name);
    }

}
