<?php
namespace watoki\curir;

use watoki\curir\delivery\WebRequest;
use watoki\curir\delivery\WebResponse;

interface Responder {

    /**
     * @param WebRequest $request
     * @return WebResponse
     */
    public function createResponse(WebRequest $request);
}