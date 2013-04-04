<?php
namespace watoki\curir;
 
use watoki\collections\Map;

interface Renderer {

    const CLASS_NAME = __CLASS__;

    /**
     * @param array|object|Map $model The view model
     * @return string The rendered template
     */
    public function render($model);

}
