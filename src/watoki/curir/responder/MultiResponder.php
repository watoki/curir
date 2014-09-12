<?php
namespace watoki\curir\responder;

use watoki\curir\MimeTypes;
use watoki\curir\Resource;
use watoki\curir\Responder;
use watoki\curir\WebRequest;
use watoki\curir\WebResponse;
use watoki\factory\Factory;

class MultiResponder implements Responder {

    /** @var array|callable[] indexed by format */
    private $renderers = array();

    /**
     * @param string $body Default body
     */
    function __construct($body = '') {
        $this->setBody('', $body);
    }

    public function setBody($format, $body) {
        $this->setRenderer($format, function () use ($body) {
            return $body;
        });
    }

    public function setRenderer($format, callable $renderer) {
        $this->renderers[$format] = $renderer;
    }

    /**
     * @param WebRequest $request
     * @param \watoki\curir\Resource $resource
     * @param Factory $factory
     * @return WebResponse
     */
    public function createResponse(WebRequest $request, Resource $resource, Factory $factory) {
        $formats = $request->getFormats();

        foreach ($formats as $accepted) {
            if (array_key_exists($accepted, $this->renderers)) {
                return $this->respondWith($accepted);
            }
        }

        return new WebResponse($this->getDefaultBody());
    }

    private function getDefaultBody() {
        return isset($this->renderers['']) ? call_user_func($this->renderers['']) : '';
    }

    private function respondWith($accepted) {
        $body = call_user_func($this->renderers[$accepted]);
        $response = new WebResponse($body);
        $response->getHeaders()->set(WebResponse::HEADER_CONTENT_TYPE, MimeTypes::getType($accepted));
        return $response;
    }
}