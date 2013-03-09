<?php
namespace spec\watoki\webco\steps;
 
use spec\watoki\webco\Step;
use watoki\collections\Map;
use watoki\factory\Factory;
use \watoki\webco\controller\Module;
use watoki\webco\Request;
use watoki\webco\Response;

class When extends Step {

    /**
     * @var Response
     */
    public $response;

    /**
     * @var null|\Exception
     */
    public $caught;

    public function iSendTheRequestTo($controllerClass) {
        $factory = new Factory();

        $request = new Request($this->test->given->requestMethod,
            $this->test->given->requestResource,
            new Map($this->test->given->requestParams),
            new Map($this->test->given->requestHeaders)
        );

        /** @var $controllerClass \watoki\webco\controller\Module */
        $controllerClass = $factory->getInstance($controllerClass, array('route' => '/base/'));

        $this->response = $controllerClass->respond($request);
    }

    public function iTryToSendTheRequestTo($controllerClass) {
        $this->caught = null;
        try {
            $this->iSendTheRequestTo($controllerClass);
        } catch (\Exception $e) {
            $this->caught = $e;
        }
    }

}
