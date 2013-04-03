<?php
namespace watoki\curir\controller;
 
use watoki\factory\Factory;
use watoki\curir\Controller;
use watoki\curir\Path;
use watoki\curir\Request;
use watoki\curir\Response;
use watoki\curir\Url;

class RedirectController extends Controller {

    public static $CLASS = __CLASS__;

    /**
     * @var string
     */
    private $target;

    function __construct($target, Factory $factory, Path $route, Module $parent = null) {
        parent::__construct($factory, $route, $parent);
        $this->target = $target;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function respond(Request $request) {
        $this->redirect(Url::parse($this->target));
        return $this->getResponse();
    }
}
