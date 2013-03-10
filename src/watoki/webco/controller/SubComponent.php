<?php
namespace watoki\webco\controller;

use watoki\webco\Request;
use watoki\webco\controller\Module;

abstract class SubComponent {

    /**
     * @var \watoki\webco\controller\Component
     */
    protected $super;

    function __construct(Component $super) {
        $this->super = $super;
    }

    /**
     * @return
     */
    abstract public function render();

}