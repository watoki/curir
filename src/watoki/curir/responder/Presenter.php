<?php
namespace watoki\curir\responder;

use watoki\curir\http\Request;
use watoki\curir\http\Response;
use watoki\curir\Resource;
use watoki\curir\Responder;

class Presenter extends Responder {

    /** @var mixed */
    private $model;

    function __construct($viewModel = array()) {
        $this->model = $viewModel;
    }

    /**
     * @param \watoki\curir\Resource $resource
     * @param \watoki\curir\http\Request $request
     * @return \watoki\curir\http\Response
     */
    public function createResponse(Resource $resource, Request $request) {
        $templateFile = $this->getTemplateFile($resource, $request->getFormat());
        return new Response($this->render($templateFile, $request->getFormat()));
    }

    private function render($templateFile, $format) {
        $method = new \ReflectionMethod($this, 'render' . ucfirst($format));

        if (count($method->getParameters())) {
            return $method->invoke($this, $this->getTemplate($templateFile));
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

    private function getTemplate($templateFile) {
        if (!file_exists($templateFile)) {
            throw new \Exception("Could not find template [$templateFile]");
        }
        return file_get_contents($templateFile);
    }

    protected function getTemplateFile(Resource $resource, $format) {
        return $resource->getDirectory() . DIRECTORY_SEPARATOR . lcfirst($resource->getName()) . '.' . $format;
    }
}