<?php
namespace watoki\curir\resource;
use watoki\curir\http\Request;
use watoki\curir\Resource;
use watoki\curir\http\Response;

/**
 * A StaticResource is the implicit Resource associated with a static file.
 */
class StaticResource extends Resource {

    /**
     * @param Request $request
     * @return Response
     */
    public function respond(Request $request) {
        return new Response();
    }
}
 