<?php
namespace watoki\webco\controller;

use watoki\collections\Map;
use watoki\webco\Controller;
use watoki\webco\Request;
use watoki\webco\Response;

class SubComponent {

    public static $CLASS = __CLASS__;

    /**
     * @var \watoki\webco\controller\Component
     */
    protected $super;

    /**
     * @var Request
     */
    private $defaultRequest;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
public $response;

    // TODO Use lazy Route type instead of component class
    function __construct(SuperComponent $super, $defaultComponent = null, Map $defaultParameters = null) {
        $this->super = $super;
        $defaultParameters = $defaultParameters ?: new Map();
        $route = $defaultComponent ? $super->getRoot()->findController($defaultComponent)->getRoute() : null;

        $this->defaultRequest = new Request(Request::METHOD_GET, $route, $defaultParameters);
        $this->request = new Request(Request::METHOD_GET, $route, $defaultParameters->copy());
    }

    public function getRequest() {
        return $this->request;
    }

    /**
     * @param $name
     * @param Map $superParameters
     * @return Response
     */
    public function execute($name, Map $superParameters) {
        $this->response = $this->postProcess($this->super->getRoot()->respond($this->request),
            $name, $superParameters);
        return $this->response;
    }

    /**
     * @return Response
     */
    public function getResponse() {
        if (!$this->response) {
            throw new \Exception('Cannot get Response. SubComponent needs to be executed first.');
        }
        return $this->response;
    }

    private function postProcess(Response $response, $name, Map $superParameters) {
        // TODO There needs to be a better way to handle the component instance
        $component = $this->super->getRoot()->resolve($this->request->getResource());
        $postProcessor = new SubComponentPostProcessor($name, $superParameters, $component, $this->super);
        $response->setBody($postProcessor->postProcess($response->getBody()));
        return $response;
    }

    public function getNonDefaultRequest() {
        return new Request(Request::METHOD_GET, $this->getNonDefaultResource(), $this->getNonDefaultParameters());
    }

    private function getNonDefaultResource() {
        if ($this->request->getResource() != $this->defaultRequest->getResource()) {
            return $this->request->getResource();
        } else {
            return null;
        }
    }

    /**
     * @return \watoki\collections\Map Containing the part of the state that was not set in the constructor
     */
    private function getNonDefaultParameters() {
        $parameters = new Map();
        foreach ($this->request->getParameters() as $key => $value) {
            if (!$this->isDefaultParameterValue($key, $value)) {
                $parameters->set($key, $value);
            }
        }
        return $parameters;
    }

    /**
     * @param $key
     * @param $value
     * @return bool
     */
    private function isDefaultParameterValue($key, $value) {
        return ($this->defaultRequest->getParameters()->has($key)
                    && $this->defaultRequest->getParameters()->get($key) == $value)
                || $this->isDefaultMethodArgument($key, $value);
    }

    private function isDefaultMethodArgument($key, $value) {
        /** @var $component Component */
        $component = $this->super->getRoot()->resolve($this->request->getResource());
        $class = new \ReflectionClass($component);
        $methodName = $component->makeMethodName($this->request->getMethod());

        if (!$class->hasMethod($methodName)) {
            return false;
        }
        $method = $class->getMethod($methodName);
        foreach ($method->getParameters() as $param) {
            if ($param->getName() == $key) {
                return $param->isDefaultValueAvailable() && $param->getDefaultValue() == $value;
            }
        }
        return false;
    }

}