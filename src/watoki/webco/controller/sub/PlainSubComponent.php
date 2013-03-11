<?php
namespace watoki\webco\controller\sub;

use watoki\collections\Map;
use watoki\webco\Request;
use watoki\webco\Response;
use watoki\webco\controller\Component;
use watoki\webco\controller\Module;
use watoki\webco\controller\SubComponent;

class PlainSubComponent extends SubComponent {

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
     * @var string
     */
    private $action;

    function __construct(Component $super, $componentClass, Map $defaultParameters = null) {
        parent::__construct($super, $defaultParameters);
        $this->componentClass = $componentClass;
        $this->action = Request::METHOD_GET;
    }

    public function render() {
        /** @var $response Response */
        $response = $this->getComponent()->respond(new Request($this->action, '', $this->getParameters()));
        return $response->getBody();
    }

    /**
     * @throws \Exception If Component cannot be found
     * @return \watoki\webco\controller\Component
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

    protected function getName() {
        if (!$this->name) {
            $this->name = $this->super->getSubComponents()->keyOf($this);
        }
        return $this->name;
    }

    protected function isDefaultParameter($key, $value) {
        return parent::isDefaultParameter($key, $value) || $this->isDefaultMethodArgument($key, $value);
    }


    private function isDefaultMethodArgument($key, $value) {
        $reflClass = new \ReflectionClass($this->getComponent());
        $methodName = $this->getComponent()->makeMethodName($this->action);
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

}