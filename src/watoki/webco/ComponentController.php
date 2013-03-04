<?php
namespace watoki\webco;
 
use watoki\factory\Factory;
use watoki\collections\Liste;

abstract class ComponentController {

    public static $CLASS = __CLASS__;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var \watoki\factory\Factory
     */
    protected $factory;

    /**
     * @var string Path to this controller in the current request
     */
    private $root;

    /**
     * @param array|object $model
     * @param string $template
     * @return string The rendered template
     */
    abstract protected function doRender($model, $template);

    function __construct(Factory $factory, $root) {
        $this->factory = $factory;
        $this->root = $root;
    }

    /**
     * @param Request $request
     * @throws \Exception
     * @return Response
     */
    public function respond(Request $request) {
        if ($request->getResourcePath()->isEmpty()) {
            $response = $this->getResponse();
            $model = $this->invokeAction($request);
            if ($model !== null) {
                $response->setBody($this->render($model));
            }
            return $response;
        } else {
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
    }

    /**
     * @param Request $request
     * @return mixed
     * @throws \Exception
     */
    protected function invokeAction(Request $request) {
        $action = $this->makeMethodName($this->getActionName($request));

        if (!method_exists($this, $action)) {
            throw new \Exception('Method [' . $action . '] not found in controller [' . get_class($this) . '].');
        }

        $method = new \ReflectionMethod($this, $action);
        $args = $this->assembleArguments($request, $method);

        return $method->invokeArgs($this, $args);
    }

    /**
     * @param Request $request
     * @return mixed|string
     */
    protected function getActionName(Request $request) {
        return $request->getParameters()->has('action')
            ? $request->getParameters()->get('action')
            : strtolower($request->getMethod());
    }

    /**
     * @param Request $request
     * @param \ReflectionMethod $method
     * @return array
     * @throws \Exception If a parameter is missing
     */
    private function assembleArguments(Request $request, \ReflectionMethod $method) {
        $args = array();
        foreach ($method->getParameters() as $param) {
            if ($request->getParameters()->has($param->getName())) {
                $args[] = $request->getParameters()->get($param->getName());
            } else if (!$param->isOptional()) {
                throw new \Exception('Invalid request: Missing Parameter [' . $param->getName() . ']');
            } else {
                $args[] = $param->getDefaultValue();
            }
        }
        return $args;
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
     * @return ComponentController|null
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
                $nextRoot = $request->getResourcePath()->slice(0, $i);
                $request->setResourcePath($request->getResourcePath()->slice($i));
                return $this->factory->get($controllerClass, array($this->root . $nextRoot->join('/') . '/'));
            }
        }

        $name = $request->getResourceName() ?: 'index';
        $controllerClass = $currentNamespace . '\\' . $this->makeControllerName($name);
        if (class_exists($controllerClass)) {
            $nextRoot = $request->getResourcePath()->slice(0, -1);
            $request->setResourcePath(new Liste());
            return $this->factory->get($controllerClass,
                array($this->root . $nextRoot->join('/') . '/'));
        }

        return null;
    }

    /**
     * @param $actionName
     * @return string
     */
    protected function makeMethodName($actionName) {
        return 'do' . ucfirst($actionName);
    }

    protected function makeControllerName($name) {
        return ucfirst($name) . 'Controller';
    }

    private function getDirectory() {
        $class = new \ReflectionClass($this);
        return dirname($class->getFileName());
    }

    protected function getResponse() {
        if (!$this->response) {
            $this->response = new Response();
        }
        return $this->response;
    }

    protected function render($model) {
        $templateFile = $this->getTemplateFile();
        if (!file_exists($templateFile)) {
            return json_encode($model);
        }

        $template = file_get_contents($templateFile);
        return $this->doRender($model, $template);
     }

    protected function getTemplateFile() {
        return $this->getDirectory() . '/' . $this->getTemplateFileName();
    }

    protected function getTemplateFileName() {
        $classReflection = new \ReflectionClass($this);
        return strtolower(substr($classReflection->getShortName(), 0, -strlen('Controller'))) . '.html';
    }

    protected function redirect(Url $url) {
        $urlString = $url->toString();
        if ($url->isRelative()) {
            $urlString = $this->root . $urlString;
        }
        $response = $this->getResponse();
        $response->getHeaders()->set(Response::HEADER_LOCATION, $urlString);
        return null;
    }

}
