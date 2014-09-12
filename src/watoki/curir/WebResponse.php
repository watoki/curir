<?php
namespace watoki\curir;

use watoki\deli\Response;

class WebResponse extends Response {

    const HEADER_CONTENT_TYPE = 'Content-Type';
    const HEADER_LOCATION = 'Location';

    const STATUS_OK = '200 OK';
    const STATUS_MOVED_PERMANENTLY = '301 Moved Permanently';
    const STATUS_SEE_OTHER = '303 See Other';
    const STATUS_BAD_REQUEST = '400 Bad Request';
    const STATUS_UNAUTHORIZED = '401 Unauthorized';
    const STATUS_FORBIDDEN = '403 Forbidden';
    const STATUS_NOT_FOUND = '404 Not Found';
    const STATUS_METHOD_NOT_ALLOWED = '405 Method Not Allowed';
    const STATUS_NOT_ACCEPTABLE = '406 Not Acceptable';
    const STATUS_SERVER_ERROR = '500 Internal Server Error';
    const STATUS_NOT_IMPLEMENTED = '501 Not Implemented';

} 