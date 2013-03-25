<?php
namespace watoki\webco;
 
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
    const STATUS_UNSUPPORTED_MEDIA_TYPE = '415 Unsupported Media Type';
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

    function __construct() {
        $this->headers = new Map();
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

    public function flush() {
        foreach ($this->getHeaders() as $header => $value) {
            header($header . ': ' . $value);
        }
        echo $this->getBody();
    }

}
