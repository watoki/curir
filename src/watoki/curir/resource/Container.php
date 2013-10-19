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

        $container = $this->findStaticContainer($child);
        if ($container) {
            return $container->respond($nextRequest);
        }

        throw new \Exception("Resource [$child] not found in container [" . get_class($this). "]");
    }

    public function getContainerDirectory() {
        return $this->getDirectory()  . DIRECTORY_SEPARATOR . $this->getName();
    }

    /**
     * @param string $child
     * @return null|\watoki\curir\Resource
     */
    private function findStaticChild($child) {
        if (file_exists($this->getContainerDirectory() . DIRECTORY_SEPARATOR . $child)) {
            return $this->factory->getInstance(StaticResource::$CLASS, array(
                'directory' => $this->getContainerDirectory(),
                'name' => $child,
                'parent' => $this
            ));
        }
        return null;
    }

    /**
     * @param string $child
     * @return null|\watoki\curir\Resource
     */
    private function findDynamicChild($child) {
        $class = $child . 'Resource';
        $fileName = $this->getContainerDirectory() . DIRECTORY_SEPARATOR . $class . '.php';

        if (file_exists($fileName)) {
            $fqn = $this->getNamespace() . '\\' . $this->getName() . '\\' . $class;
            return $this->factory->getInstance($fqn, array(
                'directory' => $this->getContainerDirectory(),
                'name' => $child,
                'parent' => $this
            ));
        }
        return null;
    }

    /**
     * @param string $child
     * @return null|\watoki\curir\Resource
     */
    private function findStaticContainer($child) {
        $dir = $this->getContainerDirectory();
        if (file_exists($dir) && is_dir($dir)) {
            $namespace = $this->getNamespace() . '\\' . $this->getName();
            return $this->factory->getInstance(StaticContainer::$CLASS, array(
                'namespace' => $namespace,
                'directory' => $dir,
                'name' => $child,
                'parent' => $this
            ));
        }
        return null;
    }

    protected function getNamespace() {
        $reflection = new \ReflectionClass($this);
        return $reflection->getNamespaceName();
    }

}
 