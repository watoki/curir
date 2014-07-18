<?php
namespace watoki\curir\responder;

use watoki\curir\http\Request;
use watoki\curir\http\Response;
use watoki\curir\http\Url;
use watoki\curir\Resource;
use watoki\curir\Responder;

class Redirecter extends Responder {

    /** @var \watoki\curir\http\Url */
    private $target;

    /** @var string */
    private $status;

    function __construct(Url $target, $status = Response::STATUS_SEE_OTHER) {
        $this->target = $target;
        $this->status = $status;
    }

    /**
     * @param \watoki\curir\http\Request $request
     * @return \watoki\curir\http\Response
     */
    public function createResponse(Request $request) {
        $response = new Response();
        $response->setStatus($this->status);
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