<?php
namespace watoki\curir\responder;

use watoki\curir\Resource;
use watoki\curir\Responder;
use watoki\curir\protocol\Url;
use watoki\curir\delivery\WebRequest;
use watoki\curir\delivery\WebResponse;
use watoki\factory\Factory;

class Redirecter implements Responder {

    /** @var \watoki\curir\protocol\Url */
    private $target;

    /** @var string */
    private $status;

    /**
     * @param string $target Can be a absolute URL or relative to the Resource
     * @param string $status
     * @return Redirecter
     */
    public static function fromString($target, $status = WebResponse::STATUS_SEE_OTHER) {
        return new Redirecter(Url::fromString($target), $status);
    }

    function __construct(Url $target, $status = WebResponse::STATUS_SEE_OTHER) {
        $this->target = $target;
        $this->status = $status;
    }

    /**
     * @param WebRequest $request
     * @param \watoki\curir\Resource $resource
     * @param \watoki\factory\Factory $factory
     * @return \watoki\curir\delivery\WebResponse
     */
    public function createResponse(WebRequest $request, Resource $resource, Factory $factory) {
        $response = new WebResponse();
        $response->setStatus($this->status);
        $response->getHeaders()->set(WebResponse::HEADER_LOCATION, $this->getAbsoluteTarget($request)->toString());
        return $response;
    }

    /**
     * @param WebRequest $request
     * @return Url
     */
    private function getAbsoluteTarget(WebRequest $request) {
        $target = $this->getTarget();
        if ($target->isAbsolute()) {
            return $target;
        }
        return Url::fromString($request->getContext()->toString() . ($target->isEmpty() ? '' : '/' . $target));
    }

    /**
     * @return Url
     */
    public function getTarget() {
        return $this->target;
    }
}