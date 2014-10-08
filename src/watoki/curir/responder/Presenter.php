<?php
namespace watoki\curir\responder;

use watoki\curir\error\HttpError;
use watoki\curir\protocol\MimeTypes;
use watoki\curir\renderer\Renderer;
use watoki\curir\Resource;
use watoki\curir\Responder;
use watoki\curir\delivery\WebRequest;
use watoki\curir\delivery\WebResponse;
use watoki\factory\Factory;
use watoki\stores\file\raw\RawFileStore;

class Presenter implements Responder {

    /** @var mixed */
    private $model;

    /** @var Resource */
    private $resource;

    /** @var Factory */
    private $factory;

    function __construct($viewModel = null, Resource $resource, Factory $factory) {
        $this->model = $viewModel;
        $this->resource = $resource;
        $this->factory = $factory;
    }

    /**
     * @return mixed
     */
    public function getModel() {
        return $this->model;
    }

    public function renderJson() {
        return json_encode($this->getModel());
    }

    public function renderHtml() {
        return $this->getRenderer()->render($this->getTemplate('html'), $this->getModel());
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
            "Invalid accepted types for [" . get_class($this->resource) . "]: " .
            "[" . $formats->join(', ') . "]");
    }

    private function render($format) {
        $method = new \ReflectionMethod($this, 'render' . ucfirst($format));
        return $method->invoke($this);
    }

    protected function getTemplate($format) {
        $templateFile = $this->resource->getTemplateName() . '.' . $format;

        /** @var RawFileStore $store */
        $store = $this->factory->getInstance(RawFileStore::$CLASS, array(
            'rootDirectory' => $this->resource->getDirectory(),
        ));

        if (!$store->exists($templateFile)) {
            $class = get_class($this->resource);
            throw new \Exception("Could not find template [$templateFile] for [$class]");
        }
        return $store->read($templateFile)->content;
    }

    /**
     * @return Renderer
     */
    protected function getRenderer() {
        return $this->factory->getInstance(Renderer::RENDERER);
    }
}