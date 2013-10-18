<?php
namespace watoki\curir\responder;

use watoki\curir\http\Path;
use watoki\curir\http\Request;
use watoki\curir\http\Response;
use watoki\curir\Resource;
use watoki\curir\Responder;

class Redirecter extends Responder {

    private $target;

    function __construct(Path $target) {
        $this->target = $target;
    }

    /**
     * @param \watoki\curir\Resource $resource
     * @param \watoki\curir\http\Request $request
     * @return \watoki\curir\http\Response
     */
    public function createResponse(Resource $resource, Request $request) {
        $response = new Response();
        $response->getHeaders()->set(Response::HEADER_LOCATION, $this->target->toString());
        return $response;
    }
}