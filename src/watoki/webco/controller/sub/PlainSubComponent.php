<?php
namespace watoki\webco\controller\sub;

use watoki\collections\Map;
use watoki\webco\Request;
use watoki\webco\Response;
use watoki\webco\controller\Component;
use watoki\webco\controller\SubComponent;

class PlainSubComponent extends SubComponent {

    public static $CLASS = __CLASS__;

    /**
     * @var null|Response
     */
    public $response;

    /**
     * @var string|null
     */
    private $name;

    /**
     * @var Component|null
     */
    private $component;

    /**
     * @var string
     */
    protected $componentClass;

    /**
     * @var \watoki\collections\Map
     */
    private $parameters;

    /**
     * @var \watoki\collections\Map
     */
    private $defaultParameters;

    /**
     * @var string
     */
    protected $method = Request::METHOD_GET;

    function __construct(Component $super, $componentClass, Map $defaultParameters = null) {
        parent::__construct($super, $defaultParameters);
        $this->componentClass = $componentClass;
        $this->parameters = $defaultParameters ?: new Map();
        $this->defaultParameters = $defaultParameters ? $defaultParameters->copy() : new Map();
    }

    public function render() {
        $this->response = $this->getComponent()->respond(new Request($this->method, '', $this->getParameters()));
        return $this->response->getBody();
    }

    /**
     * @throws \Exception If Component cannot be found
     * @return \watoki\webco\controller\Component
     * @return null|\watoki\webco\Controller|\watoki\webco\controller\Component
     */
    protected function getComponent() {
        if (!$this->component) {
            $this->component = $this->super->getRoot()->findController($this->componentClass);
            if (!$this->component) {
                throw new \Exception('Could not find route to ' . $this->componentClass);
            }
        }
        return $this->component;
    }

    public function setRoute($absoluteRoute) {
        $this->component = $this->super->getRoot()->resolve($absoluteRoute);
    }

    /**
     * @return null|\watoki\webco\Response
     */
    public function getResponse() {
        return $this->response;
    }

    protected function getName() {
        if (!$this->name) {
            $this->name = $this->super->getSubComponents()->keyOf($this);
        }
        return $this->name;
    }


    private function isDefaultMethodArgument($key, $value) {
        $reflClass = new \ReflectionClass($this->getComponent());
        $methodName = $this->getComponent()->makeMethodName($this->method);
        if (!$reflClass->hasMethod($methodName)) {
            return false;
        }
        $method = $reflClass->getMethod($methodName);
        foreach ($method->getParameters() as $param) {
            if ($param->getName() == $key) {
                return $param->isDefaultValueAvailable() && $param->getDefaultValue() == $value;
            }
        }
        return false;
    }

    /**
     * @return \watoki\collections\Map
     */
    public function getParameters() {
        return $this->parameters;
    }

    public function getNonDefaultParameters() {
        $nonDefault = new Map();
        foreach ($this->parameters as $key => $value) {
            if (!$this->isDefaultParameter($key, $value)) {
                $nonDefault->set($key, $value);
            }
        }
        return $nonDefault;
    }

    protected function isDefaultParameter($key, $value) {
        return $this->defaultParameters->has($key) && $this->defaultParameters->get($key) == $value
                || $this->isDefaultMethodArgument($key, $value);
    }

    /**
     * @param string $method
     */
    public function setMethod($method) {
        $this->method = $method;
    }

}