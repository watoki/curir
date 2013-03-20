<?php
namespace watoki\webco\controller\sub;
 
use watoki\collections\Map;
use watoki\webco\controller\Component;
use watoki\webco\controller\SubComponent;

class RenderedSubComponent extends SubComponent {

    public static $CLASS = __CLASS__;

    private $content;

    function __construct(Component $super, $content) {
        parent::__construct($super);
        $this->content = $content;
    }

    public function render($name, $state) {
        return $this->content;
    }

    /**
     * @return Map
     */
    public function getState() {
        return new Map();
    }
}
