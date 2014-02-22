<?php
namespace watoki\curir\http\error;

use Exception;
use watoki\curir\http\Response;

class HttpError extends \Exception {

    private $status;

    public function __construct($status = Response::STATUS_SERVER_ERROR, $message = "", $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getStatus() {
        return $this->status;
    }

} 