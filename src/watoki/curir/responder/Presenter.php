<?php
namespace watoki\curir\responder;

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

    function __construct(DynamicResource $resource, $viewModel = array()) {
        $this->resource = $resource;
        $this->model = $viewModel;
    }

    /**
     * @param \watoki\curir\http\Request $request
     * @return \watoki\curir\http\Response
     */
    public function createResponse(Request $request) {
        $format = $request->getFormat();
        $response = new Response($this->render($format));
        $response->getHeaders()->set(Response::HEADER_CONTENT_TYPE, MimeTypes::getType($format));
        return $response;
    }

    private function render($format) {
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

    private function getTemplate($format) {
        $templateFile = $this->findFile($this->resource->getResourceDirectory(), $this->resource->getResourceName() . '.' . $format);

        if (!$templateFile) {
            throw new \Exception("Could not find template [$templateFile]");
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