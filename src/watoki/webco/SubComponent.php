<?php
namespace watoki\webco;

use watoki\collections\Map;
use watoki\factory\Factory;

class SubComponent {

    /**
     * @var \watoki\webco\Module
     */
    private $root;

    /**
     * @var string
     */
    private $componentClass;

    function __construct(Module $root, $componentClass) {
        $this->root = $root;
        $this->componentClass = $componentClass;
    }

    public function render() {
        /** @var $component Component */
        $component = $this->root->findController($this->componentClass);
        $response = $component->respond(new Request(Request::METHOD_GET, '', new Map(), new Map()));
        return $response->getBody();
    }

}