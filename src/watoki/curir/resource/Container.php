<?php
namespace watoki\curir\resource;

use watoki\curir\http\Request;
use watoki\curir\http\Url;
use watoki\curir\Resource;
use watoki\curir\serialization\InflaterRepository;
use watoki\factory\Factory;

abstract class Container extends DynamicResource {

    /** @var \watoki\factory\Factory */
    private $factory;

    public function __construct($directory, $name, Url $url, Container $parent = null,
                                InflaterRepository $repository, Factory $factory) {
        parent::__construct($directory, $name, $url, $parent, $repository);
        $this->factory = $factory;
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
        return $this->getDirectory() . DIRECTORY_SEPARATOR . $this->getName();
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

            $container = $this->factory->getInstance($parent->getName(), array(
                'directory' => dirname($parent->getFileName()),
                'name' => substr($parent->getShortName(), 0, -strlen('Resource')),
                'url' => $this->getUrl(),
                'parent' => $this->getParent()
            ));
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
        $file = $this->findFile($child);
        if ($file) {
            return $this->createChild(StaticResource::$CLASS, $child, basename($file));
        }
        return null;
    }

    /**
     * @param string $child
     * @return null|\watoki\curir\Resource
     */
    private function findDynamicChild($child) {
        $file = $this->findFile($child . 'Resource.php');
        $class = substr(basename($file), 0, -4);

        if ($file) {
            $fqn = $this->getNamespace() . '\\' . $this->getName() . '\\' . $class;
            return $this->createChild($fqn, $child, substr($class, 0, -strlen('Resource')));
        }
        return null;
    }

    /**
     * @param string $child
     * @return null|\watoki\curir\Resource
     */
    private function findStaticContainer($child) {
        $dir = $this->findFile($child);
        if ($dir && is_dir($dir)) {
            $name = basename($dir);
            $namespace = $this->getNamespace() . '\\' . $this->getName();
            return $this->createChild(StaticContainer::$CLASS, $child, $name, array(
                'namespace' => $namespace
            ));
        }
        return null;
    }

    private function findFile($fileName) {
        foreach (glob($this->getContainerDirectory() . DIRECTORY_SEPARATOR . '*') as $file) {
            if (strtolower(basename($file)) == strtolower($fileName)) {
                return $file;
            }
        }
        return null;
    }

    private function findPlaceholder($child) {
        foreach (glob($this->getContainerDirectory() . '/_*.php') as $file) {
            $class = substr(basename($file), 0, -4);
            $fqn = $this->getNamespace() . '\\' . $this->getName() . '\\' . $class;
            return $this->createChild($fqn, $child, substr($class, 0, -strlen('Resource')));
        }
        return null;
    }

    private function createChild($class, $child, $name, $args = array()) {
        $nextUrl = $this->getUrl()->copy();
        $nextUrl->getPath()->append($child);

        return $this->factory->getInstance($class, array_merge(array(
            'directory' => $this->getContainerDirectory(),
            'url' => $nextUrl,
            'name' => $name,
            'parent' => $this
        ), $args));
    }

    protected function getNamespace() {
        $reflection = new \ReflectionClass($this);
        return $reflection->getNamespaceName();
    }

}
 