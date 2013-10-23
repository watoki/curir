<?php
namespace watoki\curir\responder;

use watoki\curir\http\Request;
use watoki\curir\http\Response;
use watoki\curir\Resource;
use watoki\curir\resource\DynamicResource;
use watoki\curir\Responder;

class Presenter extends Responder {

    /** @var mixed */
    private $model;

    function __construct($viewModel = array()) {
        $this->model = $viewModel;
    }

    /**
     * @param \watoki\curir\resource\DynamicResource $resource
     * @param \watoki\curir\http\Request $request
     * @return \watoki\curir\http\Response
     */
    public function createResponse(DynamicResource $resource, Request $request) {
        $format = $request->getFormat() ? : $resource->getDefaultFormat();
        return new Response($this->render($resource, $format));
    }

    private function render(DynamicResource $resource, $format) {
        $method = new \ReflectionMethod($this, 'render' . ucfirst($format));

        if (count($method->getParameters())) {
            return $method->invoke($this, $this->getTemplate($resource, $format));
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

    private function getTemplate(DynamicResource $resource, $format) {
        $templateFile = $this->findFile($resource->getResourceDirectory(), $resource->getResourceName() . '.' . $format);

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