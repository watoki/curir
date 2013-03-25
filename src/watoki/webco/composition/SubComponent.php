<?php
namespace watoki\webco\composition;

use watoki\collections\Liste;
use watoki\collections\Map;
use watoki\webco\Controller;
use watoki\webco\Path;
use watoki\webco\Request;
use watoki\webco\Response;
use watoki\webco\controller\Component;
use watoki\webco\composition\PostProcessor;
use watoki\webco\composition\SuperComponent;

class SubComponent {

    public static $CLASS = __CLASS__;

    /**
     * @var Liste
     */
    public $headElements;

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

    function __construct(SuperComponent $super, Path $defaultRoute = null, Map $defaultParameters = null) {
        $this->super = $super;
        $defaultParameters = $defaultParameters ?: new Map();

        $this->defaultRequest = new Request(Request::METHOD_GET, $defaultRoute ?: new Path(), $defaultParameters);
        $this->request = new Request(Request::METHOD_GET, $defaultRoute ? $defaultRoute->copy() : new Path(), $defaultParameters->copy());
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
        $this->response = $this->postProcess($this->super->getRoot()->respond($this->request->copy()),
            $name, $superParameters);
        return $this->response;
    }

    /**
     * @throws \Exception
     * @return Response
     */
    public function getResponse() {
        if (!$this->response) {
            throw new \Exception('Cannot get Response. SubComponent needs to be executed first.');
        }
        return $this->response;
    }

    private function postProcess(Response $response, $name, Map $superParameters) {
        /** @var $component Component */
        $component = $this->super->getRoot()->resolve($this->request->getResource());
        $postProcessor = new PostProcessor($name, $superParameters, $component, $this->super);
        $this->headElements = $postProcessor->getHeadElements();
        return $postProcessor->postProcess($response);
    }

    /**
     * @param string|null $nodeName Filter by node name (if given)
     * @return \watoki\collections\Liste
     */
    public function getHeadElements($nodeName = null) {
        if (!$nodeName) {
            return $this->headElements;
        } else {
            return $this->headElements->filter(function (\DOMNode $element) use ($nodeName) {
                return $element->nodeName == $nodeName;
            });
        }
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