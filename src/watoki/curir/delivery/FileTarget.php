<?php
namespace watoki\curir\delivery;

use watoki\curir\protocol\MimeTypes;
use watoki\deli\Target;
use watoki\stores\file\raw\File;

class FileTarget extends Target {

    /** @var File */
    private $file;

    /** @var string */
    private $key;

    /** @var WebRequest */
    private $webRequest;

    function __construct(WebRequest $request, File $file, $key) {
        parent::__construct($request);

        $this->webRequest = $request;
        $this->file = $file;
        $this->key = $key;
    }

    /**
     * @return mixed
     */
    function respond() {
        $response = new WebResponse($this->file->content);

        if (strpos($this->key, '.') !== false) {
            $parts = explode('.', $this->key);
            $response->getHeaders()->set(WebResponse::HEADER_CONTENT_TYPE, MimeTypes::getType(end($parts)));
        } else if (!$this->webRequest->getFormats()->isEmpty()) {
            $response->getHeaders()->set(WebResponse::HEADER_CONTENT_TYPE, MimeTypes::getType($this->webRequest->getFormats()->first()));
        }

        return $response;
    }
}