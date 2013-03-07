<?php
namespace watoki\webco;
 
use watoki\factory\Factory;
use watoki\collections\Liste;

abstract class Controller {

    public static $CLASS = __CLASS__;

    /**
     * @var Module|null
     */
    private $parent;

    /**
     * @var Response
     */
    private $response;

    function __construct(Module $parent) {
        $this->parent = $parent;
    }

    /**
     * @param Request $request
     * @return Response
     */
    abstract public function respond(Request $request);

    protected function getResponse() {
        if (!$this->response) {
            $this->response = new Response();
        }
        return $this->response;
    }

    /**
     * @return null|\watoki\webco\Module
     */
    protected function getParent() {
        return $this->parent;
    }

    protected function getDirectory() {
        $class = new \ReflectionClass($this);
        return dirname($class->getFileName());
    }

}
