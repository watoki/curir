<?php
namespace watoki\curir;

use watoki\factory\Factory;

interface Responder {

    /**
     * @param WebRequest $request
     * @param \watoki\curir\Resource $resource
     * @param Factory $factory
     * @return WebResponse
     */
    public function createResponse(WebRequest $request, Resource $resource, Factory $factory);
}