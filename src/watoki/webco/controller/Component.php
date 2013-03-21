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

abstract class Component extends Controller {

    public static $CLASS = __CLASS__;

    const PARAMETER_PRIMARY_REQUEST = '.';

    const PARAMETER_SUB_STATE = '.';

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
        $subResponse = $this->handlePrimaryRequest($request);
        if ($subResponse) {
            return $subResponse;
        }

        $response = $this->getResponse();
        $methodName = $this->makeMethodName($this->getActionName($request));
        $response->setBody($this->renderAction($methodName, $request->getParameters()));

        return $response;
    }

    /**
     * @param Request $request
     * @return Response|null If a response is returned, it should be returned immediately
     */
    // TODO This should be somehow passed into the model
    private function handlePrimaryRequest(Request $request) {
        if ($request->getParameters()->has(self::PARAMETER_SUB_STATE)) {
            $subState = $request->getParameters()->get(self::PARAMETER_SUB_STATE);
            if ($subState->has(self::PARAMETER_PRIMARY_REQUEST)) {
                $primarySubName = $subState->get(self::PARAMETER_PRIMARY_REQUEST);

                $primaryResponse = $this->getPrimaryRequestResponse($primarySubName, $subState, $request);

                if ($primaryResponse->getHeaders()->has(Response::HEADER_LOCATION)) {
                    $this->bubbleUpRedirect($primarySubName, $primaryResponse, new Map(), $request->getParameters()->copy());
                    return $this->getResponse();
                }
            }
        }
        return null;
    }

    /**
     * @param $primarySubName
     * @param $subState
     * @param Request $request
     * @return Response
     */
    private function getPrimaryRequestResponse($primarySubName, Map $subState, Request $request) {
        /** @var $primaryParameters Map */
        $primaryParameters = $subState->get($primarySubName);

        $primaryTarget = $primaryParameters->get(self::PARAMETER_TARGET);

        $primarySub = $this->getRoot()->resolve($primaryTarget);

        $primaryResponse = $primarySub->respond(new Request($request->getMethod(), '', $primaryParameters));
        $request->setMethod(Request::METHOD_GET);

        return $primaryResponse;
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

        $subs = $this->collectSubComponents($model);
        $preparedModel = $this->renderSubComponents($model, $subs, $parameters);
        $rendered = $this->render($preparedModel);

        $this->collectSubRedirects($subs, $parameters);

        return $this->mergeSubHeaders($rendered, $subs);
    }

    /**
     * @param $action
     * @param Map $parameters
     * @throws \Exception
     * @return mixed
     */
    protected function invokeAction($action, Map $parameters) {
        if (!method_exists($this, $action)) {
            throw new \Exception('Method [' . $action . '] not found in component [' . get_class($this) . '].');
        }

        $method = new \ReflectionMethod($this, $action);
        $args = $this->collectArguments($parameters, $method);

        return $method->invokeArgs($this, $args);
    }

    // TODO Collect deep (names must be flat, though)
    // TODO should model have to be a Map?
    protected function collectSubComponents($model) {
        if (!is_array($model)) {
            return array();
        }
        $subs = array();
        foreach ($model as $field => $value) {
            if ($value instanceof SubComponent) {
                $subs[$field] = $value;
            }
        }
        return $subs;
    }

    /**
     * @param \watoki\collections\Map $parameters
     * @param \ReflectionMethod $method
     * @throws \Exception If a parameter is missing
     * @return array
     */
    private function collectArguments(Map $parameters, \ReflectionMethod $method) {
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

    /**
     * @param array $model
     * @param array|SubComponent[] $subs
     * @param Map $parameters
     * @return mixed
     */
    protected function renderSubComponents($model, $subs, $parameters) {
        if ($parameters->has(self::PARAMETER_SUB_STATE)) {
            $this->restoreSubStates($subs, $parameters->get(self::PARAMETER_SUB_STATE));
        }

        $subStates = $this->collectSubStates($subs);

        $state = $parameters->copy();
        if (!$subStates->isEmpty()) {
            $state->set(self::PARAMETER_SUB_STATE, $subStates);
        }

        foreach ($subs as $name => $sub) {
            $model[$name] = $sub->render($name, $state);
        }
        return $model;
    }

    /**
     * @param $subs
     * @param \watoki\collections\Map $state
     * @return void
     */
    private function restoreSubStates($subs, Map $state) {
        foreach ($subs as $name => $sub) {
            if ($sub instanceof PlainSubComponent && $state->has($name)) {
                /** @var $restoreSubState Map */
                $restoreSubState = $state->get($name);
                if ($restoreSubState->has(self::PARAMETER_TARGET)) {
                    $sub->setRoute($restoreSubState->get(self::PARAMETER_TARGET));
                }
                $sub->getParameters()->merge($restoreSubState);
            }
        }
    }

    /**
     * @param SubComponent[] $subs
     * @return Map
     */
    private function collectSubStates($subs) {
        $subStates = new Map();
        foreach ($subs as $name => $sub) {
            $subState = $sub->getState();
            if (!$subState->isEmpty()) {
                $subStates->set($name, $subState);
            }
        }
        return $subStates;
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

    // TODO This needs to be done by the SubComponent and decoupled asset management
    private function mergeSubHeaders($body, array $subs) {
        $parser = new HtmlParser($body);

        foreach ($subs as $sub) {
            if ($sub instanceof HtmlSubComponent) {
                if (!isset($head)) {
                    $head = $parser->getRoot()->firstChild;
                    if ($head->nodeName != 'head') {
                        $body = $head;
                        $head = $parser->getDocument()->createElement('head');
                        $parser->getRoot()->insertBefore($head, $body);
                    }
                }

                foreach ($sub->getHeadElements('link') as $element) {
                    $head->appendChild($parser->getDocument()->importNode($element, true));
                }
            }
        }

        return isset($parser) ? $parser->toString() : $body;
    }

    private function collectSubRedirects($subs, Map $requestParams) {
        $state = $target = null;

        foreach ($subs as $subName => $sub) {
            /** @var $sub PlainSubComponent */
            if ($sub->getResponse() && $sub->getResponse()->getHeaders()->has(Response::HEADER_LOCATION)) {
                if (!$target) {
                    $state = new Map();
                    $target = $this->createRedirectTarget($requestParams, $state);
                }

                $this->bubbleUpRedirect($subName, $sub->getResponse(), $state, $requestParams, $target);
            }
        }
    }

    private function bubbleUpRedirect($subName, Response $subResponse, Map $state, Map $requestParams, Url $target = null) {
        if (!$target) {
            $target = $this->createRedirectTarget($requestParams, $state);
        }

        $subTarget = Url::parse($subResponse->getHeaders()->get(Response::HEADER_LOCATION));
        $subParams = $subTarget->getParameters()->copy();
        $subParams->set(self::PARAMETER_TARGET, $subTarget->getResource());
        $target->setFragment($subTarget->getFragment());
        $state->set($subName, $subParams);

        $response = $this->getResponse();
        $response->getHeaders()->set(Response::HEADER_LOCATION, $target->toString());
    }

    private function createRedirectTarget(Map $requestParams, Map $state) {
        $target = new Url($this->getRoute(), $requestParams);
        $target->getParameters()->set(self::PARAMETER_SUB_STATE, $state);
        return $target;
    }

}
