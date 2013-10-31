<?php
namespace watoki\curir;

use watoki\curir\http\Request;
use watoki\curir\http\Response;
use watoki\curir\resource\Container;

/**
 * A Resource responds to a Request with a Response.
 */
abstract class Resource {

    /** @var string */
    private $name;

    /** @var Container|null */
    private $parent;

    public function __construct($name, Resource $parent = null) {
        $this->name = $name;
        $this->parent = $parent;
    }

    /**
     * @param Request $request
     * @return Response
     */
    abstract public function respond(Request $request);

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return null|\watoki\curir\resource\Resource
     */
    public function getParent() {
        return $this->parent;
    }

}
 