<?php
namespace watoki\curir\responder;

use watoki\curir\http\error\HttpError;
use watoki\curir\http\MimeTypes;
use watoki\curir\http\Request;
use watoki\curir\http\Response;
use watoki\curir\Resource;
use watoki\curir\resource\DynamicResource;
use watoki\curir\Responder;

class Presenter extends Responder {

    /** @var mixed */
    private $model;

    /** @var DynamicResource */
    private $resource;

    /** @var array|callable[] indexed by format */
    private $renderers = array();

    function __construct(DynamicResource $resource, $viewModel = array()) {
        $this->resource = $resource;
        $this->model = $viewModel;
    }

    public function setRenderer($formats, $renderer) {
        if (!is_array($formats)) {
            $formats = array($formats);
        }
        foreach ($formats as $format) {
            $this->renderers[$format] = $renderer;
        }
    }

    /**
     * @param \watoki\curir\http\Request $request
     * @throws \watoki\curir\http\error\HttpError
     * @return \watoki\curir\http\Response
     */
    public function createResponse(Request $request) {
        $formats = $request->getFormats();

        foreach ($formats as $format) {
            try {
                $response = new Response($this->render($format));
                $response->getHeaders()->set(Response::HEADER_CONTENT_TYPE, MimeTypes::getType($format));
                return $response;
            } catch (\ReflectionException $e) {}
        }

        throw new HttpError(Response::STATUS_NOT_ACCEPTABLE, "Could not render the resource in an accepted format",
            "Invalid accepted types for [" . get_class($this->resource) . "] aka [" . $this->resource->getUrl() . "]: " .
            "[" . implode(', ', $formats) . "]");
    }

    private function render($format) {
        if (array_key_exists($format, $this->renderers)) {
            $renderer = $this->renderers[$format];
            return $renderer($this);
        }

        $method = new \ReflectionMethod($this, 'render' . ucfirst($format));

        if (count($method->getParameters())) {
            return $method->invoke($this, $this->getTemplate($format));
        } else {
            return $method->invoke($this);
        }
    }

    /**
     * @return mixed
     */
    public function getModel() {
        return $this->model;
    }

    public function getTemplate($format) {
        $fileName = $this->resource->getResourceName() . '.' . $format;
        $templateFile = $this->findFile($this->resource->getResourceDirectory(), $fileName);

        if (!$templateFile) {
            throw new \Exception("Could not find template [$fileName]");
        }
        return file_get_contents($templateFile);
    }

    protected function findFile($directory, $fileName) {
        foreach (glob($directory . DIRECTORY_SEPARATOR . '*') as $file) {
            if (strtolower(basename($file)) == strtolower($fileName)) {
                return $file;
            }
        }
        return null;
    }
}