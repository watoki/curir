<?php
namespace watoki\curir;
 
use watoki\factory\Factory;
use watoki\collections\Liste;
use watoki\curir\controller\Module;

abstract class Controller {

    public static $CLASS = __CLASS__;

    /**
     * @var \watoki\factory\Factory
     */
    protected $factory;

    /**
     * @var Path
     */
    private $route;

    /**
     * @var Module|null
     */
    private $parent;

    /**
     * @var Response
     */
    private $response;

    function __construct(Factory $factory, Path $route, Module $parent = null) {
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
     * @return Path
     */
    public function getRoute() {
        return $this->route->copy();
    }

    public function getResponse() {
        if (!$this->response) {
            $this->response = new Response();
        }
        return $this->response;
    }

    /**
     * @return null|\watoki\curir\controller\Module
     */
    protected function getParent() {
        return $this->parent;
    }

    /**
     * @return \watoki\curir\controller\Module
     */
    public function getRoot() {
        if ($this->parent) {
            return $this->parent->getRoot();
        }
        return $this;
    }

    protected function redirect(Url $url) {
        if (!$url->getPath()->isAbsolute()) {
            $url->getPath()->getNodes()->insertAll($this->getBaseRoute()->getNodes(), 0);
        }
        $this->getResponse()->getHeaders()->set(Response::HEADER_LOCATION, $url->toString());
        return null;
    }

    protected function getBaseRoute() {
        return $this->getRoute();
    }
}
