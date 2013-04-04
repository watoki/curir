<?php
namespace watoki\curir\renderer;

use watoki\collections\Map;
use watoki\curir\Renderer;

class NoneRenderer implements Renderer {

    public static $CLASS = __CLASS__;

    /**
     * @param array|object|Map $model The view model
     * @return string The rendered template
     */
    public function render($model) {
        return $model;
    }
}