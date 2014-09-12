<?php
namespace watoki\curir;

use watoki\factory\Factory;

class Resource {

    /** @var Factory */
    private $factory;

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
     * @param Responder $responder
     * @param WebRequest $request
     * @return WebResponse|null
     */
    public function after(Responder $responder, WebRequest $request) {
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