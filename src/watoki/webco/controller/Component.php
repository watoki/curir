<?php
namespace watoki\webco\controller;
 
use watoki\collections\Map;
use watoki\webco\Controller;
use watoki\webco\Request;
use watoki\webco\Response;
use watoki\webco\Url;

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
        $body = $model !== null ? $this->render($model) : null;
        return $body;
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
    protected function makeMethodName($actionName) {
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

    protected function redirect(Url $url) {
        $urlString = $url->toString();
        if ($url->isRelative()) {
            $urlString = $this->route . $urlString;
        }
        $response = $this->getResponse();
        $response->getHeaders()->set(Response::HEADER_LOCATION, $urlString);
        return null;
    }

}
