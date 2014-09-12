<?php
namespace watoki\curir;

use watoki\collections\Liste;
use watoki\collections\Map;
use watoki\deli\Path;
use watoki\deli\Request;

class WebRequest extends Request {

    const METHOD_GET = 'get';
    const METHOD_POST = 'post';
    const METHOD_PUT = 'put';
    const METHOD_PATCH = 'patch';
    const METHOD_DELETE = 'delete';
    const METHOD_HEAD = 'head';
    const METHOD_OPTIONS = 'options';
    const METHOD_TRACE = 'trace';

    const HEADER_ACCEPT = 'Accept';
    const HEADER_ACCEPT_CHARSET = 'Accept-Charset';
    const HEADER_ACCEPT_ENCODING = 'Accept-Encoding';
    const HEADER_ACCEPT_LANGUAGE = 'Accept-Language';
    const HEADER_CACHE_CONTROL = 'Cache-Control';
    const HEADER_CONNECTION = 'Connection';
    const HEADER_PRAGMA = 'Pragma';
    const HEADER_USER_AGENT = 'User-Agent';
    const HEADER_CONTENT_TYPE = 'Content-Type';

    /** @var Liste */
    private $formats;

    /** @var Map */
    private $headers;

    function __construct(Url $context, Path $target, $method = null, Map $arguments = null, Liste $formats = null,
                         Map $headers = null) {
        parent::__construct($context, $target, $method, $arguments);
        $this->formats = $formats ? : new Liste();
        $this->headers = $headers ? : new Map();
    }

    public function getFormats() {
        return $this->formats;
    }

    public function getHeaders() {
        return $this->headers;
    }

    /**
     * @return Url
     */
    public function getContext() {
        return parent::getContext();
    }

    public function copy() {
        return new WebRequest(
            $this->getContext()->copy(),
            $this->getTarget()->copy(),
            $this->getMethod(),
            $this->getArguments()->copy(),
            $this->formats->copy(),
            $this->headers->copy()
        );
    }
}