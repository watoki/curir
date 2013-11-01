<?php
namespace watoki\curir\resource;

use rtens\mockster\Method;
use watoki\collections\Map;
use watoki\curir\http\Request;
use watoki\curir\Resource;
use watoki\curir\Responder;
use watoki\curir\serialization\InflaterRepository;
use watoki\factory\ClassResolver;

/**
 * The Response of a DynamicResource is rendered by translating the Request into a method invocation.
 */
abstract class DynamicResource extends Resource {

    /** @var InflaterRepository */
    private $repository;

    public function __construct($name, Resource $parent = null, InflaterRepository $repository) {
        parent::__construct($name, $parent);
        $this->repository = $repository;
    }

    public function respond(Request $request) {
        $this->setPlaceholderKey($request);
        $responder = $this->invokeMethod($request->getMethod(), $request->getParameters());
        return $responder->createResponse($this, $request);
    }

    private function setPlaceholderKey(Request $request) {
        $key = $this->getPlaceholderKey();
        if (!$key || $request->getParameters()->has($key)) {
            return;
        }
        $request->getParameters()->set($key, $this->getName());
    }

    /**
     * @return null|string If string is returned, the Resource's name is set as the corresponding Request parameter (if not set already)
     */
    protected function getPlaceholderKey() {
        return null;
    }

    /**
     * @param string $method
     * @param Map $parameters
     * @return Responder
     */
    private function invokeMethod($method, Map $parameters) {
        $reflection = new \ReflectionMethod($this, $this->buildMethodName($method));
        return $reflection->invokeArgs($this, $this->collectArguments($parameters, $reflection));
    }

    protected function collectArguments(Map $parameters, \ReflectionMethod $method) {
        $args = array();
        foreach ($method->getParameters() as $param) {
            if ($parameters->has($param->getName())) {
                $type = $this->findTypeHint($method, $param);
                $value = $parameters->get($param->getName());

                if ($type) {
                    $args[] = $this->inflate($value, $type);
                } else {
                    $args[] = $value;
                }
            } else if ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                $class = get_class($this);
                throw new \Exception(
                    "Invalid request: Missing parameter [{$param->getName()}] for method [{$method->getName()}] in component [$class]");
            }
        }
        return $args;
    }

    private function buildMethodName($method) {
        return 'do' . ucfirst($method);
    }

    private function inflate($value, $type) {
        try {
            return $this->repository->getInflater($type)->inflate($value);
        } catch (\Exception $e) {
            return $value;
        }
    }

    private function findTypeHint(\ReflectionMethod $method, \ReflectionParameter $param) {
        if ($param->getClass()) {
            return $param->getClass()->getName();
        }

        if ($method->getDocComment()) {
            $matches = array();
            $pattern = '/@param\s+(\S+)\s+\$' . $param->getName() . '/';
            $found = preg_match($pattern, $method->getDocComment(), $matches);

            if ($found) {
                return $this->resolveType($matches[1]);
            }
        }

        return null;
    }

    private function resolveType($hint) {
        switch ($hint) {
            case 'array':
                return 'array';
            case 'int':
            case 'integer':
                return 'integer';
            case 'bool':
            case 'boolean':
                return 'boolean';
            case 'float':
            case 'string':
                return $hint;
        }

        $resolver = new ClassResolver(new \ReflectionClass($this));
        return $resolver->resolve($hint) ? : $hint;
    }

    public function getResourceDirectory() {
        $reflection = new \ReflectionClass($this);
        return dirname($reflection->getFileName());
    }

    public function getResourceName() {
        $reflection = new \ReflectionClass($this);
        return substr(basename($reflection->getShortName()), 0, -strlen('Resource'));
    }

    public function getDefaultFormat() {
        return 'html';
    }

}
 