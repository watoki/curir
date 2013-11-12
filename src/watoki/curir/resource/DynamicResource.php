<?php
namespace watoki\curir\resource;

use watoki\collections\Map;
use watoki\curir\http\Request;
use watoki\curir\Resource;
use watoki\curir\Responder;
use watoki\factory\Factory;
use watoki\factory\filters\DefaultFilterFactory;
use watoki\factory\Injector;

/**
 * The Response of a DynamicResource is rendered by translating the Request into a method invocation.
 */
abstract class DynamicResource extends Resource {

    /** @var DefaultFilterFactory <- */
    public $filters;

    /** @var Factory <- */
    public $factory;

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
        $request->getParameters()->set($key, $this->getUrl()->getPath()->last());
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
        $injector = new Injector($this->factory);
        return $injector->injectMethodArguments($method, $parameters->toArray(), $this->filters);
    }

    private function buildMethodName($method) {
        return 'do' . ucfirst($method);
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
 