<?php
namespace watoki\curir\responder;

use watoki\collections\Liste;
use watoki\curir\delivery\WebRequest;
use watoki\curir\delivery\WebResponse;
use watoki\curir\error\HttpError;
use watoki\curir\protocol\MimeTypes;
use watoki\curir\Responder;

class MultiResponder implements Responder {

    /** @var array|callable[] indexed by format */
    private $renderers = array();

    /**
     * @param string $defaultBody
     */
    function __construct($defaultBody = null) {
        if ($defaultBody) {
            $this->setBody('', $defaultBody);
        }
    }

    /**
     * @param string $format
     * @param string $body
     * @return $this
     */
    public function setBody($format, $body) {
        $this->setRenderer($format, function () use ($body) {
            return $body;
        });
        return $this;
    }

    /**
     * @param string $format
     * @param callable $renderer
     * @return $this
     */
    public function setRenderer($format, $renderer) {
        $this->renderers[$format] = $renderer;
        return $this;
    }

    /**
     * @param \watoki\curir\delivery\WebRequest $request
     * @return \watoki\curir\delivery\WebResponse
     */
    public function createResponse(WebRequest $request) {
        $formats = $request->getFormats();

        foreach ($formats as $accepted) {
            if (array_key_exists($accepted, $this->renderers)) {
                return $this->respondWith($accepted);
            }
        }

        return $this->respondWithDefault($formats);
    }

    private function respondWith($accepted) {
        $body = call_user_func($this->renderers[$accepted]);
        $response = new WebResponse($body);
        $response->getHeaders()->set(WebResponse::HEADER_CONTENT_TYPE, MimeTypes::getType($accepted));
        return $response;
    }

    /**
     * @param \watoki\collections\Liste $formats
     * @throws HttpError If no renderer for accepted format and no default renderer is set
     * @return WebResponse
     */
    private function respondWithDefault( Liste $formats) {
        if (!isset($this->renderers[''])) {
            throw new HttpError(WebResponse::STATUS_NOT_ACCEPTABLE, "Could not render the resource in an accepted format.",
                    "Invalid accepted types: " .
                    "[" . $formats->join(', ') . "] not supported by " .
                    "[" . implode(', ', array_keys($this->renderers)) . "]");
        }
        $response = new WebResponse(call_user_func($this->renderers['']));
        if (!$formats->isEmpty()) {
            $response->getHeaders()->set(WebResponse::HEADER_CONTENT_TYPE, MimeTypes::getType($formats->first()));
        }
        return $response;
    }
}