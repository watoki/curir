<?php
namespace watoki\curir\rendering\adapter;

use watoki\curir\rendering\Renderer;

class TempanRenderer implements Renderer {

    /**
     * @param string $template
     * @param mixed $model
     * @throws \Exception
     * @return string
     */
    public function render($template, $model) {
        if (!class_exists('watoki\tempan\Renderer')) {
            throw new \Exception('You must install watoki/tempan to be able to use this renderer');
        }
        $renderer = new \watoki\tempan\Renderer($template);
        return $renderer->render($model);
    }
}