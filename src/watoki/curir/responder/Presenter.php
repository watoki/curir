<?php
namespace watoki\curir\responder;

use watoki\curir\http\Request;
use watoki\curir\http\Response;
use watoki\curir\renderer\RendererFactory;
use watoki\curir\Resource;
use watoki\curir\Responder;

class Presenter extends Responder {

    /** @var mixed */
    private $viewModel;

    function __construct($viewModel = array()) {
        $this->viewModel = $viewModel;
    }

    /**
     * @param \watoki\curir\Resource $resource
     * @param \watoki\curir\http\Request $request
     * @return \watoki\curir\http\Response
     */
    public function createResponse(Resource $resource, Request $request) {
        $factory = new RendererFactory();
        $renderer = $factory->getRenderer($request->getFormat());
        $template = $this->getTemplate($resource);

        return new Response($renderer->render($template, $this->viewModel));
    }

    private function getTemplate($resource) {
        return '';
    }
}