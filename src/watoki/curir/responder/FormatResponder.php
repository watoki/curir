<?php
namespace watoki\curir\responder;

use watoki\curir\error\HttpError;
use watoki\curir\protocol\MimeTypes;
use watoki\curir\rendering\Renderer;
use watoki\curir\rendering\TemplateLocator;
use watoki\curir\Responder;
use watoki\curir\delivery\WebRequest;
use watoki\curir\delivery\WebResponse;

/**
 * Invokes a `render$format()` method when creating the Response and sets the content type.
 */
class FormatResponder implements Responder {

    /** @var mixed */
    private $model;

    /** @var \watoki\curir\rendering\TemplateLocator */
    private $templateLocator;

    /** @var Renderer */
    private $renderer;

    function __construct($viewModel = null, TemplateLocator $locator, Renderer $renderer) {
        $this->model = $viewModel;
        $this->templateLocator = $locator;
        $this->renderer = $renderer;
    }

    public function renderJson() {
        return json_encode($this->getModel());
    }

    public function renderHtml() {
        return $this->renderer->render($this->getTemplate('html'), $this->getModel());
    }

    /**
     * @return mixed
     */
    public function getModel() {
        return $this->model;
    }

    /**
     * @param WebRequest $request
     * @throws \watoki\curir\error\HttpError
     * @return WebResponse
     */
    public function createResponse(WebRequest $request) {
        $formats = $request->getFormats();

        foreach ($formats as $format) {
            try {
                $response = new WebResponse($this->render($format));
                $response->getHeaders()->set(WebResponse::HEADER_CONTENT_TYPE, MimeTypes::getType($format));
                return $response;
            } catch (\ReflectionException $e) {}
        }

        throw new HttpError(WebResponse::STATUS_NOT_ACCEPTABLE, "Could not render the resource in an accepted format.",
            "Invalid accepted types: [" . $formats->join(', ') . "]");
    }

    private function render($format) {
        $method = new \ReflectionMethod($this, 'render' . ucfirst($format));
        return $method->invoke($this);
    }

    protected function getTemplate($format) {
        return $this->templateLocator->find($format);
    }
}