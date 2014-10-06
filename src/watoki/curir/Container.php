<?php
namespace watoki\curir;

use watoki\curir\delivery\WebRequest;
use watoki\curir\delivery\WebResponse;
use watoki\deli\Request;
use watoki\deli\Responding;
use watoki\deli\Router;
use watoki\deli\target\ObjectTarget;
use watoki\factory\Factory;

class Container extends Resource implements Responding {

    /** @var Router */
    protected $router;

    /**
     * @param Factory $factory <-
     */
    function __construct(Factory $factory) {
        parent::__construct($factory);
        $this->router = WebRouter::fromResource($this, $factory);
    }

    /**
     * @param Request|WebRequest $request
     * @return WebResponse
     */
    public function respond(Request $request) {
        if ($request->getTarget()->isEmpty()) {
            $target = new ObjectTarget($request, $this, $this->factory);
        } else {
            $target = $this->router->route($request);
        }
        return $target->respond();
    }

}