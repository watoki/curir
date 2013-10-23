<?php
namespace watoki\curir;

use watoki\curir\http\Request;
use watoki\curir\resource\DynamicResource;

abstract class Responder {

    /**
     * @param resource\DynamicResource $resource
     * @param \watoki\curir\http\Request $request
     * @return \watoki\curir\http\Response
     */
    abstract public function createResponse(DynamicResource $resource, Request $request);
}