<?php
namespace watoki\curir;

use watoki\curir\delivery\WebRequest;
use watoki\curir\delivery\WebResponse;
use watoki\curir\responder\Presenter;
use watoki\factory\Factory;

class Resource {

    /** @var Factory */
    protected $factory;

    /**
     * @param Factory $factory <-
     */
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
        if ($return instanceof WebResponse) {
            return $return;
        } else if ($return instanceof Responder) {
            return $return->createResponse($request);
        } else if (is_array($return)) {
            return $this->createDefaultResponder($return)->createResponse($request);
        } else {
            return $this->createResponseFromString((string) $return);
        }
    }

    /**
     * @param $return
     * @return Responder
     */
    protected function createDefaultResponder($return) {
        return new Presenter($return, $this, $this->factory);
    }

    /**
     * @param $return
     * @return WebResponse
     */
    protected function createResponseFromString($return) {
        return new WebResponse($return);
    }

    public function getName() {
        $reflection = new \ReflectionClass($this);
        return lcfirst(substr(basename($reflection->getShortName()), 0, -strlen(WebRouter::SUFFIX)));
    }

    public function getTemplateName() {
        return $this->getName();
    }

    public function getDirectory() {
        $reflection = new \ReflectionClass($this);
        return dirname($reflection->getFileName());
    }

}