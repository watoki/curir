<?php
namespace watoki\curir;

use watoki\curir\delivery\WebRequest;
use watoki\curir\delivery\WebResponse;
use watoki\curir\responder\Presenter;
use watoki\factory\Factory;

class Resource {

    /** @var Factory */
    protected $factory;

    function __construct(Factory $factory) {
        $this->factory = $factory;
    }

    /**
     * @param WebRequest $request
     * @return WebRequest|null
     */
    public function before(WebRequest $request) {
        return $request;
    }

    /**
     * @param Responder|string|array $return
     * @param WebRequest $request
     * @return WebResponse|null
     */
    public function after($return, WebRequest $request) {
        if ($return instanceof Responder) {
            return $this->createResponse($return, $request);
        } else if (is_array($return)) {
            return $this->createResponse(new Presenter($return), $request);
        } else {
            return new WebResponse((string) $return);
        }
    }

    /**
     * @param Responder $responder
     * @param WebRequest $request
     * @return WebResponse
     */
    protected function createResponse(Responder $responder, WebRequest $request) {
        return $responder->createResponse($request, $this, $this->factory);
    }

    public function getName() {
        $reflection = new \ReflectionClass($this);
        return substr(basename($reflection->getShortName()), 0, -strlen(WebRouter::SUFFIX));
    }

    public function getTemplateName() {
        return lcfirst($this->getName());
    }

    public function getDirectory() {
        $reflection = new \ReflectionClass($this);
        return dirname($reflection->getFileName());
    }

}