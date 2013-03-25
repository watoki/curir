<?php
namespace watoki\webco\router;
 
use watoki\webco\Controller;
use watoki\webco\Path;
use watoki\webco\Request;
use watoki\webco\Router;
use watoki\webco\controller\RedirectController;

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
     * @param \watoki\webco\Path $route
     * @return boolean
     */
    public function matches(Path $route) {
        return $route == $this->route;
    }
}
