<?php
namespace watoki\curir\responder;

use watoki\curir\http\Request;
use watoki\curir\http\Response;
use watoki\curir\http\Url;
use watoki\curir\Resource;
use watoki\curir\resource\DynamicResource;
use watoki\curir\Responder;

class Redirecter extends Responder {

    /** @var \watoki\curir\http\Url */
    private $target;

    function __construct(Url $target) {
        $this->target = $target;
    }

    /**
     * @param \watoki\curir\resource\DynamicResource $resource
     * @param \watoki\curir\http\Request $request
     * @return \watoki\curir\http\Response
     */
    public function createResponse(DynamicResource $resource, Request $request) {
        $response = new Response();
        $response->getHeaders()->set(Response::HEADER_LOCATION, $this->target->toString());
        return $response;
    }

    /**
     * @return \watoki\curir\http\Url
     */
    public function getTarget() {
        return $this->target;
    }

    /**
     * @param \watoki\curir\http\Url $target
     */
    public function setTarget($target) {
        $this->target = $target;
    }
}