<?php
namespace watoki\curir\resource;
 
use watoki\curir\http\Request;
use watoki\curir\Resource;

abstract class Container extends DynamicResource {

    public function respond(Request $request) {
        if ($request->getTarget()->isEmpty()) {
            return parent::respond($request);
        }

        $nextRequest = clone $request;
        $nextRequest->setTarget($request->getTarget()->copy());
        $child = $nextRequest->getTarget()->shift();

        $staticChild = $this->findStaticChild($child);
        if ($staticChild) {
            return $staticChild->respond($nextRequest);
        }

        throw new \Exception("Resource [$child] not found in container [" . get_class($this). "]");
    }

    /**
     * @param string $child
     * @return null|\watoki\curir\Resource
     */
    private function findStaticChild($child) {
        if (file_exists($this->getDirectory() . DIRECTORY_SEPARATOR . $child)) {
            return new StaticResource($this->getDirectory(), $child, $this);
        }
        return null;
    }

}
 