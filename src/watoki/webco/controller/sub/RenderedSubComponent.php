<?php
namespace watoki\webco\controller\sub;
 
use watoki\webco\controller\Component;
use watoki\webco\controller\SubComponent;

class RenderedSubComponent extends SubComponent {

    public static $CLASS = __CLASS__;

    private $content;

    function __construct(Component $super, $content) {
        parent::__construct($super);
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function render() {
        return $this->content;
    }
}
