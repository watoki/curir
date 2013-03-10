<?php
namespace watoki\webco\controller\sub;

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

    function __construct(Component $super, $componentClass) {
        parent::__construct($super);
        $this->componentClass = $componentClass;
    }

    public function render() {
        /** @var $response Response */
        $response = $this->getComponent()->respond(new Request());
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

}