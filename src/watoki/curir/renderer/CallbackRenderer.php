<?php
namespace watoki\curir\renderer;

class CallbackRenderer implements Renderer {

    /** @var callable */
    private $callback;

    public function __construct($callback) {
        $this->callback = $callback;
    }

    /**
     * @param string $template
     * @param mixed $model
     * @return string
     */
    public function render($template, $model) {
        return call_user_func($this->callback, $template, $model);
    }
}