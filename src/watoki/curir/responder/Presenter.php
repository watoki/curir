<?php
namespace watoki\curir\responder;

use watoki\curir\error\HttpError;
use watoki\curir\MimeTypes;
use watoki\curir\Resource;
use watoki\curir\Responder;
use watoki\curir\WebRequest;
use watoki\curir\WebResponse;
use watoki\factory\Factory;
use watoki\stores\file\raw\RawFileStore;

abstract class Presenter implements Responder {

    /** @var mixed */
    private $model;

    function __construct($viewModel = null) {
        $this->model = $viewModel;
    }

    /**
     * @return mixed
     */
    public function getModel() {
        return $this->model;
    }

    /**
     * @param WebRequest $request
     * @param \watoki\curir\Resource $resource
     * @param \watoki\factory\Factory $factory
     * @throws \watoki\curir\error\HttpError
     * @return WebResponse
     */
    public function createResponse(WebRequest $request, Resource $resource, Factory $factory) {
        $formats = $request->getFormats();

        foreach ($formats as $format) {
            try {
                $response = new WebResponse($this->render($format, $resource, $factory));
                $response->getHeaders()->set(WebResponse::HEADER_CONTENT_TYPE, MimeTypes::getType($format));
                return $response;
            } catch (\ReflectionException $e) {}
        }

        throw new HttpError(WebResponse::STATUS_NOT_ACCEPTABLE, "Could not render the resource in an accepted format.",
            "Invalid accepted types for [" . get_class($resource) . "]: " .
            "[" . $formats->join(', ') . "]");
    }

    private function render($format, Resource $resource, Factory $factory) {
        $method = new \ReflectionMethod($this, 'render' . ucfirst($format));

        if (count($method->getParameters())) {
            return $method->invoke($this, $this->getTemplate($format, $resource, $factory));
        } else {
            return $method->invoke($this);
        }
    }

    public function getTemplate($format, Resource $resource, Factory $factory) {
        $templateFile = $resource->getTemplateName() . '.' . $format;

        /** @var RawFileStore $store */
        $store = $factory->getInstance(RawFileStore::$CLASS, array(
            'rootDirectory' => $resource->getDirectory(),
        ));

        if (!$store->exists($templateFile)) {
            $class = get_class($resource);
            throw new \Exception("Could not find template [$templateFile] for [$class]");
        }
        return $store->read($templateFile)->content;
    }
}