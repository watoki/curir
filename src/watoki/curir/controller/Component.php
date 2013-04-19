<?php
namespace watoki\curir\controller;

use watoki\collections\Map;
use watoki\curir\renderer\RendererFactory;
use watoki\factory\Factory;
use watoki\curir\Controller;
use watoki\curir\Path;
use watoki\curir\Renderer;
use watoki\curir\Request;
use watoki\curir\Response;

abstract class Component extends Controller {

    public static $CLASS = __CLASS__;

    /**
     * @var RendererFactory
     */
    protected $rendererFactory;

    function __construct(Factory $factory, Path $route, Module $parent = null) {
        parent::__construct($factory, $route, $parent);
        $this->rendererFactory = $factory->getInstance(RendererFactory::$CLASS);
    }

    /**
     * @param Request $request
     * @throws \Exception
     * @return Response
     */
    public function respond(Request $request) {
        $response = $this->getResponse();
        $methodName = $this->makeMethodName($this->getActionName($request));
        $response->setBody($this->renderAction($methodName, $request->getParameters()));

        return $response;
    }

    /**
     * @param string $action
     * @param Map $parameters
     * @return null|string
     */
    protected function renderAction($action, Map $parameters) {
        $model = $this->invokeAction($action, $parameters);
        if ($model === null) {
            return null;
        }

        return $this->render($model);
    }

    /**
     * @param $action
     * @param Map $parameters
     * @throws \Exception
     * @return mixed
     */
    protected function invokeAction($action, Map $parameters) {
        if (!method_exists($this, $action)) {
            throw new \Exception('Method [' . $action . '] not found in component [' . get_class($this) . '].');
        }

        $method = new \ReflectionMethod($this, $action);
        $args = $this->collectArguments($parameters, $method);

        return $method->invokeArgs($this, $args);
    }

    /**
     * @param \watoki\collections\Map $parameters
     * @param \ReflectionMethod $method
     * @throws \Exception If a parameter is missing
     * @return array
     */
    private function collectArguments(Map $parameters, \ReflectionMethod $method) {
        $args = array();
        foreach ($method->getParameters() as $param) {
            if ($parameters->has($param->getName())) {
                $args[] = $parameters->get($param->getName());
            } else if (!$param->isOptional()) {
                $class = get_class($this);
                throw new \Exception(
                    "Invalid request: Missing Parameter [{$param->getName()}] for method [{$method->getName()}] in component [$class]");
            } else {
                $args[] = $param->getDefaultValue();
            }
        }
        return $args;
    }

    /**
     * @param Request $request
     * @return string
     */
    protected function getActionName(Request $request) {
        return $request->getParameters()->has('action')
                ? $request->getParameters()->get('action')
                : strtolower($request->getMethod());
    }

    /**
     * @param $actionName
     * @return string
     */
    public function makeMethodName($actionName) {
        return 'do' . ucfirst($actionName);
    }

    protected function render($model) {
        return $this->getRenderer()->render($model);
    }

    /**
     * @return Renderer
     */
    protected function getRenderer() {
        return $this->rendererFactory->getRenderer($this, $this->getFormat());
    }

    protected function getFormat() {
        $leafExtension = $this->getRoute()->getLeafExtension();
        if ($leafExtension) {
            return $leafExtension;
        }
        return $this->getDefaultFormat();
    }

    protected function getDefaultFormat() {
        return 'html';
    }

    /**
     * @return \watoki\curir\Path
     */
    public function getBaseRoute() {
        return new Path($this->getRoute()->getNodes()->slice(0, -1));
    }

}
