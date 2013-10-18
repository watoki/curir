<?php
namespace watoki\curir;

use watoki\curir\http\Request;

abstract class Responder {

    /**
     * @param \watoki\curir\Resource $resource
     * @param \watoki\curir\http\Request $request
     * @return \watoki\curir\http\Response
     */
    abstract public function createResponse(Resource $resource, Request $request);
}