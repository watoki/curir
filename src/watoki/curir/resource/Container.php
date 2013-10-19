<?php
namespace watoki\curir\resource;
 
use watoki\curir\http\Request;
use watoki\curir\Resource;
use watoki\curir\serialization\InflaterRepository;
use watoki\factory\Factory;

abstract class Container extends DynamicResource {

    /** @var \watoki\factory\Factory */
    private $factory;

    public function __construct($directory, $name, Container $parent = null, InflaterRepository $repository, Factory $factory) {
        parent::__construct($directory, $name, $parent, $repository);
        $this->factory = $factory;
    }

    public function respond(Request $request) {
        if ($request->getTarget()->isEmpty()) {
            return parent::respond($request);
        }

        $nextRequest = clone $request;
        $nextRequest->setTarget($request->getTarget()->copy());
        $child = $nextRequest->getTarget()->shift();

        $dynamicChild = $this->findDynamicChild($child);
        if ($dynamicChild) {
            return $dynamicChild->respond($nextRequest);
        }

        $staticChild = $this->findStaticChild($child . '.' . $request->getFormat());
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
    /**
     * @param string $child
     * @return null|\watoki\curir\Resource
     */
    private function findDynamicChild($child) {
        $class = $child . 'Resource';
        if (file_exists($this->getDirectory() . DIRECTORY_SEPARATOR . $class . '.php')) {
            return $this->factory->getInstance($class, array(
                'directory' => $this->getDirectory(),
                'name' => $child,
                'parent' => $this
            ));
        }
        return null;
    }

}
 