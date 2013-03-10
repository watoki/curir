<?php
namespace watoki\webco;
 
use watoki\factory\Factory;
use watoki\collections\Liste;
use watoki\webco\controller\Module;

abstract class Controller {

    public static $CLASS = __CLASS__;

    /**
     * @var \watoki\factory\Factory
     */
    protected $factory;

    /**
     * @var string
     */
    protected $route;

    /**
     * @var Module|null
     */
    private $parent;

    /**
     * @var Response
     */
    private $response;

    function __construct(Factory $factory, $route, Module $parent = null) {
        $this->parent = $parent;
        $this->factory = $factory;
        $this->route = $route;
    }

    /**
     * @param Request $request
     * @return Response
     */
    abstract public function respond(Request $request);

    /**
     * @return string
     */
    public function getRoute() {
        return $this->route;
    }

    protected function getResponse() {
        if (!$this->response) {
            $this->response = new Response();
        }
        return $this->response;
    }

    /**
     * @return null|\watoki\webco\controller\Module
     */
    protected function getParent() {
        return $this->parent;
    }

    /**
     * @return \watoki\webco\controller\Module
     */
    public function getRoot() {
        if ($this->parent) {
            return $this->parent->getRoot();
        }
        return $this;
    }

    protected function getDirectory() {
        $class = new \ReflectionClass($this);
        return dirname($class->getFileName());
    }

    protected function redirect(Url $url) {
        $urlString = $url->toString();
        if ($url->isRelative()) {
            $urlString = $this->getBaseRoute() . $urlString;
        }
        $response = $this->getResponse();
        $response->getHeaders()->set(Response::HEADER_LOCATION, $urlString);
        return null;
    }

    abstract protected function getBaseRoute();
}
