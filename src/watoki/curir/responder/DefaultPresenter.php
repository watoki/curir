<?php
namespace watoki\curir\responder;

use watoki\tempan\Renderer;

class DefaultPresenter extends Presenter {

    public function renderJson() {
        return json_encode($this->getModel());
    }

    public function renderHtml($template) {
        $renderer = new Renderer($template);
        $renderer->render($this->getModel());
    }

} 