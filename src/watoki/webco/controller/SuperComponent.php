<?php
namespace watoki\webco\controller;

use watoki\collections\Map;
use watoki\tempan\HtmlParser;
use watoki\webco\Request;
use watoki\webco\Response;
use watoki\webco\Url;
use watoki\webco\controller\sub\HtmlSubComponent;
use watoki\webco\controller\sub\PlainSubComponent;

abstract class SuperComponent extends Component {

    public static $CLASS = __CLASS__;

    const PARAMETER_PRIMARY_REQUEST = '.';

    const PARAMETER_SUB_STATE = '.';

    const PARAMETER_TARGET = '~';

    /**
     * @var null|array|SubComponent[] Null until invokeAction has been called
     */
    public $subs;

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

    protected function invokeAction($action, Map $parameters) {
        $model = parent::invokeAction($action, $parameters);
        if ($model === null) {
            return $model;
        }

        $this->subs = $this->collectSubComponents($model);
        $preparedModel = $this->renderSubComponents($model, $this->subs, $parameters);

        $this->collectSubRedirects($this->subs, $parameters);
        return $preparedModel;
    }

    protected function render($model) {
        $rendered = parent::render($model);

        return $this->mergeSubHeaders($rendered, $this->subs);
    }

    // TODO Collect deep (names must be flat, though)
    // TODO should model have to be a Map?
    private function collectSubComponents($model) {
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
     * @param array $model
     * @param array|SubComponent[] $subs
     * @param Map $parameters
     * @return mixed
     */
    private function renderSubComponents($model, $subs, $parameters) {
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
     * @param SubComponent[] $subs
     * @param \watoki\collections\Map $state
     * @return void
     */
    private function restoreSubStates($subs, Map $state) {
        foreach ($subs as $name => $sub) {
            if ($state->has($name)) {
                /** @var $restoreSubState Map */
                $restoreSubState = $state->get($name);
                $sub->getState()->merge($restoreSubState);
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

    private function collectSubRedirects($subs, Map $requestParams) {
        $state = $target = null;

        foreach ($subs as $subName => $sub) {
            /** @var $sub PlainSubComponent */
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
