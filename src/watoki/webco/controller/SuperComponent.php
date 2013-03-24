<?php
namespace watoki\webco\controller;

use watoki\collections\Liste;
use watoki\collections\Map;
use watoki\tempan\HtmlParser;
use watoki\webco\Request;
use watoki\webco\Response;

abstract class SuperComponent extends Component {

    public static $CLASS = __CLASS__;

    const PARAMETER_PRIMARY_REQUEST = '!';

    const PARAMETER_SUB_REQUESTS = '.';

    const PARAMETER_TARGET = '~';

    /**
     * @var null|Request
     */
    public $primaryRequest;

    public function respond(Request $request) {
        $params = $request->getParameters();
        if ($params->has(self::PARAMETER_PRIMARY_REQUEST)) {
            /** @var $subParameters Map */
            $subName = $params->get(self::PARAMETER_PRIMARY_REQUEST);
            $subRequests = $params->has(self::PARAMETER_SUB_REQUESTS)
                ? $params->get(self::PARAMETER_SUB_REQUESTS)
                : new Map();
            $subParameters = $subRequests->has($subName)
                    ? $subRequests->get($subName)
                    : new Map();

            $this->primaryRequest = new Request(
                $request->getMethod(),
                $subParameters->has(self::PARAMETER_TARGET)
                        ? $subParameters->get(self::PARAMETER_TARGET)
                        : null,
                $subParameters
            );
            $request->setMethod(Request::METHOD_GET);
        }
        return parent::respond($request);
    }

    protected function renderAction($action, Map $parameters) {
        $model = $this->invokeAction($action, $parameters);
        if ($model === null) {
            return null;
        }

        $subs = $this->collectSubComponents($model);

        if ($parameters->has(self::PARAMETER_SUB_REQUESTS)) {
            $this->restoreSubRequests($subs, $parameters->get(self::PARAMETER_SUB_REQUESTS));
        }

        $subRequests = $this->collectSubRequests($subs);
        if (!$subRequests->isEmpty()) {
            $parameters->set(self::PARAMETER_SUB_REQUESTS, $subRequests);
        }

        if ($this->primaryRequest) {
            /** @var $primarySub SubComponent */
            $primarySubName = $parameters->remove(self::PARAMETER_PRIMARY_REQUEST);
            $primarySub = $subs[$primarySubName];
            $primarySub->getRequest()->setMethod($this->primaryRequest->getMethod());
            $primarySub->getRequest()->getParameters()->merge($this->primaryRequest->getParameters());

            if ($this->primaryRequest->getResourcePath()) {
                $primarySub->getRequest()->setResourcePath($this->primaryRequest->getResourcePath());
            }
            $primaryRendered = $primarySub->getResponse($primarySubName, $parameters)->getBody();

            $model = $this->invokeAction($action, $parameters);
        }

        foreach ($subs as $name => $sub) {
            if (isset($primaryRendered) && isset($primarySubName) && $name == $primarySubName) {
                $model[$name] = $primaryRendered;
            } else {
                $model[$name] = $sub->getResponse($name, $parameters)->getBody();
            }
        }

        $this->collectSubRedirects($subs, $parameters);

        return $this->mergeSubHeaders($this->render($model), $subs);
    }

    /**
     * @param $model
     * @return array|SubComponent[]
     */
    // TODO should model have to be a Map? No. Array, Object and Map should be handled. => We need a unified iterator.
    private function collectSubComponents($model) {
        if (!is_array($model)) {
            return array();
        }
        $subs = array();
        foreach ($model as $name => $sub) {
            if ($sub instanceof SubComponent) {
                $subs[$name] = $sub;
            }
        }
        return $subs;
    }

    /**
     * @param array|SubComponent[] $subs
     * @param \watoki\collections\Map $parameters
     * @return void
     */
    private function restoreSubRequests($subs, Map $parameters) {
        foreach ($subs as $name => $sub) {
            if ($parameters->has($name)) {
                $subParameters = $parameters->get($name);
                $sub->getRequest()->getParameters()->merge($subParameters);
                if ($subParameters->has(self::PARAMETER_TARGET)) {
                    $sub->getRequest()->setResourcePath(Liste::split('/', $subParameters->get(self::PARAMETER_TARGET)));
                }

            }
        }
    }

    /**
     * @param array|SubComponent[] $subs
     * @return Map
     */
    private function collectSubRequests($subs) {
        $requests = new Map();
        foreach ($subs as $name => $sub) {
            $parameters = $sub->getNonDefaultRequest()->getParameters();
            if (!$parameters->isEmpty()) {
                $requests->set($name, $parameters);
            }
        }
        return $requests;
    }

    // TODO This needs to be done by the SubComponent and decoupled asset management
    private function mergeSubHeaders($body, array $subs) {
        return $body;
//        $parser = new HtmlParser($body);
//
//        foreach ($subs as $sub) {
//            if ($sub instanceof HtmlSubComponent) {
//                if (!isset($head)) {
//                    $head = $parser->getRoot()->firstChild;
//                    if ($head->nodeName != 'head') {
//                        $body = $head;
//                        $head = $parser->getDocument()->createElement('head');
//                        $parser->getRoot()->insertBefore($head, $body);
//                    }
//                }
//
//                foreach ($sub->getHeadElements('link') as $element) {
//                    $head->appendChild($parser->getDocument()->importNode($element, true));
//                }
//            }
//        }
//
//        return isset($parser) ? $parser->toString() : $body;
    }

    /**
     * @param array|SubComponent[] $subs
     * @param Map $requestParams
     */
    private function collectSubRedirects($subs, Map $requestParams) {
//        $state = $target = null;
//
//        foreach ($subs as $subName => $sub) {
//            if (!$sub instanceof PlainSubComponent) {
//                continue;
//            }
//
//            $subResponse = $sub->getResponse();
//            if ($subResponse && $subResponse->getHeaders()->has(Response::HEADER_LOCATION)) {
//                if (!$target) {
//                    $state = new Map();
//                    $target = $this->createRedirectTarget($requestParams, $state);
//                }
//
//                $this->bubbleUpRedirect($subName, $sub->getResponse(), $state, $requestParams, $target);
//            }
//        }
    }

//    private function bubbleUpRedirect($subName, Response $subResponse, Map $state, Map $requestParams, Url $target = null) {
//        if (!$target) {
//            $target = $this->createRedirectTarget($requestParams, $state);
//        }
//
//        $subTarget = Url::parse($subResponse->getHeaders()->get(Response::HEADER_LOCATION));
//        $subParams = $subTarget->getParameters()->copy();
//        $subParams->set(self::PARAMETER_TARGET, $subTarget->getResource());
//        $target->setFragment($subTarget->getFragment());
//        $state->set($subName, $subParams);
//
//        $response = $this->getResponse();
//        $response->getHeaders()->set(Response::HEADER_LOCATION, $target->toString());
//    }
//
//    private function createRedirectTarget(Map $requestParams, Map $state) {
//        $target = new Url($this->getRoute(), $requestParams);
//        $target->getParameters()->set(self::PARAMETER_SUB_STATE, $state);
//        return $target;
//    }

}
