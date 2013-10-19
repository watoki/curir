<?php
namespace watoki\curir\resource;

use watoki\curir\http\Request;
use watoki\curir\Resource;
use watoki\curir\serialization\InflaterRepository;
use watoki\factory\Factory;

abstract class Container extends DynamicResource {

    /** @var \watoki\factory\Factory */
    private $factory;

    private $realName;

    public function __construct($directory, $name, Container $parent = null, InflaterRepository $repository, Factory $factory) {
        parent::__construct($directory, $name, $parent, $repository);
        $this->factory = $factory;
        $this->realName = $name;
    }

    public function respond(Request $request) {
        if ($request->getTarget()->isEmpty()) {
            return parent::respond($request);
        }

        $nextRequest = clone $request;
        $nextRequest->setTarget($request->getTarget()->copy());
        $child = $nextRequest->getTarget()->shift();

        $found = $this->findInSuperClasses($child, $request->getFormat());
        if ($found) {
            return $found->respond($nextRequest);
        }

        throw new \Exception("Resource [$child] not found in container [" . get_class($this) . "]");
    }

    public function getContainerDirectory() {
        return $this->getDirectory() . DIRECTORY_SEPARATOR . $this->realName;
    }

    private function findInSuperClasses($child, $format) {
        $container = $this;
        while (true) {
            $found = $container->findChild($child, $format);
            if ($found) {
                return $found;
            }

            $reflection = new \ReflectionClass($container);
            $parent = $reflection->getParentClass();

            if ($parent->isAbstract()) {
                return null;
            }

            /** @var Container $container */
            $container = $this->factory->getInstance($parent->getName(), array(
                'directory' => dirname($parent->getFileName()),
                'name' => $this->getName(),
                'parent' => $this->getParent()
            ));
            $container->realName = substr($parent->getShortName(), 0, -strlen('Resource'));
        }
        return null;
    }

    private function findChild($child, $format) {
        $dynamicChild = $this->findDynamicChild($child);
        if ($dynamicChild) {
            return $dynamicChild;
        }

        $staticChild = $this->findStaticChild($child . '.' . $format);
        if ($staticChild) {
            return $staticChild;
        }

        $container = $this->findStaticContainer($child);
        if ($container) {
            return $container;
        }

        $placeholder = $this->findPlaceholder($child);
        if ($placeholder) {
            return $placeholder;
        }

        return null;
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
            $fqn = $this->getNamespace() . '\\' . $this->realName . '\\' . $class;
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
        $dir = $this->getContainerDirectory() . DIRECTORY_SEPARATOR . $child;
        if (file_exists($dir) && is_dir($dir)) {
            $namespace = $this->getNamespace() . '\\' . $this->realName;
            /** @var Container $container */
            $container = $this->factory->getInstance(StaticContainer::$CLASS, array(
                'namespace' => $namespace,
                'directory' => $this->getContainerDirectory(),
                'name' => $child,
                'parent' => $this
            ));
            $container->realName = $child;
            return $container;
        }
        return null;
    }

    private function findPlaceholder($child) {
        foreach (glob($this->getContainerDirectory() . '/_*.php') as $file) {
            $class = substr(basename($file), 0, -4);
            $fqn = $this->getNamespace() . '\\' . $this->realName . '\\' . $class;

            return $this->factory->getInstance($fqn, array(
                'directory' => $this->getContainerDirectory(),
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
 