<?php
namespace watoki\curir\resource;

use watoki\collections\Map;
use watoki\curir\http\error\HttpError;
use watoki\curir\http\Request;
use watoki\curir\http\Response;
use watoki\curir\Resource;
use watoki\curir\Responder;
use watoki\curir\responder\DefaultResponder;
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

        $params = $request->getParameters();
        if (!$params->has('request')) {
            $params->set('request', $request);
        }

        $responder = $this->invokeMethod($request->getMethod(), $params);
        if (!$responder instanceof Responder) {
            $responder = new DefaultResponder((string) $responder);
        }
        return $responder->createResponse($request);
    }

    protected function setPlaceholderKey(Request $request) {
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
     * @throws \watoki\curir\http\error\HttpError
     * @return Responder
     */
    private function invokeMethod($method, Map $parameters) {
        $methodName = $this->buildMethodName($method);
        try {
            $reflection = new \ReflectionMethod($this, $methodName);
        } catch (\ReflectionException $e) {
            throw new HttpError(Response::STATUS_METHOD_NOT_ALLOWED, 'Method ' . strtoupper($method) . ' is not allowed here.',
                "Resource [" . get_class($this) . "] aka [" . $this->getUrl() . "] has no method [$methodName]");
        }

        return $reflection->invokeArgs($this, $this->collectArguments($parameters, $reflection));
    }

    protected function collectArguments(Map $parameters, \ReflectionMethod $method) {
        $injector = new Injector($this->factory);
        try {
            return $injector->injectMethodArguments($method, $parameters->toArray(), $this->filters);
        } catch (\Exception $e) {
            throw new HttpError(Response::STATUS_BAD_REQUEST, "A request parameter is invalid or missing.", $e->getMessage());
        }
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
 
