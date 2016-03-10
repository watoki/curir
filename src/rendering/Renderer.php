<?php
namespace watoki\curir\rendering;

interface Renderer {

    const RENDERER = __CLASS__;

    /**
     * @param string $template
     * @param mixed $model
     * @return string
     */
    public function render($template, $model);

} 