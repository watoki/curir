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
     * @return string
     */
    abstract public function render();

    function __construct(Component $super) {
        $this->super = $super;
    }

}