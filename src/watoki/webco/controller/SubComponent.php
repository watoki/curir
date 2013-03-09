<?php
namespace watoki\webco\controller;

use watoki\webco\Request;
use watoki\webco\controller\Module;

abstract class SubComponent {

    /**
     * @var \watoki\webco\controller\Module
     */
    protected $root;

    /**
     * @var string The local name of the SubComponent for its super
     */
    protected $name;

    function __construct($name, Module $root) {
        $this->name = $name;
        $this->root = $root;
    }

    /**
     * @return
     */
    abstract public function render();

}