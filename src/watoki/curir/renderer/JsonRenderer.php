<?php
namespace watoki\curir\renderer;

use watoki\collections\Map;
use watoki\curir\Renderer;

class JsonRenderer implements Renderer {

    public static $CLASS = __CLASS__;

    /**
     * @param string $template The template to be rendered
     * @param array|object|Map $model The view model
     * @return string The rendered template
     */
    public function render($template, $model) {
        return json_encode($model);
    }

    public function needsTemplate() {
        return false;
    }
}