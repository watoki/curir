<?php
namespace watoki\webco\controller\sub;

use watoki\webco\Request;
use watoki\webco\Response;
use watoki\webco\controller\Component;
use watoki\webco\controller\Module;
use watoki\webco\controller\SubComponent;

class PlainSubComponent extends SubComponent {

    /**
     * @var Component|null
     */
    private $component;

    /**
     * @var string
     */
    protected $componentClass;

    function __construct($name, Module $root, $componentClass) {
        parent::__construct($name, $root);
        $this->componentClass = $componentClass;
    }

    public function render() {
        /** @var $response Response */
        $response = $this->getComponent()->respond(new Request());
        return $response->getBody();
    }

    /**
     * @return null|\watoki\webco\controller\Component
     */
    protected function getComponent() {
        if (!$this->component) {
            $this->component = $this->root->findController($this->componentClass);
        }
        return $this->component;
    }

}