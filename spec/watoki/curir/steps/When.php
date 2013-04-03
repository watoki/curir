<?php
namespace spec\watoki\curir\steps;
 
use spec\watoki\curir\Step;
use watoki\collections\Map;
use watoki\factory\Factory;
use \watoki\curir\controller\Module;
use watoki\curir\Request;
use watoki\curir\Response;

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

        /** @var $controllerClass \watoki\curir\controller\Module */
        $controllerClass = $factory->getInstance($controllerClass, array('route' => $this->test->given->moduleRoute));

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
