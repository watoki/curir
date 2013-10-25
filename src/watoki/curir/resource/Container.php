<?php
namespace watoki\curir\resource;

use watoki\curir\http\Request;
use watoki\curir\Resource;
use watoki\curir\serialization\InflaterRepository;
use watoki\factory\Factory;

abstract class Container extends DynamicResource {

    const PLACEHOLDER_PREFIX = 'xx';

    /** @var \watoki\factory\Factory */
    private $factory;

    public function __construct($name, Container $parent = null, InflaterRepository $repository, Factory $factory) {
        parent::__construct($name, $parent, $repository);
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

        throw new \Exception("Resource [$child] not found in container [" . get_class($this) . "] aka [" . $this->getName() . "]");
    }

    public function getContainerDirectory() {
        return $this->getResourceDirectory() . DIRECTORY_SEPARATOR . lcfirst($this->getResourceName());
    }

    public function getContainerNamespace() {
        return $this->getResourceNamespace() . '\\' . lcfirst($this->getResourceName());
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
                'name' => $this->getName(),
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
            return $this->factory->getInstance(StaticResource::$CLASS, array(
                'name' => $child,
                'parent' => $this,
                'file' => $file
            ));
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
            require_once($file);
            $fqn = $this->getContainerNamespace() . '\\' . $class;
            return $this->factory->getInstance($fqn, array(
                'name' => $child,
                'parent' => $this,
            ));
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
            return $this->factory->getInstance(StaticContainer::$CLASS, array(
                'name' => $child,
                'parent' => $this,
                'directory' => $dir,
                'namespace' => $this->getContainerNamespace()
            ));
        }
        return null;
    }

    private function findPlaceholder($child) {
        foreach (glob($this->getContainerDirectory() . '/' . self::PLACEHOLDER_PREFIX . '*') as $file) {
            if (substr(basename($file), -4) == '.php') {
                require_once($file);
                $class = substr(basename($file), 0, -4);
                $fqn = $this->getContainerNamespace() . '\\' . $class;
                return $this->factory->getInstance($fqn, array(
                    'name' => $child,
                    'parent' => $this,
                ));
            } else if (is_dir($file)) {
                return $this->factory->getInstance(StaticContainer::$CLASS, array(
                    'name' => $child,
                    'parent' => $this,
                    'directory' => $file,
                    'namespace' => $this->getContainerNamespace()
                ));
            } else {
                return $this->factory->getInstance(StaticResource::$CLASS, array(
                    'name' => $child,
                    'parent' => $this,
                    'file' => $file
                ));
            }
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

    protected function getResourceNamespace() {
        $reflection = new \ReflectionClass($this);
        return $reflection->getNamespaceName();
    }

}
 