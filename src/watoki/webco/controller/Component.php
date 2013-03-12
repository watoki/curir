<?php
namespace watoki\webco\controller;
 
use watoki\collections\Map;
use watoki\tempan\HtmlParser;
use watoki\webco\Controller;
use watoki\webco\Request;
use watoki\webco\Response;
use watoki\webco\Url;
use watoki\webco\controller\sub\HtmlSubComponent;
use watoki\webco\controller\sub\PlainSubComponent;
use watoki\webco\controller\sub\RenderedSubComponent;

abstract class Component extends Controller {

    public static $CLASS = __CLASS__;

    const PARAMETER_PRIMARY_REQUEST = '.';
    const PARAMETER_STATE = '.';
    const PARAMETER_TARGET = '~';

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

        if ($request->getParameters()->has(self::PARAMETER_STATE)) {
            /** @var $state Map */
            $state = $request->getParameters()->remove(self::PARAMETER_STATE);
            $this->restoreState($state);

            if ($state->has(self::PARAMETER_PRIMARY_REQUEST)) {
                $primarySubName = $state->remove(self::PARAMETER_PRIMARY_REQUEST);
                $primarySub = $this->renderSubComponent($primarySubName, $request->getMethod());
                if ($primarySub->getResponse()->getHeaders()->has(Response::HEADER_LOCATION)) {
                    return $this->bubbleUpRedirect($primarySubName, $primarySub->getResponse(), $response, $this->getState(), $request->getParameters()->copy());
                }
                $request->setMethod(Request::METHOD_GET);
            }
        }

        $action = $this->makeMethodName($this->getActionName($request));
        $response->setBody($this->renderAction($action, $request->getParameters()));

        return $this->collectSubRedirects($response, $request->getParameters());
    }

    /**
     * @param $action
     * @param $parameters
     * @return null|string
     */
    protected function renderAction($action, $parameters) {
        $model = $this->invokeAction($action, $parameters);
        if ($model === null) {
            return null;
        }
        return $this->mergeSubHeaders($this->render($model));
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
     * @param string|null $class Filter by class
     * @return \watoki\collections\Map|SubComponent[]
     */
    public function getSubComponents($class = null) {
        $class = $class ?: SubComponent::$CLASS;
        $subs = new Map();
        foreach ($this as $name => $member) {
            if (is_a($member, $class)) {
                $subs->set($name, $member);
            }
        }
        return $subs;
    }

    public function getState() {
        $params = new Map();
        foreach ($this->getSubComponents(PlainSubComponent::$CLASS) as $name => $sub) {
            /** @var $sub PlainSubComponent */
            $nonDefaultParams = $sub->getNonDefaultParameters();
            if ($sub->hasRouteChanged()) {
                $nonDefaultParams->set(self::PARAMETER_TARGET, $sub->getRoute());
            }
            $params->set($name, $nonDefaultParams);
        }
        return $params;
    }

    private function restoreState(Map $state) {
        foreach ($this->getSubComponents(PlainSubComponent::$CLASS) as $name => $subComponent) {
            /** @var $subComponent PlainSubComponent */
            if ($state->has($name)) {
                /** @var $subState Map */
                $subState = $state->get($name);
                if ($subState->has(self::PARAMETER_TARGET)) {
                    $subComponent->setRoute($subState->remove(self::PARAMETER_TARGET));
                }
                $subComponent->getParameters()->merge($subState);
            }
        }
    }

    /**
     * @param $subName
     * @param $method
     * @return PlainSubComponent
     */
    private function renderSubComponent($subName, $method) {
        /** @var $sub PlainSubComponent */
        $sub = $this->getSubComponents(PlainSubComponent::$CLASS)->get($subName);
        $sub->setMethod($method);
        $this->$subName = new RenderedSubComponent($this, $sub->render());
        return $sub;
    }

    private function collectSubRedirects(Response $response, Map $requestParams) {
        $state = $target = null;

        foreach ($this->getSubComponents(PlainSubComponent::$CLASS) as $subName => $sub) {
            /** @var $sub PlainSubComponent */
            if ($sub->getResponse() && $sub->getResponse()->getHeaders()->has(Response::HEADER_LOCATION)) {
                if (!$target) {
                    $state = $this->getState();
                    $target = $this->createRedirectTarget($requestParams, $state);
                }

                $this->bubbleUpRedirect($subName, $sub->getResponse(), $response, $state, $requestParams, $target);
            }
        }

        return $response;
    }

    private function bubbleUpRedirect($subName, Response $subResponse, Response $response, Map $state, Map $requestParams, Url $target = null) {
        if (!$target) {
            $target = $this->createRedirectTarget($requestParams, $state);
        }

        $subTarget = Url::parse($subResponse->getHeaders()->get(Response::HEADER_LOCATION));
        $subParams = $subTarget->getParameters()->copy();
        $subParams->set(self::PARAMETER_TARGET, $subTarget->getResource());
        $target->setFragment($subTarget->getFragment());
        $state->set($subName, $subParams);

        $response->getHeaders()->set(Response::HEADER_LOCATION, $target->toString());

        return $response;
    }

    private function createRedirectTarget(Map $requestParams, Map $state) {
        $target = new Url($this->getRoute(), $requestParams);
        $target->getParameters()->set(self::PARAMETER_STATE, $state);
        return $target;
    }

}
