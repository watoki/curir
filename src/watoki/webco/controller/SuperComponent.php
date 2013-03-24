<?php
namespace watoki\webco\controller;

use watoki\collections\Liste;
use watoki\collections\Map;
use watoki\tempan\HtmlParser;
use watoki\webco\Request;
use watoki\webco\Response;
use watoki\webco\Url;

abstract class SuperComponent extends Component {

    public static $CLASS = __CLASS__;

    const PARAMETER_PRIMARY_REQUEST = '!';

    const PARAMETER_SUB_REQUESTS = '.';

    const PARAMETER_TARGET = '~';

    /**
     * @var null|SubComponent
     */
    public $primaryRequestSub;

    /**
     * @var null|string
     */
    public $primaryRequestSubName;

    public function respond(Request $request) {
        $params = $request->getParameters();
        if ($params->has(self::PARAMETER_PRIMARY_REQUEST)) {
            $response = $this->getPrimaryRequestResponse($params);

            if ($response->getHeaders()->has(Response::HEADER_LOCATION)) {
                return $this->bubbleUpRedirect($this->primaryRequestSubName, $response, $params);
            }

            $request->setMethod(Request::METHOD_GET);
        }
        return parent::respond($request);
    }

    /**
     * @param Map $params
     * @return Response
     */
    private function getPrimaryRequestResponse(Map $params) {
        $this->primaryRequestSubName = $params->remove(self::PARAMETER_PRIMARY_REQUEST);
        $subRequests = $params->get(self::PARAMETER_SUB_REQUESTS);
        /** @var $subParameters Map */
        $subParameters = $subRequests->get($this->primaryRequestSubName);
        $subTarget = $subParameters->get(self::PARAMETER_TARGET);

        $this->primaryRequestSub = new SubComponent($this, null, $subParameters);
        $this->primaryRequestSub->getRequest()->setResourcePath(Liste::split('/', $subTarget));
        $response = $this->primaryRequestSub->execute($this->primaryRequestSubName, $params);
        return $response;
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

        $responses = array();
        foreach ($subs as $name => $sub) {
            if ($name == $this->primaryRequestSubName) {
                $subs[$name] = $this->primaryRequestSub;
                $response = $this->primaryRequestSub->getResponse();
            } else {
                $response = $sub->execute($name, $parameters);
                $responses[$name] = $response;
            }
            $model[$name] = $response->getBody();
        }

        $this->collectSubRedirects($responses, $parameters);

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
     * @param array|Response[] $responses
     * @param Map $requestParams
     */
    private function collectSubRedirects($responses, Map $requestParams) {
        $target = null;

        foreach ($responses as $subName => $subResponse) {
            if ($subResponse->getHeaders()->has(Response::HEADER_LOCATION)) {
                if (!$target) {
                    $target = new Url($this->getRoute(), $requestParams);
                }

                $this->bubbleUpRedirect($subName, $subResponse, $requestParams, $target);
            }
        }
    }

    private function bubbleUpRedirect($subName, Response $subResponse, Map $requestParams, Url $target = null) {
        if (!$target) {
            $target = new Url($this->getRoute(), $requestParams);
        }

        $subTarget = Url::parse($subResponse->getHeaders()->get(Response::HEADER_LOCATION));
        $subParams = $subTarget->getParameters()->copy();
        $subParams->set(self::PARAMETER_TARGET, $subTarget->getResource());
        $target->setFragment($subTarget->getFragment());
        $requestParams->get(self::PARAMETER_SUB_REQUESTS)->set($subName, $subParams);

        $response = $this->getResponse();
        $response->getHeaders()->set(Response::HEADER_LOCATION, $target->toString());
        return $response;
    }

}
