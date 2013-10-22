<?php
namespace watoki\curir;

use watoki\curir\http\Request;
use watoki\curir\http\Response;
use watoki\curir\http\Url;
use watoki\curir\resource\Container;

/**
 * A Resource responds to a Request with a Response.
 */
abstract class Resource {

    /** @var string */
    private $directory;

    /** @var string */
    private $name;

    /** @var \watoki\curir\http\Url */
    private $url;

    /** @var Container|null */
    private $parent;

    public function __construct($directory, $name, Url $url, Container $parent = null) {
        $this->directory = rtrim($directory, DIRECTORY_SEPARATOR);
        $this->name = $name;
        $this->url = $url;
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
     * @return \watoki\curir\http\Url
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * @return null|\watoki\curir\resource\Container
     */
    public function getParent() {
        return $this->parent;
    }

}
 