<?php
namespace watoki\webco\controller;
 
use watoki\collections\Map;
use watoki\tempan\HtmlParser;
use watoki\webco\Controller;
use watoki\webco\Request;
use watoki\webco\Response;
use watoki\webco\Url;
use watoki\webco\controller\sub\HtmlSubComponent;

abstract class Component extends Controller {

    public static $CLASS = __CLASS__;

    /**
     * @param array|object $model
     * @param string $template
     * @return string The rendered template
     */
    abstract protected function doRender($model, $template);

    /**
     * @param Request $request
     * @throws \Exception
     * @return Response
     */
    public function respond(Request $request) {
        $response = $this->getResponse();
        if ($request->getParameters()->has('.')) {
            $this->restoreState($request->getParameters()->get('.'));
        }
        $action = $this->makeMethodName($this->getActionName($request));
        $response->setBody($this->renderAction($action, $request->getParameters()));
        return $response;
    }

    /**
     * @param $action
     * @param $parameters
     * @return null|string
     */
    protected function renderAction($action, $parameters) {
        $model = $this->invokeAction($action, $parameters);
        $body = ($model !== null ? $this->render($model) : null);
        return $this->mergeSubHeaders($body);
    }

    /**
     * @param $action
     * @param Map $parameters
     * @throws \Exception
     * @return mixed
     */
    protected function invokeAction($action, Map $parameters) {
        if (!method_exists($this, $action)) {
            throw new \Exception('Method [' . $action . '] not found in controller [' . get_class($this) . '].');
        }

        $method = new \ReflectionMethod($this, $action);
        $args = $this->assembleArguments($parameters, $method);

        return $method->invokeArgs($this, $args);
    }

    /**
     * @param Request $request
     * @return string
     */
    protected function getActionName(Request $request) {
        return $request->getParameters()->has('action')
                ? $request->getParameters()->get('action')
                : strtolower($request->getMethod());
    }

    /**
     * @param \watoki\collections\Map $parameters
     * @param \ReflectionMethod $method
     * @throws \Exception If a parameter is missing
     * @return array
     */
    private function assembleArguments(Map $parameters, \ReflectionMethod $method) {
        $args = array();
        foreach ($method->getParameters() as $param) {
            if ($parameters->has($param->getName())) {
                $args[] = $parameters->get($param->getName());
            } else if (!$param->isOptional()) {
                throw new \Exception('Invalid request: Missing Parameter [' . $param->getName() . ']');
            } else {
                $args[] = $param->getDefaultValue();
            }
        }
        return $args;
    }

    /**
     * @param $actionName
     * @return string
     */
    public function makeMethodName($actionName) {
        return 'do' . ucfirst($actionName);
    }

    protected function render($model) {
        $templateFile = $this->getTemplateFile();
        if (!file_exists($templateFile)) {
            return json_encode($model);
        }

        $template = file_get_contents($templateFile);
        return $this->doRender($model, $template);
    }

    protected function getTemplateFile() {
        return $this->getDirectory() . '/' . $this->getTemplateFileName();
    }

    protected function getTemplateFileName() {
        $classReflection = new \ReflectionClass($this);
        return strtolower($classReflection->getShortName()) . '.html';
    }

    public function getBaseRoute() {
        return substr($this->route, 0, strrpos($this->route, '/') + 1);
    }

    private function mergeSubHeaders($body) {
        $parser = new HtmlParser($body);

        foreach ($this as $member) {
            if ($member instanceof HtmlSubComponent) {
                if (!isset($head)) {
                    $head = $parser->getRoot()->firstChild;
                    if ($head->nodeName != 'head') {
                        $body = $head;
                        $head = $parser->getDocument()->createElement('head');
                        $parser->getRoot()->insertBefore($head, $body);
                    }
                }

                foreach ($member->getHeadElements('link') as $element) {
                    $head->appendChild($parser->getDocument()->importNode($element, true));
                }
            }
        }

        return isset($parser) ? $parser->toString() : $body;
    }

    /**
     * @return \watoki\collections\Map|SubComponent[]
     */
    public function getSubComponents() {
        $subs = new Map();
        foreach ($this as $name => $member) {
            if ($member instanceof SubComponent) {
                $subs->set($name, $member);
            }
        }
        return $subs;
    }

    public function getState() {
        $params = new Map();
        foreach ($this->getSubComponents() as $name => $sub) {
            $params->set($name, $sub->getNonDefaultParameters());
        }
        return $params;
    }

    private function restoreState(Map $state) {
        $subComponents = $this->getSubComponents();
        foreach ($state as $name => $subState) {
            /** @var $subComponent SubComponent */
            $subComponent = $subComponents->get($name);
            $subComponent->getParameters()->merge($subState);
        }
    }

}
