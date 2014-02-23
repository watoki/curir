<?php
namespace watoki\curir\http;
 
use watoki\collections\Map;

class Response {

    const HEADER_CONTENT_TYPE = 'Content-Type';
    const HEADER_LOCATION = 'Location';

    const STATUS_OK = '200 OK';
    const STATUS_SEE_OTHER = '303 See Other';
    const STATUS_MOVED_PERMANENTLY = '301 Moved Permanently';
    const STATUS_BAD_REQUEST = '400 Bad Request';
    const STATUS_FORBIDDEN = '403 Forbidden';
    const STATUS_NOT_FOUND = '404 Not Found';
    const STATUS_METHOD_NOT_ALLOWED = '405 Method Not Allowed';
    const STATUS_NOT_ACCEPTABLE = '406 Not Acceptable';
    const STATUS_SERVER_ERROR = '500 Internal Server Error';
    const STATUS_NOT_IMPLEMENTED = '501 Not Implemented';

    public static $CLASS = __CLASS__;

    /**
     * @var Map
     */
    private $headers;

    /**
     * @var string
     */
    private $body;

    /** @var null|string */
    private $status = self::STATUS_OK;

    function __construct($body = null) {
        $this->headers = new Map();
        $this->body = $body;
    }

    /**
     * @param string $body
     */
    public function setBody($body) {
        $this->body = $body;
    }

    /**
     * @return string
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * @return \watoki\collections\Map
     */
    public function getHeaders() {
        return $this->headers;
    }

    public function setStatus($status) {
        $this->status = $status;
    }

    public function getStatus() {
        return $this->status;
    }

    public function flush() {
        if ($this->status) {
            header('HTTP/1.0 ' . $this->status);
        }
        foreach ($this->getHeaders() as $header => $value) {
            header($header . ': ' . $value);
        }
        echo $this->getBody();
    }

}
