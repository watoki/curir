<?php
namespace watoki\curir\composition;

use watoki\collections\Map;
use watoki\dom\Element;
use watoki\dom\Parser;
use watoki\dom\Printer;
use watoki\curir\Path;
use watoki\curir\Request;
use watoki\curir\Response;
use watoki\curir\Url;
use watoki\curir\composition\SubComponent;
use watoki\curir\controller\Component;

abstract class SuperComponent extends Component {

    public static $CLASS = __CLASS__;

    const PARAMETER_PRIMARY_REQUEST = '!';

    const PARAMETER_SUB_REQUESTS = ':';

    const PARAMETER_TARGET = '~';

    /**
     * @var null|SubComponent
     */
    public $primaryRequestSub;

    /**
     * @var null|string
     */
    public $primaryRequestSubName;

    protected function subComponent($componentClass, Map $parameters = null) {
        $foundController = $this->getRoot()->findController($componentClass);

        if (!$foundController) {
            throw new \Exception('Could not find a route to ' . $componentClass);
        }

        $route = $foundController->getRoute();
        return new SubComponent($this, $route, $parameters);
    }

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
        $this->primaryRequestSub->getRequest()->setResource(Path::parse($subTarget));
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
                $response = $sub->execute($name, $parameters->deepCopy());
                $responses[$name] = $response;
            }
            $this->setModelKey($model, $name, $response->getBody());
        }

        $this->collectSubRedirects($responses, $parameters);

        return $this->mergeSubHeaders($this->render($model), $subs);
    }

    // TODO (2) This could do without reference passing using a Map
    public function setModelKey(array &$model, $name, $value) {
        foreach (explode('.', $name) as $part) {
            $model =& $model[$part];
        }
        $model = $value;
    }

    /**
     * @param $model
     * @param string $prefix
     * @return array|SubComponent[]
     */
    // TODO (2) should model have to be a Map? No. Array, Object and Map should be handled. => We need a unified iterator.
    private function collectSubComponents($model, $prefix = '') {
        if (!is_array($model)) {
            return array();
        }
        $subs = array();
        foreach ($model as $key => $value) {
            if ($value instanceof SubComponent) {
                $subs[$prefix . $key] = $value;
            } else if (is_array($value)) {
                $subs = array_merge($subs, $this->collectSubComponents($value, $prefix . $key . '.'));
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
                    $sub->getRequest()->setResource(Path::parse($subParameters->get(self::PARAMETER_TARGET)));
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
                $requests->set($name, $parameters->deepCopy());
            }
        }
        return $requests;
    }

    /**
     * @param string $body
     * @param array|SubComponent[] $subs
     * @return Element|mixed|string
     */
    private function mergeSubHeaders($body, array $subs) {
        $parser = new Parser($body);
        $head = null;

        foreach ($subs as $sub) {
            foreach ($sub->getHeadElements('link') as $element) {
                if (!$head) {
                    $root = $parser->findElement('html');
                    $head = $parser->findElement('head');
                    if (!$head) {
                        $head = new Element('head');
                        $root->getChildren()->insert($head, 0);
                    }
                }
                $head->getChildren()->append($element);
            }
        }

        $printer = new Printer();
        return $head ? $printer->printNodes($parser->getNodes()) : $body;
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

        $subTarget = Url::parse(urldecode($subResponse->getHeaders()->get(Response::HEADER_LOCATION)));
        $subParams = $subTarget->getParameters()->copy();
        $subParams->set(self::PARAMETER_TARGET, $subTarget->getPath()->toString());
        $target->setFragment($subTarget->getFragment());
        $requestParams->get(self::PARAMETER_SUB_REQUESTS)->set($subName, $subParams);

        $response = $this->getResponse();
        $response->getHeaders()->set(Response::HEADER_LOCATION, $target->toString());
        return $response;
    }

}
