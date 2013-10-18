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
    private $directory;

    /** @var string */
    private $name;

    /** @var Container|null */
    private $parent;

    /**
     * @param string $directory
     * @param string $name
     * @param Container|null $parent
     */
    public function __construct($directory, $name, Container $parent = null) {
        $this->directory = rtrim($directory, DIRECTORY_SEPARATOR);
        $this->parent = $parent;
        $this->name = $name;
    }

    /**
     * @param Request $request
     * @return Response
     */
    abstract public function respond(Request $request);

    /**
     * @return string
     */
    public function getDirectory() {
        return $this->directory;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return null|\watoki\curir\resource\Container
     */
    public function getParent() {
        return $this->parent;
    }

}
 