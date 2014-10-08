<?php
namespace watoki\curir\renderer;

use watoki\tempan\Renderer as TempanRenderer;

class DefaultRenderer implements Renderer {

    public static $CLASS = __CLASS__;

    /**
     * @param string $template
     * @param mixed $model
     * @return string
     */
    public function render($template, $model) {
        $tempan = new TempanRenderer($template);
        return $tempan->render($model);
    }
}