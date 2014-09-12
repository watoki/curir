<?php
namespace watoki\curir\responder;

use watoki\curir\Resource;
use watoki\curir\Responder;
use watoki\curir\Url;
use watoki\curir\WebRequest;
use watoki\curir\WebResponse;
use watoki\factory\Factory;

class Redirecter implements Responder {

    /** @var Url */
    private $target;

    /** @var string */
    private $status;

    public static function fromString($string, $status = WebResponse::STATUS_SEE_OTHER) {
        return new Redirecter(Url::fromString($string), $status);
    }

    function __construct(Url $target, $status) {
        $this->target = $target;
        $this->status = $status;
    }

    /**
     * @param WebRequest $request
     * @param \watoki\curir\Resource $resource
     * @param \watoki\factory\Factory $factory
     * @return WebResponse
     */
    public function createResponse(WebRequest $request, Resource $resource, Factory $factory) {
        $response = new WebResponse();
        $response->setStatus($this->status);
        $response->getHeaders()->set(WebResponse::HEADER_LOCATION, $this->target->toString());
        return $response;
    }

    /**
     * @return Url
     */
    public function getTarget() {
        return $this->target;
    }
}