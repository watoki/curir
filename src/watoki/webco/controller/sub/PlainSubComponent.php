<?php
namespace watoki\webco\controller\sub;

use watoki\collections\Map;
use watoki\webco\Request;
use watoki\webco\Response;
use watoki\webco\controller\Component;
use watoki\webco\controller\SubComponent;
use watoki\webco\controller\SuperComponent;

class PlainSubComponent extends SubComponent {

    public static $CLASS = __CLASS__;

    /**
     * @var null|Response
     */
    public $response;

    /**
     * @var Component|null
     */
    private $component;

    /**
     * @var string
     */
    protected $componentClass;

    /**
     * @var string
     */
    protected $method = Request::METHOD_GET;

    function __construct(SuperComponent $super, $componentClass, Map $defaultState = null) {
        parent::__construct($super, $defaultState ?: new Map());
        $this->componentClass = $componentClass;
    }

    public function render($name, Map $superState) {
        $this->response = $this->getComponent()->respond(
            new Request($this->method, '', $this->getState()));
        return $this->response->getBody();
    }

    /**
     * @throws \Exception If Component cannot be found
     * @return \watoki\webco\controller\Component
     * @return null|\watoki\webco\Controller|\watoki\webco\controller\Component
     */
    protected function getComponent() {
        if (!$this->component) {
            if ($this->getState()->has(SuperComponent::PARAMETER_TARGET)) {
                $route = $this->getState()->get(SuperComponent::PARAMETER_TARGET);
                $this->component = $this->super->getRoot()->resolve($route);
                if (!$this->component) {
                    throw new \Exception('Could not resolve route ' . $route);
                }
            } else {
                $this->component = $this->super->getRoot()->findController($this->componentClass);
                if (!$this->component) {
                    throw new \Exception('Could not find route to ' . (string)$this->componentClass);
                }
            }
        }
        return $this->component;
    }

    /**
     * @return null|\watoki\webco\Response
     */
    public function getResponse() {
        return $this->response;
    }

    protected function isDefaultStateValue($key, $value) {
        return parent::isDefaultStateValue($key, $value) || $this->isDefaultMethodArgument($key, $value);
    }

    private function isDefaultMethodArgument($key, $value) {
        $class = new \ReflectionClass($this->getComponent());
        $methodName = $this->getComponent()->makeMethodName($this->method);
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

    public function setMethod($method) {
        $this->method = $method;
    }
}