<?php
namespace watoki\curir;
 
use watoki\factory\Factory;
use watoki\collections\Liste;
use watoki\curir\controller\Module;

abstract class Controller {

    public static $CLASS = __CLASS__;

    /** @var \watoki\factory\Factory */
    protected $factory;

    /** @var Path */
    private $route;

    /** @var Module|null */
    private $parent;

    /** @var Response */
    private $response;

    /**
     * @param Factory $factory
     * @param Path $route
     * @param Module $parent
     */
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
     * @return \watoki\curir\Path
     */
    public function getRoute() {
        return $this->route->copy();
    }

    /**
     * @return Response
     */
    public function getResponse() {
        if (!$this->response) {
            $this->response = new Response();
        }
        return $this->response;
    }

    /**
     * @return null|Module
     */
    public function getParent() {
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

    /**
     * @return Path
     */
    public function getBaseRoute() {
        return $this->getRoute();
    }

    /**
     * @param Url $url
     * @return null
     */
    protected function redirect(Url $url) {
        if (!$url->getPath()->isAbsolute()) {
            $url->getPath()->getNodes()->insertAll($this->getBaseRoute()->getNodes(), 0);
        }
        $this->getResponse()->getHeaders()->set(Response::HEADER_LOCATION, $url->toString());
        return null;
    }
}
