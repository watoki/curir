<?php
namespace watoki\curir\controller;

use watoki\collections\Map;
use watoki\curir\MimeTypes;
use watoki\curir\renderer\RendererFactory;
use watoki\factory\Factory;
use watoki\curir\Controller;
use watoki\curir\Path;
use watoki\curir\Renderer;
use watoki\curir\Request;
use watoki\curir\Response;

abstract class Component extends Controller {

    /** @var RendererFactory */
    protected $rendererFactory;

    /**
     * @param Factory $factory
     * @param Path $route
     * @param Module|null $parent
     */
    function __construct(Factory $factory, Path $route, Module $parent = null) {
        parent::__construct($factory, $route, $parent);
        $this->rendererFactory = $factory->getInstance(RendererFactory::$CLASS);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function respond(Request $request) {
        $response = $this->getResponse();
        $methodName = $this->makeMethodName($this->getActionName($request));
        $rendered = $this->renderAction($methodName, $request->getParameters());
        if ($rendered) {
            $response->setBody($rendered);
            $contentType = MimeTypes::getType($this->getFormat());
            if ($contentType) {
                $response->getHeaders()->set(Response::HEADER_CONTENT_TYPE, $contentType);
            }
        }

        return $response;
    }

    /**
     * @param $action
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
     * @param string $action
     * @param Map $parameters
     * @return mixed The view model
     * @throws \Exception
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
     * @param Map $parameters
     * @param \ReflectionMethod $method
     * @return array Of values for the method's arguments
     * @throws \Exception
     */
    protected function collectArguments(Map $parameters, \ReflectionMethod $method) {
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
     * @param string $actionName
     * @return string
     */
    public function makeMethodName($actionName) {
        return 'do' . ucfirst($actionName);
    }

    /**
     * @param mixed $model
     * @return string
     */
    public function render($model) {
        return $this->getRenderer()->render($model);
    }

    /**
     * @return Renderer
     */
    protected function getRenderer() {
        return $this->rendererFactory->getRenderer($this, $this->getFormat());
    }

    /**
     * @return string
     */
    protected function getFormat() {
        $leafExtension = $this->getRoute()->getLeafExtension();
        if ($leafExtension) {
            return $leafExtension;
        }
        return $this->getDefaultFormat();
    }

    /**
     * @return string
     */
    protected function getDefaultFormat() {
        return 'html';
    }

    /**
     * @return Path
     */
    public function getBaseRoute() {
        return new Path($this->getRoute()->getNodes()->slice(0, -1));
    }

}
