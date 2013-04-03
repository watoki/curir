<?php
namespace watoki\curir\router;
 
use watoki\curir\Controller;
use watoki\curir\Path;
use watoki\curir\Request;
use watoki\curir\Router;
use watoki\curir\controller\RedirectController;

class RedirectRouter extends Router {

    public static $CLASS = __CLASS__;

    private $route;

    private $redirect;

    function __construct(Path $route, $redirect) {
        $this->route = $route;
        $this->redirect = $redirect;
    }

    /**
     * @param Request $request
     * @return Controller
     */
    public function resolve(Request $request) {
        return $this->createController(RedirectController::$CLASS, new Path(), array('target' => $this->redirect));
    }

    /**
     * @param \watoki\curir\Path $route
     * @return boolean
     */
    public function matches(Path $route) {
        return $route == $this->route;
    }
}
