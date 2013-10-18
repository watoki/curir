<?php
namespace watoki\curir\resource;

use rtens\mockster\Method;
use watoki\collections\Map;
use watoki\curir\http\Request;
use watoki\curir\Resource;
use watoki\curir\Responder;
use watoki\curir\serialization\DateTimeInflater;
use watoki\curir\serialization\InflaterRepository;
use watoki\curir\serialization\StringInflater;

/**
 * The Response of a DynamicResource is rendered by translating the Request into a method invocation.
 */
abstract class DynamicResource extends Resource {

    public function __construct($directory, $name, Container $parent = null) {
        parent::__construct($directory, $name, $parent);
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
        $repository->setInflater('string', new StringInflater());
        $repository->setInflater('DateTime', new DateTimeInflater());
        return $repository->getInflater($type)->inflate($value);
    }

    private function findTypeHint(\ReflectionMethod $method, \ReflectionParameter $param) {
        return $param->getClass() ? $param->getClass()->getName() : "string";
    }

}
 