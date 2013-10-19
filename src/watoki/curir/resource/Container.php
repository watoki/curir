<?php
namespace watoki\curir\resource;
 
use watoki\curir\http\Request;
use watoki\curir\Resource;

abstract class Container extends DynamicResource {

    public function respond(Request $request) {
        $nextRequest = clone $request;
        $nextRequest->setTarget($request->getTarget()->copy());
        $child = $nextRequest->getTarget()->shift();

        throw new \Exception("Resource [$child] not found in container [" . get_class($this). "]");
    }

}
 