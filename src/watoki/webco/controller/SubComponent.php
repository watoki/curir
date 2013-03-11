<?php
namespace watoki\webco\controller;

use watoki\collections\Map;
use watoki\webco\Request;

abstract class SubComponent {

    /**
     * @var \watoki\webco\controller\Component
     */
    protected $super;

    /**
     * @var \watoki\collections\Map
     */
    private $parameters;

    /**
     * @var \watoki\collections\Map
     */
    private $defaultParameters;

    /**
     * @return string
     */
    abstract public function render();

    function __construct(Component $super, Map $defaultParameters = null) {
        $this->super = $super;
        $this->parameters = $defaultParameters ?: new Map();
        $this->defaultParameters = $defaultParameters ? $defaultParameters->copy() : new Map();
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
            if (!($this->defaultParameters->has($key) && $this->defaultParameters->get($key) == $value)) {
                $nonDefault->set($key, $value);
            }
        }
        return $nonDefault;
    }

}