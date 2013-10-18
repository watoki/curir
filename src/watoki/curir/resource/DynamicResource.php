<?php
namespace watoki\curir\resource;

use rtens\mockster\Method;
use watoki\collections\Map;
use watoki\curir\http\Request;
use watoki\curir\Resource;
use watoki\curir\Responder;
use watoki\curir\serialization\ArrayInflater;
use watoki\curir\serialization\BooleanInflater;
use watoki\curir\serialization\DateTimeInflater;
use watoki\curir\serialization\FloatInflater;
use watoki\curir\serialization\InflaterRepository;
use watoki\curir\serialization\IntegerInflater;
use watoki\curir\serialization\StringInflater;
use watoki\factory\ClassResolver;

/**
 * The Response of a DynamicResource is rendered by translating the Request into a method invocation.
 */
abstract class DynamicResource extends Resource {

    /** @var ClassResolver */
    private $resolver;

    public function __construct($directory, $name, Container $parent = null) {
        parent::__construct($directory, $name, $parent);
        $this->resolver = new ClassResolver(new \ReflectionClass($this));
    }

    public function respond(Request $request) {
        $responder = $this->invokeMethod($request->getMethod(), $request->getParameters());
        return $responder->createResponse($this, $request);
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

                $args[] = $this->inflate($value, $type);
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
        $repository = new InflaterRepository();
        $repository->setInflater('boolean', new BooleanInflater());
        $repository->setInflater('integer', new IntegerInflater());
        $repository->setInflater('float', new FloatInflater());
        $repository->setInflater('array', new ArrayInflater());
        $repository->setInflater('string', new StringInflater());
        $repository->setInflater('DateTime', new DateTimeInflater());
        return $repository->getInflater($type)->inflate($value);
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

        return "string";
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

        $resolved = $this->resolver->resolve($hint);
        return $resolved ? : $hint;
    }

}
 