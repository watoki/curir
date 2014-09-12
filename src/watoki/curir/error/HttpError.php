<?php
namespace watoki\curir\error;

use watoki\curir\WebResponse;

class HttpError extends \Exception {

    public static $CLASS = __CLASS__;

    private $status;

    private $userMessage;

    public function __construct($status = WebResponse::STATUS_SERVER_ERROR, $userMessage = "", $message = "",
                                $code = 0, \Exception $previous = null) {
        parent::__construct($message ? : $userMessage, $code, $previous);
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