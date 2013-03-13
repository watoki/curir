<?php
namespace watoki\webco\router;
 
use watoki\webco\Controller;
use watoki\webco\Request;
use watoki\webco\Router;
use watoki\webco\controller\RedirectController;

class RedirectRouter extends Router {

    public static $CLASS = __CLASS__;

    private $route;

    private $redirect;

    function __construct($route, $redirect) {
        $this->route = $route;
        $this->redirect = $redirect;
    }

    /**
     * @param Request $request
     * @return Controller
     */
    public function resolve(Request $request) {
        return $this->createController(RedirectController::$CLASS, '', array('target' => $this->redirect));
    }

    /**
     * @param string $route
     * @return boolean
     */
    public function matches($route) {
        return $route == $this->route;
    }
}
