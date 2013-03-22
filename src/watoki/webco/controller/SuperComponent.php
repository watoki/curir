<?php
namespace watoki\webco\controller;

use watoki\collections\Map;
use watoki\tempan\HtmlParser;
use watoki\webco\Request;
use watoki\webco\Response;
use watoki\webco\Url;
use watoki\webco\controller\sub\HtmlSubComponent;
use watoki\webco\controller\sub\PlainSubComponent;
use watoki\webco\controller\sub\RenderedSubComponent;

abstract class SuperComponent extends Component {

    public static $CLASS = __CLASS__;

    const PARAMETER_PRIMARY_REQUEST = '!';

    const PARAMETER_SUB_STATE = '.';

    const PARAMETER_TARGET = '~';

    /**
     * @var null|array|SubComponent[]
     */
    public $subs = array();

    public function respond(Request $request) {
        $subResponse = $this->handlePrimaryRequest($request);
        if ($subResponse) {
            return $subResponse;
        }

        return parent::respond($request);
    }

    /**
     * @param Request $request
     * @return Response|null If a response is returned, it should be returned immediately
     */
    private function handlePrimaryRequest(Request $request) {
        $params = $request->getParameters();
        if ($params->has(self::PARAMETER_PRIMARY_REQUEST)) {
            $subName = $params->remove(self::PARAMETER_PRIMARY_REQUEST);

            $subState = $this->getSubState($params, $subName);
            $sub = $this->createSubComponent($subState);
            $sub->setMethod($request->getMethod());
            $request->setMethod(Request::METHOD_GET);

            $rendered = $sub->render($subName, $subState);

            if ($sub->getResponse()->getHeaders()->has(Response::HEADER_LOCATION)) {
                $this->bubbleUpRedirect($subName, $sub->getResponse(), new Map(), $request->getParameters()->copy());
                return $this->getResponse();
            }

            $this->subs[$subName] = new RenderedSubComponent($this, $rendered);
        }
        return null;
    }

    /**
     * @param Map $params
     * @param $subName
     * @return Map
     */
    private function getSubState(Map $params, $subName) {
        $subParams = new Map();
        if ($params->has(self::PARAMETER_SUB_STATE) && $params->get(self::PARAMETER_SUB_STATE)->has($subName)) {
            $subParams = $params->get(self::PARAMETER_SUB_STATE)->get($subName);
            return $subParams;
        }
        return $subParams;
    }

    private function createSubComponent(Map $state) {
        $sub = new HtmlSubComponent($this, null);
        $sub->getState()->merge($state);
        return $sub;
    }

    protected function invokeAction($action, Map $parameters) {
        $model = parent::invokeAction($action, $parameters);
        if ($model === null) {
            return $model;
        }

        $this->collectSubComponents($model);
        $preparedModel = $this->renderSubComponents($model, $parameters);

        $this->collectSubRedirects($parameters);
        return $preparedModel;
    }

    protected function render($model) {
        return $this->mergeSubHeaders(parent::render($model), $this->subs);
    }

    // TODO should model have to be a Map? No. Array, Object and Map should be handled. => We need a unified iterator.
    private function collectSubComponents($model) {
        if (!is_array($model)) {
            return;
        }
        foreach ($model as $name => $sub) {
            if ($sub instanceof SubComponent && !array_key_exists($name, $this->subs)) {
                $this->subs[$name] = $sub;
            }
        }
    }

    /**
     * @param array $model
     * @param Map $parameters
     * @return mixed
     */
    private function renderSubComponents($model, $parameters) {
        if ($parameters->has(self::PARAMETER_SUB_STATE)) {
            $this->restoreSubStates($parameters->get(self::PARAMETER_SUB_STATE));
        }

        $subStates = $this->collectSubStates();

        $state = $parameters->copy();
        if (!$subStates->isEmpty()) {
            $state->set(self::PARAMETER_SUB_STATE, $subStates);
        }

        foreach ($this->subs as $name => $sub) {
            $model[$name] = $sub->render($name, $state);
        }
        return $model;
    }

    /**
     * @param \watoki\collections\Map $state
     * @return void
     */
    private function restoreSubStates(Map $state) {
        foreach ($this->subs as $name => $sub) {
            if ($state->has($name)) {
                /** @var $restoreSubState Map */
                $restoreSubState = $state->get($name);
                $sub->getState()->merge($restoreSubState);
            }
        }
    }

    /**
     * @return Map
     */
    private function collectSubStates() {
        $subStates = new Map();
        foreach ($this->subs as $name => $sub) {
            $subState = $sub->getNonDefaultState();
            if (!$subState->isEmpty()) {
                $subStates->set($name, $subState);
            }
        }
        return $subStates;
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

    private function collectSubRedirects(Map $requestParams) {
        $state = $target = null;

        foreach ($this->subs as $subName => $sub) {
            if (!$sub instanceof PlainSubComponent) {
                continue;
            }

            $subResponse = $sub->getResponse();
            if ($subResponse && $subResponse->getHeaders()->has(Response::HEADER_LOCATION)) {
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
