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

    /** @var null|RendererFactory */
    private $rendererFactory;

    function __construct($viewModel = array()) {
        $this->viewModel = $viewModel;
    }

    /**
     * @param \watoki\curir\Resource $resource
     * @param \watoki\curir\http\Request $request
     * @return \watoki\curir\http\Response
     */
    public function createResponse(Resource $resource, Request $request) {
        $renderer = $this->getRendererFactory()->getRenderer($request->getFormat());

        if ($renderer->needsTemplate()) {
            $template = $this->getTemplate($resource, $request->getFormat());
        } else {
            $template = null;
        }

        return new Response($renderer->render($template, $this->viewModel));
    }

    private function getTemplate(Resource $resource, $format) {
        $templateFile = $resource->getDirectory() . DIRECTORY_SEPARATOR . lcfirst($resource->getName()) . '.' . $format;
        if (!file_exists($templateFile)) {
            throw new \Exception("Could not find template [$templateFile]");
        }
        return file_get_contents($templateFile);
    }

    /**
     * @return RendererFactory
     */
    public function getRendererFactory() {
        if (!$this->rendererFactory) {
            $this->rendererFactory = new RendererFactory();
        }
        return $this->rendererFactory;
    }
}