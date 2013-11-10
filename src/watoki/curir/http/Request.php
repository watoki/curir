<?php
namespace watoki\curir\http;

use watoki\collections\Map;

class Request {

    const DEFAULT_RESOURCE_NAME = 'index.html';

    public static $CLASS = __CLASS__;

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

    /** @var Url */
    private $target;

    /** @var string|null */
    private $format;

    /** @var string Request::METHOD_X */
    private $method;

    /** @var Map Parameter keys and values parsed from query string or body */
    private $parameters;

    /** @var Map Indexed by self::HEADER_X */
    private $headers;

    /** @var string */
    private $body;

    function __construct(Path $target, $format = null, $method = Request::METHOD_GET, Map $parameters = null, Map $headers = null, $body = '') {
        $this->target = $target;
        $this->format = $format;
        $this->method = $method;
        $this->parameters = $parameters ?: new Map();
        $this->headers = $headers ?: new Map();
        $this->body = $body;
    }

    /**
     * @return string
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * @return \watoki\collections\Map Indexed by self::HEADER_*
     */
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * @return string Request::METHOD_*
     */
    public function getMethod() {
        return $this->method;
    }

    /**
     * @return \watoki\collections\Map
     */
    public function getParameters() {
        return $this->parameters;
    }

    /**
     * @return Path
     */
    public function getTarget() {
        return $this->target;
    }

    /**
     * @param \watoki\curir\http\Path $target
     */
    public function setTarget($target) {
        $this->target = $target;
    }

    /**
     * @return string|null
     */
    public function getFormat() {
        return $this->format;
    }

    /**
     * @param string|null $format
     */
    public function setFormat($format) {
        $this->format = $format;
    }

    /**
     * @param string $method From Request::METHOD_*
     */
    public function setMethod($method) {
        $this->method = $method;
    }

}
