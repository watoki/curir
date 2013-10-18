<?php
namespace watoki\curir\resource;

use watoki\collections\Map;
use watoki\curir\http\Request;
use watoki\curir\renderer\RendererFactory;
use watoki\curir\Resource;
use watoki\curir\http\Response;

/**
 * The Response of a DynamicResource is rendered by translating the Request into a method invocation.
 */
abstract class DynamicResource extends Resource {

    /** @var \watoki\curir\renderer\RendererFactory */
    private $rendererFactory;

    public function __construct($directory, $name, Container $parent = null, RendererFactory $rendererFactory) {
        parent::__construct($directory, $name, $parent);
        $this->rendererFactory = $rendererFactory;
    }

    public function respond(Request $request) {
        return new Response($this->renderBody($request));
    }

    private function renderBody(Request $request) {
        $model = $this->invokeMethod($request->getMethod(), $request->getParameters());
        $renderer = $this->rendererFactory->getRenderer($request->getFormat());
        return $renderer->render($this->getTemplate($request), $model);
    }

    private function invokeMethod($method, Map $parameters) {
        $reflection = new \ReflectionMethod($this, $this->buildMethodName($method));
        return $reflection->invokeArgs($this, $this->collectArguments($parameters, $reflection));
    }

    protected function collectArguments(Map $parameters, \ReflectionMethod $method) {
        $args = array();
        foreach ($method->getParameters() as $param) {
            if ($parameters->has($param->getName())) {
                $args[] = $parameters->get($param->getName());
            } else if (!$param->isOptional()) {
                $class = get_class($this);
                throw new \Exception(
                    "Invalid request: Missing Parameter [{$param->getName()}] for method [{$method->getName()}] in component [$class]");
            } else {
                $args[] = $param->getDefaultValue();
            }
        }
        return $args;
    }

    private function buildMethodName($method) {
        return 'do' . ucfirst($method);
    }

    protected function getTemplate(Request $request) {
        return '';
    }

}
 