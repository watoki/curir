<?php
namespace watoki\webco\controller;
 
use watoki\factory\Factory;
use watoki\webco\Controller;
use watoki\webco\Path;
use watoki\webco\Request;
use watoki\webco\Response;
use watoki\webco\Url;

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
