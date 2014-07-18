<?php
namespace watoki\curir\http\error;

use Exception;
use watoki\curir\http\Response;

class HttpError extends \Exception {

    private $status;

    private $userMessage;

    public function __construct($status = Response::STATUS_SERVER_ERROR, $userMessage = "", $message = "",
                                $code = 0, Exception $previous = null) {
        parent::__construct($message ?: $userMessage, $code, $previous);
        $this->status = $status;
        $this->userMessage = $userMessage;
    }

    /**
     * @return string
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getUserMessage() {
        return $this->userMessage;
    }

} 