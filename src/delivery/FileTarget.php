<?php
namespace watoki\curir\delivery;

use watoki\curir\protocol\MimeTypes;
use watoki\deli\Target;

class FileTarget extends Target {

    /** @var string */
    private $content;

    /** @var string */
    private $key;

    /** @var WebRequest */
    private $webRequest;

    function __construct(WebRequest $request, $content, $key) {
        parent::__construct($request);

        $this->webRequest = $request;
        $this->content = $content;
        $this->key = $key;
    }

    /**
     * @return mixed
     */
    function respond() {
        $response = new WebResponse($this->content);

        if (strpos($this->key, '.') !== false) {
            $parts = explode('.', $this->key);
            $response->getHeaders()->set(WebResponse::HEADER_CONTENT_TYPE, MimeTypes::getType(end($parts)));
        } else if (!$this->webRequest->getFormats()->isEmpty()) {
            $response->getHeaders()->set(WebResponse::HEADER_CONTENT_TYPE, MimeTypes::getType($this->webRequest->getFormats()->first()));
        }

        return $response;
    }
}