<?php
namespace watoki\webco\controller\sub;

use watoki\webco\Request;
use watoki\webco\controller\Component;
use watoki\webco\controller\Module;
use watoki\webco\controller\SubComponent;

class PlainSubComponent extends SubComponent {

    /**
     * @var string
     */
    protected $componentClass;

    function __construct($name, Module $root, $componentClass) {
        parent::__construct($name, $root);
        $this->componentClass = $componentClass;
    }

    public function render() {
        /** @var $component Component */
        $component = $this->root->findController($this->componentClass);
        $response = $component->respond(new Request());
        return $response->getBody();
    }

}