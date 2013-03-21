<?php
namespace watoki\webco\controller;

use watoki\collections\Map;
use watoki\webco\Request;

abstract class SubComponent {

    public static $CLASS = __CLASS__;

    /**
     * @var \watoki\webco\controller\Component
     */
    protected $super;

    /**
     * @var \watoki\collections\Map
     */
    private $defaultState;

    /**
     * @var Map
     */
    private $state;

    function __construct(SuperComponent $super, Map $defaultState) {
        $this->super = $super;
        $this->defaultState = $defaultState;
        $this->state = $defaultState->copy();
    }

    /**
     * @param string $name
     * @param Map $superState
     * @return string
     */
    abstract public function render($name, Map $superState);

    /**
     * @return Map Containing parameters and target
     */
    public function getState() {
        return $this->state;
    }

    /**
     * @return \watoki\collections\Map Containing the part of the state that was not set in the constructor
     */
    public function getNonDefaultState() {
        $nonDefaultState = new Map();
        foreach ($this->getState() as $key => $value) {
            if (!$this->isDefaultStateValue($key, $value)) {
                $nonDefaultState->set($key, $value);
            }
        }
        return $nonDefaultState;
    }

    /**
     * @param $key
     * @param $value
     * @return bool
     */
    protected function isDefaultStateValue($key, $value) {
        return $this->defaultState->has($key) && $this->defaultState->get($key) == $value;
    }

}